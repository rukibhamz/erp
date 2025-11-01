<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Period_lock_model extends Base_Model {
    protected $table = 'period_locks';
    
    public function isLocked($financialYearId, $month, $year) {
        try {
            $result = $this->db->fetchOne(
                "SELECT locked FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE financial_year_id = ? AND period_month = ? AND period_year = ?",
                [$financialYearId, $month, $year]
            );
            
            return $result ? (bool)$result['locked'] : false;
        } catch (Exception $e) {
            error_log('Period_lock_model isLocked error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function lockPeriod($financialYearId, $month, $year, $userId) {
        try {
            // Check if already locked
            $existing = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE financial_year_id = ? AND period_month = ? AND period_year = ?",
                [$financialYearId, $month, $year]
            );
            
            if ($existing) {
                return $this->update($existing['id'], [
                    'locked' => 1,
                    'locked_at' => date('Y-m-d H:i:s'),
                    'locked_by' => $userId
                ]);
            } else {
                return $this->create([
                    'financial_year_id' => $financialYearId,
                    'period_month' => $month,
                    'period_year' => $year,
                    'locked' => 1,
                    'locked_at' => date('Y-m-d H:i:s'),
                    'locked_by' => $userId
                ]);
            }
        } catch (Exception $e) {
            error_log('Period_lock_model lockPeriod error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function unlockPeriod($financialYearId, $month, $year) {
        try {
            $lock = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE financial_year_id = ? AND period_month = ? AND period_year = ?",
                [$financialYearId, $month, $year]
            );
            
            if ($lock) {
                return $this->update($lock['id'], [
                    'locked' => 0,
                    'locked_at' => null,
                    'locked_by' => null
                ]);
            }
            
            return true; // Not locked, so already unlocked
        } catch (Exception $e) {
            error_log('Period_lock_model unlockPeriod error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getLockedPeriods($financialYearId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE financial_year_id = ? AND locked = 1
                 ORDER BY period_year, period_month",
                [$financialYearId]
            );
        } catch (Exception $e) {
            error_log('Period_lock_model getLockedPeriods error: ' . $e->getMessage());
            return [];
        }
    }
}

