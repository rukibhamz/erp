<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_payment_model extends Base_Model {
    protected $table = 'tax_payments';
    
    public function getByTaxType($taxType, $limit = 20) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE tax_type = ? 
             ORDER BY payment_date DESC 
             LIMIT ?",
            [$taxType, $limit]
        );
    }
    
    public function getTotalPaid($taxType, $periodStart = null, $periodEnd = null) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM `" . $this->db->getPrefix() . $this->table . "` 
                WHERE tax_type = ?";
        $params = [$taxType];
        
        if ($periodStart && $periodEnd) {
            $sql .= " AND payment_date >= ? AND payment_date <= ?";
            $params[] = $periodStart;
            $params[] = $periodEnd;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return floatval($result['total'] ?? 0);
    }
}

