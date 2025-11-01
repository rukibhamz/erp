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
}

