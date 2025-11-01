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
        $sql = "SELECT ca.*, a.account_code, a.account_name, a.account_type
                FROM `" . $this->db->getPrefix() . $this->table . "` ca
                JOIN `" . $this->db->getPrefix() . "accounts` a ON ca.account_id = a.id
                WHERE ca.id = ?";
        return $this->db->fetchOne($sql, [$cashAccountId]);
    }
    
    public function getActive() {
        return $this->db->fetchAll(
            "SELECT ca.*, a.account_code, a.account_name
             FROM `" . $this->db->getPrefix() . $this->table . "` ca
             JOIN `" . $this->db->getPrefix() . "accounts` a ON ca.account_id = a.id
             WHERE ca.status = 'active' ORDER BY ca.account_name"
        );
    }
}

