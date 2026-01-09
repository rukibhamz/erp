<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Education_tax_model extends Base_Model {
    protected $table = 'education_tax_config';
    
    public function getConfig($year) {
        return $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . "education_tax_config` WHERE tax_year = ?",
            [$year]
        );
    }
    
    public function getAllConfigs() {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . "education_tax_config` ORDER BY tax_year DESC"
        );
    }
    
    public function getSummary($year = null) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . "vw_education_tax_summary`";
        $params = [];
        if ($year) {
            $sql .= " WHERE tax_year = ?";
            $params[] = $year;
        }
        $sql .= " ORDER BY tax_year DESC";
        return $year ? $this->db->fetchOne($sql, $params) : $this->db->fetchAll($sql);
    }
    
    public function getPayments($year = null) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . "education_tax_payments`";
        $params = [];
        if ($year) {
            $sql .= " WHERE tax_year = ?";
            $params[] = $year;
        }
        $sql .= " ORDER BY payment_date DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getReturns($year = null) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . "education_tax_returns`";
        $params = [];
        if ($year) {
            $sql .= " WHERE tax_year = ?";
            $params[] = $year;
        }
        $sql .= " ORDER BY filing_date DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function calculateAssessableProfit($year) {
        // This is a placeholder for real accounting logic
        // Ideally, it should query journal entries or P&L for the year
        // For now, let's return a sum of sales - sum of expenses from journal_entries
        try {
            $startDate = $year . '-01-01';
            $endDate = $year . '-12-31';
            
            $result = $this->db->fetchOne(
                "SELECT 
                    (SELECT IFNULL(SUM(credit - debit), 0) FROM erp_journal_entries WHERE account_id IN (SELECT id FROM erp_accounts WHERE type = 'Revenue') AND date BETWEEN ? AND ?) -
                    (SELECT IFNULL(SUM(debit - credit), 0) FROM erp_journal_entries WHERE account_id IN (SELECT id FROM erp_accounts WHERE type = 'Expense') AND date BETWEEN ? AND ?) 
                as net_profit",
                [$startDate, $endDate, $startDate, $endDate]
            );
            return floatval($result['net_profit'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
}
