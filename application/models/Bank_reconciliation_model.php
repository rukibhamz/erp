<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank_reconciliation_model extends Base_Model {
    protected $table = 'bank_reconciliations';
    
    public function getByAccount($cashAccountId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE cash_account_id = ? 
                 ORDER BY reconciliation_date DESC",
                [$cashAccountId]
            );
        } catch (Exception $e) {
            error_log('Bank_reconciliation_model getByAccount error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getLastReconciliation($cashAccountId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE cash_account_id = ? AND status = 'completed'
                 ORDER BY reconciliation_date DESC LIMIT 1",
                [$cashAccountId]
            );
        } catch (Exception $e) {
            error_log('Bank_reconciliation_model getLastReconciliation error: ' . $e->getMessage());
            return false;
        }
    }
}

