<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Email_queue_model extends Base_Model {
    protected $table = 'email_queue';
    
    /**
     * Get pending emails
     */
    public function getPending($limit = 50) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'pending' 
                 ORDER BY priority DESC, created_at ASC 
                 LIMIT ?",
                [$limit]
            );
        } catch (Exception $e) {
            error_log('Email_queue_model getPending error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark email as sent
     */
    public function markSent($id) {
        try {
            return $this->update($id, [
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'attempts' => ($this->getById($id)['attempts'] ?? 0) + 1,
                'last_attempt_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Email_queue_model markSent error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark email as failed
     */
    public function markFailed($id, $errorMessage = null) {
        try {
            $email = $this->getById($id);
            $attempts = intval($email['attempts'] ?? 0) + 1;
            
            return $this->update($id, [
                'status' => $attempts >= 3 ? 'failed' : 'pending',
                'attempts' => $attempts,
                'last_attempt_at' => date('Y-m-d H:i:s'),
                'error_message' => $errorMessage
            ]);
        } catch (Exception $e) {
            error_log('Email_queue_model markFailed error: ' . $e->getMessage());
            return false;
        }
    }
}

