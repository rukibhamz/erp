<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pos_session_model extends Base_Model {
    protected $table = 'pos_sessions';
    
    public function getOpenSession($terminalId, $cashierId = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE terminal_id = ? AND status = 'open'";
            $params = [$terminalId];
            
            if ($cashierId) {
                $sql .= " AND cashier_id = ?";
                $params[] = $cashierId;
            }
            
            $sql .= " ORDER BY opening_time DESC LIMIT 1";
            
            return $this->db->fetchOne($sql, $params);
        } catch (Exception $e) {
            error_log('Pos_session_model getOpenSession error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function closeSession($sessionId, $closingData) {
        try {
            $closingData['status'] = 'closed';
            $closingData['closing_time'] = date('Y-m-d H:i:s');
            return $this->db->update($this->table, $closingData, "id = ?", [$sessionId]);
        } catch (Exception $e) {
            error_log('Pos_session_model closeSession error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateSessionTotals($sessionId) {
        try {
            // Calculate totals from sales
            $totals = $this->db->fetchOne(
                "SELECT 
                    COALESCE(SUM(total_amount), 0) as total_sales,
                    COALESCE(SUM(CASE WHEN payment_method = 'cash' OR payment_method = 'mixed' THEN amount_paid ELSE 0 END), 0) as total_cash,
                    COALESCE(SUM(CASE WHEN payment_method = 'card' THEN amount_paid ELSE 0 END), 0) as total_card
                 FROM `" . $this->db->getPrefix() . "pos_sales`
                 WHERE terminal_id = (SELECT terminal_id FROM `" . $this->db->getPrefix() . $this->table . "` WHERE id = ?)
                 AND DATE(sale_date) = CURDATE()
                 AND status = 'completed'",
                [$sessionId]
            );
            
            $this->db->update($this->table, [
                'total_sales' => $totals['total_sales'] ?? 0,
                'total_cash' => $totals['total_cash'] ?? 0,
                'total_card' => $totals['total_card'] ?? 0
            ], "id = ?", [$sessionId]);
            
            return true;
        } catch (Exception $e) {
            error_log('Pos_session_model updateSessionTotals error: ' . $e->getMessage());
            return false;
        }
    }
}


