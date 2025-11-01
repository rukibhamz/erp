<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Employee_model extends Base_Model {
    protected $table = 'employees';
    
    public function getNextEmployeeCode() {
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(employee_code, 4) AS UNSIGNED)) as max_code 
             FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE employee_code LIKE 'EMP-%'"
        );
        $nextNum = ($result['max_code'] ?? 0) + 1;
        return 'EMP-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
    }
    
    public function getWithUser($employeeId) {
        $sql = "SELECT e.*, u.username, u.email as user_email
                FROM `" . $this->db->getPrefix() . $this->table . "` e
                LEFT JOIN `" . $this->db->getPrefix() . "users` u ON e.user_id = u.id
                WHERE e.id = ?";
        return $this->db->fetchOne($sql, [$employeeId]);
    }
    
    public function getActiveEmployees() {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE status = 'active' ORDER BY first_name, last_name"
        );
    }
}

