<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vendor_model extends Base_Model {
    protected $table = 'vendors';
    
    public function getNextVendorCode() {
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(vendor_code, 6) AS UNSIGNED)) as max_code 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE vendor_code LIKE 'VEND-%'"
        );
        $nextNum = ($result['max_code'] ?? 0) + 1;
        return 'VEND-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }
    
    public function getTotalOutstanding($vendorId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(balance_amount) as total FROM `" . $this->db->getPrefix() . "bills` 
                 WHERE vendor_id = ? AND status IN ('received', 'partially_paid', 'overdue')",
                [$vendorId]
            );
            return $result ? floatval($result['total'] ?? 0) : 0;
        } catch (Exception $e) {
            error_log('Vendor_model getTotalOutstanding error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getAgingReport($vendorId = null) {
        try {
            $sql = "SELECT 
                        b.vendor_id,
                        v.company_name,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), b.due_date) <= 30 THEN b.balance_amount ELSE 0 END) as current_0_30,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), b.due_date) > 30 AND DATEDIFF(CURDATE(), b.due_date) <= 60 THEN b.balance_amount ELSE 0 END) as days_31_60,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), b.due_date) > 60 AND DATEDIFF(CURDATE(), b.due_date) <= 90 THEN b.balance_amount ELSE 0 END) as days_61_90,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), b.due_date) > 90 AND DATEDIFF(CURDATE(), b.due_date) <= 120 THEN b.balance_amount ELSE 0 END) as days_91_120,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), b.due_date) > 120 THEN b.balance_amount ELSE 0 END) as days_120_plus,
                        SUM(b.balance_amount) as total_outstanding
                    FROM `" . $this->db->getPrefix() . "bills` b
                    JOIN `" . $this->db->getPrefix() . $this->table . "` v ON b.vendor_id = v.id
                    WHERE b.status IN ('received', 'partially_paid', 'overdue')";
            
            $params = [];
            if ($vendorId) {
                $sql .= " AND b.vendor_id = ?";
                $params[] = $vendorId;
            }
            
            $sql .= " GROUP BY b.vendor_id, v.company_name";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Vendor_model getAgingReport error: ' . $e->getMessage());
            return [];
        }
    }
}

