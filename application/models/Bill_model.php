<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bill_model extends Base_Model {
    protected $table = 'bills';
    
    public function getNextBillNumber() {
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(bill_number, 5) AS UNSIGNED)) as max_num 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE bill_number LIKE 'BILL-%'"
        );
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return 'BILL-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    public function getWithVendor($billId) {
        $sql = "SELECT b.*, v.company_name, v.email, v.phone, v.address, v.city, v.state, v.zip_code, v.country
                FROM `" . $this->db->getPrefix() . $this->table . "` b
                JOIN `" . $this->db->getPrefix() . "vendors` v ON b.vendor_id = v.id
                WHERE b.id = ?";
        return $this->db->fetchOne($sql, [$billId]);
    }
    
    public function getItems($billId) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . "bill_items` WHERE bill_id = ? ORDER BY id",
            [$billId]
        );
    }
    
    public function addPayment($billId, $amount) {
        $bill = $this->getById($billId);
        if (!$bill) return false;
        
        $paidAmount = floatval($bill['paid_amount']) + floatval($amount);
        $balanceAmount = floatval($bill['total_amount']) - $paidAmount;
        
        $status = 'paid';
        if ($balanceAmount > 0 && $paidAmount > 0) {
            $status = 'partially_paid';
        } elseif ($balanceAmount <= 0) {
            $status = 'paid';
        }
        
        return $this->update($billId, [
            'paid_amount' => $paidAmount,
            'balance_amount' => max(0, $balanceAmount),
            'status' => $status
        ]);
    }
    
    public function updateStatus($billId) {
        $bill = $this->getById($billId);
        if (!$bill) return false;
        
        $status = $bill['status'];
        if ($bill['status'] !== 'cancelled' && $bill['status'] !== 'paid') {
            if (strtotime($bill['due_date']) < time() && $bill['balance_amount'] > 0) {
                $status = 'overdue';
            }
        }
        
        return $this->update($billId, ['status' => $status]);
    }
    
    public function addItem($billId, $itemData) {
        try {
            $sql = "INSERT INTO `" . $this->db->getPrefix() . "bill_items` 
                    (bill_id, product_id, item_description, quantity, unit_price, tax_rate, tax_amount, discount_rate, discount_amount, line_total, account_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            return $this->db->query($sql, [
                $billId,
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
            error_log('Bill_model addItem error: ' . $e->getMessage());
            return false;
        }
    }
}

