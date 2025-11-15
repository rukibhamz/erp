<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Estimate_model extends Base_Model {
    protected $table = 'estimates';
    
    public function getNextEstimateNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(estimate_number, 5) AS UNSIGNED)) as max_code
                 FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE estimate_number LIKE 'EST-%'"
            );
            $nextNum = ($result && isset($result['max_code'])) ? intval($result['max_code']) + 1 : 1;
            return 'EST-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Estimate_model getNextEstimateNumber error: ' . $e->getMessage());
            return 'EST-00001';
        }
    }
    
    public function getItems($estimateId) {
        try {
            return $this->db->fetchAll(
                "SELECT ei.*, p.product_name, p.product_code
                 FROM `" . $this->db->getPrefix() . "estimate_items` ei
                 LEFT JOIN `" . $this->db->getPrefix() . "products` p ON ei.product_id = p.id
                 WHERE ei.estimate_id = ?
                 ORDER BY ei.id",
                [$estimateId]
            );
        } catch (Exception $e) {
            error_log('Estimate_model getItems error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function convertToInvoice($estimateId, $invoiceData) {
        try {
            $this->db->beginTransaction();
            
            // Get estimate
            $estimate = $this->getById($estimateId);
            if (!$estimate) {
                throw new Exception('Estimate not found');
            }
            
            // Get estimate items
            $items = $this->getItems($estimateId);
            
            // Create invoice (using Invoice_model)
            $invoiceModel = $this->loadModel('Invoice_model');
            $invoiceId = $invoiceModel->create($invoiceData);
            
            // Copy items to invoice
            foreach ($items as $item) {
                $invoiceModel->addItem($invoiceId, [
                    'product_id' => $item['product_id'],
                    'item_description' => $item['item_description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'discount_rate' => $item['discount_rate'],
                    'discount_amount' => $item['discount_amount'],
                    'line_total' => $item['line_total']
                ]);
            }
            
            // Update estimate status
            $this->update($estimateId, [
                'status' => 'converted',
                'converted_to_invoice_id' => $invoiceId
            ]);
            
            $this->db->commit();
            return $invoiceId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Estimate_model convertToInvoice error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByStatus($status) {
        try {
            return $this->db->fetchAll(
                "SELECT e.*, c.company_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` e
                 JOIN `" . $this->db->getPrefix() . "customers` c ON e.customer_id = c.id
                 WHERE e.status = ?
                 ORDER BY e.estimate_date DESC",
                [$status]
            );
        } catch (Exception $e) {
            error_log('Estimate_model getByStatus error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getExpired() {
        try {
            return $this->db->fetchAll(
                "SELECT e.*, c.company_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` e
                 JOIN `" . $this->db->getPrefix() . "customers` c ON e.customer_id = c.id
                 WHERE e.status IN ('draft', 'sent') AND e.expiry_date < CURDATE()
                 ORDER BY e.expiry_date ASC"
            );
        } catch (Exception $e) {
            error_log('Estimate_model getExpired error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function addItem($estimateId, $itemData) {
        try {
            $sql = "INSERT INTO `" . $this->db->getPrefix() . "estimate_items` 
                    (estimate_id, product_id, item_description, quantity, unit_price, tax_rate, tax_amount, discount_rate, discount_amount, line_total, account_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            return $this->db->query($sql, [
                $estimateId,
                $itemData['product_id'] ?? null,
                $itemData['item_description'] ?? '',
                $itemData['quantity'] ?? 0,
                $itemData['unit_price'] ?? 0,
                $itemData['tax_rate'] ?? 0,
                $itemData['tax_amount'] ?? 0,
                $itemData['discount_rate'] ?? 0,
                $itemData['discount_amount'] ?? 0,
                $itemData['line_total'] ?? 0,
                $itemData['account_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log('Estimate_model addItem error: ' . $e->getMessage());
            return false;
        }
    }
    
    protected function loadModel($model) {
        // Simple model loader - can be enhanced
        $modelFile = BASEPATH . 'models/' . $model . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        }
        return null;
    }
}

