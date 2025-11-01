<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_model extends Base_Model {
    protected $table = 'invoices';
    
    public function getNextInvoiceNumber() {
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED)) as max_num 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE invoice_number LIKE 'INV-%'"
        );
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return 'INV-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    public function getWithCustomer($invoiceId) {
        $sql = "SELECT i.*, c.company_name, c.email, c.phone, c.address, c.city, c.state, c.zip_code, c.country
                FROM `" . $this->db->getPrefix() . $this->table . "` i
                JOIN `" . $this->db->getPrefix() . "customers` c ON i.customer_id = c.id
                WHERE i.id = ?";
        return $this->db->fetchOne($sql, [$invoiceId]);
    }
    
    public function getItems($invoiceId) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . "invoice_items` WHERE invoice_id = ? ORDER BY id",
            [$invoiceId]
        );
    }
    
    public function addPayment($invoiceId, $amount) {
        $invoice = $this->getById($invoiceId);
        if (!$invoice) return false;
        
        $paidAmount = floatval($invoice['paid_amount']) + floatval($amount);
        $balanceAmount = floatval($invoice['total_amount']) - $paidAmount;
        
        $status = 'paid';
        if ($balanceAmount > 0 && $paidAmount > 0) {
            $status = 'partially_paid';
        } elseif ($balanceAmount <= 0) {
            $status = 'paid';
        }
        
        return $this->update($invoiceId, [
            'paid_amount' => $paidAmount,
            'balance_amount' => max(0, $balanceAmount),
            'status' => $status
        ]);
    }
    
    public function updateStatus($invoiceId) {
        $invoice = $this->getById($invoiceId);
        if (!$invoice) return false;
        
        $status = $invoice['status'];
        if ($invoice['status'] !== 'cancelled' && $invoice['status'] !== 'paid') {
            if (strtotime($invoice['due_date']) < time() && $invoice['balance_amount'] > 0) {
                $status = 'overdue';
            }
        }
        
        return $this->update($invoiceId, ['status' => $status]);
    }
    
    public function addItem($invoiceId, $itemData) {
        try {
            $sql = "INSERT INTO `" . $this->db->getPrefix() . "invoice_items` 
                    (invoice_id, product_id, item_description, quantity, unit_price, tax_rate, tax_amount, discount_rate, discount_amount, line_total, account_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            return $this->db->query($sql, [
                $invoiceId,
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
            error_log('Invoice_model addItem error: ' . $e->getMessage());
            return false;
        }
    }
}

