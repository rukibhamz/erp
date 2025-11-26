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
    
    public function getByPeriod($period) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "payroll_runs` 
                 WHERE period = ? 
                 ORDER BY processed_date DESC",
                [$period]
            );
        } catch (Exception $e) {
            error_log('Payroll_model getByPeriod error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function createRun($data) {
        try {
            return $this->db->insert('payroll_runs', $data);
        } catch (Exception $e) {
            error_log('Payroll_model createRun error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getRunById($runId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "payroll_runs` WHERE id = ?",
                [$runId]
            );
        } catch (Exception $e) {
            error_log('Payroll_model getRunById error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateRun($runId, $data) {
        try {
            return $this->db->update('payroll_runs', $data, "id = ?", [$runId]);
        } catch (Exception $e) {
            error_log('Payroll_model updateRun error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function createPayslip($data) {
        try {
            return $this->db->insert('payslips', $data);
        } catch (Exception $e) {
            error_log('Payroll_model createPayslip error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getPayslips($payrollRunId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "payslips` 
                 WHERE payroll_run_id = ? 
                 ORDER BY employee_id",
                [$payrollRunId]
            );
        } catch (Exception $e) {
            error_log('Payroll_model getPayslips error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByEmployee($employeeId) {
        try {
            return $this->db->fetchAll(
                "SELECT ps.*, pr.period, pr.status as run_status, pr.processed_date
                 FROM `" . $this->db->getPrefix() . "payslips` ps
                 JOIN `" . $this->db->getPrefix() . "payroll_runs` pr ON ps.payroll_run_id = pr.id
                 WHERE ps.employee_id = ?
                 ORDER BY pr.processed_date DESC, pr.period DESC
                 LIMIT 50",
                [$employeeId]
            );
        } catch (Exception $e) {
            error_log('Payroll_model getByEmployee error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getPayslipById($payslipId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "payslips` WHERE id = ?",
                [$payslipId]
            );
        } catch (Exception $e) {
            error_log('Payroll_model getPayslipById error: ' . $e->getMessage());
            return false;
        }
    }
}

