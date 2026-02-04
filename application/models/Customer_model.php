<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends Base_Model {
    protected $table = 'customers';
    
    public function getNextCustomerCode() {
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(customer_code, 5) AS UNSIGNED)) as max_code 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE customer_code LIKE 'CUST-%'"
        );
        $nextNum = ($result['max_code'] ?? 0) + 1;
        return 'CUST-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }
    
    public function getTotalOutstanding($customerId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(balance_amount) as total FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE customer_id = ? AND status IN ('sent', 'partially_paid', 'overdue', 'draft')",
                [$customerId]
            );
            return $result ? floatval($result['total'] ?? 0) : 0;
        } catch (Exception $e) {
            error_log('Customer_model getTotalOutstanding error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getAgingReport($customerId = null) {
        try {
            $sql = "SELECT 
                        i.customer_id,
                        c.company_name,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) <= 30 THEN i.balance_amount ELSE 0 END) as current_0_30,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) > 30 AND DATEDIFF(CURDATE(), i.due_date) <= 60 THEN i.balance_amount ELSE 0 END) as days_31_60,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) > 60 AND DATEDIFF(CURDATE(), i.due_date) <= 90 THEN i.balance_amount ELSE 0 END) as days_61_90,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) > 90 AND DATEDIFF(CURDATE(), i.due_date) <= 120 THEN i.balance_amount ELSE 0 END) as days_91_120,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) > 120 THEN i.balance_amount ELSE 0 END) as days_120_plus,
                        SUM(i.balance_amount) as total_outstanding
                    FROM `" . $this->db->getPrefix() . "invoices` i
                    JOIN `" . $this->db->getPrefix() . $this->table . "` c ON i.customer_id = c.id
                    WHERE i.status IN ('sent', 'partially_paid', 'overdue')";
            
            $params = [];
            if ($customerId) {
                $sql .= " AND i.customer_id = ?";
                $params[] = $customerId;
            }
            
            $sql .= " GROUP BY i.customer_id, c.company_name";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Customer_model getAgingReport error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get customer by customer_code
     * @param string $code Customer code
     * @return array|false Customer data or false if not found
     */
    public function getByCode($code) {
        try {
            return $this->db->fetchOne(
                "SELECT c.*, ct.name as customer_type_name, ct.discount_percentage 
                 FROM `" . $this->db->getPrefix() . "customers` c
                 LEFT JOIN `" . $this->db->getPrefix() . "customer_types` ct ON c.customer_type_id = ct.id
                 WHERE c.customer_code = ?",
                [$code]
            );
        } catch (Exception $e) {
            error_log('Customer_model getByCode error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get customer by ID with joined type data
     */
    public function getCustomerDetailed($id) {
        try {
            return $this->db->fetchOne(
                "SELECT c.*, ct.name as customer_type_name, ct.discount_percentage 
                 FROM `" . $this->db->getPrefix() . "customers` c
                 LEFT JOIN `" . $this->db->getPrefix() . "customer_types` ct ON c.customer_type_id = ct.id
                 WHERE c.id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Customer_model getCustomerDetailed error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get customer by email address
     * @param string $email Customer email
     * @return array|false Customer data or false if not found
     */
    public function getByEmail($email) {
        try {
            return $this->db->fetchOne(
                "SELECT c.*, ct.name as customer_type_name, ct.discount_percentage 
                 FROM `" . $this->db->getPrefix() . "customers` c
                 LEFT JOIN `" . $this->db->getPrefix() . "customer_types` ct ON c.customer_type_id = ct.id
                 WHERE c.email = ?",
                [$email]
            );
        } catch (Exception $e) {
            error_log('Customer_model getByEmail error: ' . $e->getMessage());
            return false;
        }
    }
}


