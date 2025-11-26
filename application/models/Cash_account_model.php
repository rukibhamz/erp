<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cash_account_model extends Base_Model {
    protected $table = 'cash_accounts';
    
    public function updateBalance($cashAccountId, $amount, $type = 'deposit') {
        $account = $this->getById($cashAccountId);
        if (!$account) return false;
        
        $currentBalance = floatval($account['current_balance']);
        
        if ($type === 'deposit') {
            $newBalance = $currentBalance + $amount;
        } else {
            $newBalance = $currentBalance - $amount;
        }
        
        return $this->update($cashAccountId, ['current_balance' => $newBalance]);
    }
    
    public function getWithAccount($cashAccountId) {
        $sql = "SELECT ca.id, ca.account_id, ca.account_name, ca.account_type,
                       ca.bank_name, ca.account_number, ca.routing_number, ca.swift_code,
                       ca.opening_balance, ca.current_balance, ca.currency, ca.status,
                       ca.created_at, ca.updated_at,
                       a.account_code, a.account_name as linked_account_name, a.account_type as linked_account_type
                FROM `" . $this->db->getPrefix() . $this->table . "` ca
                JOIN `" . $this->db->getPrefix() . "accounts` a ON ca.account_id = a.id
                WHERE ca.id = ?";
        return $this->db->fetchOne($sql, [$cashAccountId]);
    }
    
    public function getActive() {
        return $this->db->fetchAll(
            "SELECT ca.id, ca.account_id, ca.account_name, ca.account_type, 
                    ca.bank_name, ca.account_number, ca.routing_number, ca.swift_code,
                    ca.opening_balance, ca.current_balance, ca.currency, ca.status,
                    ca.created_at, ca.updated_at,
                    a.account_code, a.account_name as linked_account_name
             FROM `" . $this->db->getPrefix() . $this->table . "` ca
             JOIN `" . $this->db->getPrefix() . "accounts` a ON ca.account_id = a.id
             WHERE ca.status = 'active' ORDER BY ca.account_name"
        );
    }
}

