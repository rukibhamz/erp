<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_model extends Base_Model {
    protected $table = 'payments';
    
    public function getNextPaymentNumber($type = 'receipt') {
        $prefix = $type === 'receipt' ? 'RCPT' : 'PAY';
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(payment_number, 6) AS UNSIGNED)) as max_num 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE payment_number LIKE '{$prefix}-%'"
        );
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return $prefix . '-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    public function getByBill($billId) {
        try {
            // Get payments allocated to this bill via payment_allocations
            $sql = "SELECT p.*, pa.amount as allocated_amount
                    FROM `" . $this->db->getPrefix() . $this->table . "` p
                    INNER JOIN `" . $this->db->getPrefix() . "payment_allocations` pa ON p.id = pa.payment_id
                    WHERE pa.bill_id = ?
                    ORDER BY p.payment_date DESC, p.id DESC";
            return $this->db->fetchAll($sql, [$billId]);
        } catch (Exception $e) {
            error_log('Payment_model getByBill error: ' . $e->getMessage());
            return [];
        }
    }
}

