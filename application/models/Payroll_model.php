<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll_model extends Base_Model {
    protected $table = 'payroll';
    
    public function getNextPayrollNumber() {
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(payroll_number, 7) AS UNSIGNED)) as max_num 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE payroll_number LIKE 'PAYROLL-%'"
        );
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return 'PAYROLL-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
    
    public function getWithEmployee($payrollId) {
        $sql = "SELECT p.*, e.first_name, e.last_name, e.employee_code, e.department, e.position
                FROM `" . $this->db->getPrefix() . $this->table . "` p
                JOIN `" . $this->db->getPrefix() . "employees` e ON p.employee_id = e.id
                WHERE p.id = ?";
        return $this->db->fetchOne($sql, [$payrollId]);
    }
    
    public function getItems($payrollId) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . "payroll_items` WHERE payroll_id = ? ORDER BY item_type, id",
            [$payrollId]
        );
    }
    
    public function getByPeriod($startDate, $endDate) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE pay_period_start >= ? AND pay_period_end <= ? 
             ORDER BY payment_date",
            [$startDate, $endDate]
        );
    }
}

