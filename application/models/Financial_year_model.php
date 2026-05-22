<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Financial_year_model extends Base_Model {
    protected $table = 'financial_years';
    
    public function getCurrent() {
        try {
            $today = date('Y-m-d');
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE start_date <= ? AND end_date >= ? AND status = 'open'
                 ORDER BY start_date DESC LIMIT 1",
                [$today, $today]
            );
        } catch (Exception $e) {
            error_log('Financial_year_model getCurrent error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getOpen() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'open'
                 ORDER BY start_date DESC"
            );
        } catch (Exception $e) {
            error_log('Financial_year_model getOpen error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function close($financialYearId, $userId) {
        try {
            // Calculate retained earnings
            $retainedEarnings = $this->calculateRetainedEarnings($financialYearId);
            
            $this->db->beginTransaction();
            
            // Update financial year status
            $this->update($financialYearId, [
                'status' => 'closed',
                'closed_at' => date('Y-m-d H:i:s'),
                'closed_by' => $userId
            ]);
            
            // Create opening balances for next year (if applicable)
            // This would typically be handled by the year-end closing process
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Financial_year_model close error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function calculateRetainedEarnings($financialYearId) {
        try {
            $year = $this->getById($financialYearId);
            if (!$year) {
                return 0;
            }

            require_once BASEPATH . 'services/Financial_reporting_service.php';
            $reporting = new Financial_reporting_service();

            return $reporting->calculateNetIncomeForPeriod(
                $year['start_date'],
                $year['end_date']
            );
        } catch (Exception $e) {
            error_log('Financial_year_model calculateRetainedEarnings error: ' . $e->getMessage());
            return 0;
        }
    }
}

