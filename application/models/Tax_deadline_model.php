<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_deadline_model extends Base_Model {
    protected $table = 'tax_deadlines';
    
    public function getUpcoming($days = 30) {
        try {
            $endDate = date('Y-m-d', strtotime("+{$days} days"));
            
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE deadline_date >= CURDATE() 
                 AND deadline_date <= ? 
                 AND status != 'completed'
                 ORDER BY deadline_date ASC",
                [$endDate]
            );
        } catch (Exception $e) {
            error_log('Tax_deadline_model getUpcoming error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getOverdue() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE deadline_date < CURDATE() 
                 AND status != 'completed'
                 ORDER BY deadline_date ASC"
            );
        } catch (Exception $e) {
            error_log('Tax_deadline_model getOverdue error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function createDeadline($taxType, $period, $deadlineDate, $deadlineType = 'filing') {
        try {
            return $this->db->insert($this->table, [
                'tax_type' => $taxType,
                'deadline_date' => $deadlineDate,
                'deadline_type' => $deadlineType,
                'period_covered' => $period,
                'status' => 'upcoming'
            ]);
        } catch (Exception $e) {
            error_log('Tax_deadline_model createDeadline error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateStatus($id, $status) {
        try {
            return $this->db->update($this->table, ['status' => $status], "id = ?", [$id]);
        } catch (Exception $e) {
            error_log('Tax_deadline_model updateStatus error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function markCompleted($id) {
        try {
            return $this->db->update($this->table, [
                'status' => 'completed',
                'completed' => 1,
                'completed_date' => date('Y-m-d')
            ], "id = ?", [$id]);
        } catch (Exception $e) {
            error_log('Tax_deadline_model markCompleted error: ' . $e->getMessage());
            return false;
        }
    }
}

