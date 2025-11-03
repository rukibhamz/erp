<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pos_terminal_model extends Base_Model {
    protected $table = 'pos_terminals';
    
    public function getActiveTerminals() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'active'
                 ORDER BY name ASC"
            );
        } catch (Exception $e) {
            error_log('Pos_terminal_model getActiveTerminals error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getNextTerminalCode() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(terminal_code, 5) AS UNSIGNED)) as max_num 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE terminal_code LIKE 'TERM-%'"
            );
            $nextNum = ($result['max_num'] ?? 0) + 1;
            return 'TERM-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Pos_terminal_model getNextTerminalCode error: ' . $e->getMessage());
            return 'TERM-0001';
        }
    }
}



