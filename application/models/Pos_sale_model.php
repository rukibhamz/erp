<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pos_sale_model extends Base_Model {
    protected $table = 'pos_sales';
    
    public function getNextSaleNumber() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(sale_number, 5) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE sale_number LIKE 'POS-%' AND DATE(created_at) = CURDATE()"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            $date = date('Ymd');
            return 'POS-' . $date . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Pos_sale_model getNextSaleNumber error: ' . $e->getMessage());
            return 'POS-' . date('Ymd') . '-00001';
        }
    }
    
    public function createSale($saleData, $items, $payments = []) {
        try {
            $this->db->beginTransaction();
            
            // Create sale
            $saleId = $this->create($saleData);
            
            if (!$saleId) {
                // Check if there's a specific DB error we can log
                if (method_exists($this->db, 'error')) {
                    $error = $this->db->error();
                    error_log('Pos_sale_model createSale DB Error: ' . print_r($error, true));
                    file_put_contents(__DIR__ . '/../../pos_debug.log', date('Y-m-d H:i:s') . " - DBINSERT ERROR: " . print_r($error, true) . "\n", FILE_APPEND);
                }
                throw new Exception('Failed to create sale (Insert returned false)');
            }
            
            // Create sale items
            foreach ($items as $item) {
                $item['sale_id'] = $saleId;
                $this->db->insert('pos_sale_items', $item);
            }
            
            // Create payments (for mixed payment methods)
            if (!empty($payments)) {
                foreach ($payments as $payment) {
                    $payment['sale_id'] = $saleId;
                    $this->db->insert('pos_payments', $payment);
                }
            }
            
            $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->db->rollBack();
            $errorMessage = $e->getMessage();
            error_log('Pos_sale_model createSale error: ' . $errorMessage);
            file_put_contents(__DIR__ . '/../../pos_debug.log', date('Y-m-d H:i:s') . " - Pos_sale_model EXCEPTION: " . $errorMessage . "\n", FILE_APPEND);
            return false;
        }
    }
    
    public function getSaleWithItems($saleId) {
        try {
            $sale = $this->getById($saleId);
            if (!$sale) return null;
            
            $sale['items'] = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "pos_sale_items` 
                 WHERE sale_id = ?
                 ORDER BY id ASC",
                [$saleId]
            );
            
            $sale['payments'] = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "pos_payments` 
                 WHERE sale_id = ?
                 ORDER BY id ASC",
                [$saleId]
            );
            
            return $sale;
        } catch (Exception $e) {
            error_log('Pos_sale_model getSaleWithItems error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function getByDateRange($startDate, $endDate, $terminalId = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE sale_date >= ? AND sale_date <= ?";
            $params = [$startDate, $endDate];
            
            if ($terminalId) {
                $sql .= " AND terminal_id = ?";
                $params[] = $terminalId;
            }
            
            $sql .= " ORDER BY sale_date DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Pos_sale_model getByDateRange error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getSalesSummary($terminalId = null, $startDate = null, $endDate = null) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_sales,
                        SUM(total_amount) as total_revenue,
                        SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as cash_sales,
                        SUM(CASE WHEN payment_method = 'card' THEN total_amount ELSE 0 END) as card_sales,
                        SUM(discount_amount) as total_discounts,
                        SUM(tax_amount) as total_taxes
                    FROM `" . $this->db->getPrefix() . $this->table . "`
                    WHERE status = 'completed'";
            $params = [];
            
            if ($terminalId) {
                $sql .= " AND terminal_id = ?";
                $params[] = $terminalId;
            }
            
            if ($startDate) {
                $sql .= " AND DATE(sale_date) >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND DATE(sale_date) <= ?";
                $params[] = $endDate;
            }
            
            return $this->db->fetchOne($sql, $params);
        } catch (Exception $e) {
            error_log('Pos_sale_model getSalesSummary error: ' . $e->getMessage());
            return [
                'total_sales' => 0,
                'total_revenue' => 0,
                'cash_sales' => 0,
                'card_sales' => 0,
                'total_discounts' => 0,
                'total_taxes' => 0
            ];
        }
    }
}



