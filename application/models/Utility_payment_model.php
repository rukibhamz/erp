<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_payment_model extends Base_Model {
    protected $table = 'utility_payments';
    
    public function getNextPaymentNumber() {
        try {
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT payment_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE payment_number LIKE 'UPAY-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['payment_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "UPAY-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "UPAY-{$year}-00001";
        } catch (Exception $e) {
            error_log('Utility_payment_model getNextPaymentNumber error: ' . $e->getMessage());
            return 'UPAY-' . date('Y') . '-00001';
        }
    }
    
    public function getByBill($billId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE bill_id = ? 
                 ORDER BY payment_date DESC, id DESC",
                [$billId]
            );
        } catch (Exception $e) {
            error_log('Utility_payment_model getByBill error: ' . $e->getMessage());
            return [];
        }
    }
}

