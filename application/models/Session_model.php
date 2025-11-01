<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Session_model extends Base_Model {
    protected $table = 'sessions';
    protected $primaryKey = 'id';
    
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    public function getById($sessionId) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE id = ?";
        return $this->db->fetchOne($sql, [$sessionId]);
    }
    
    public function updateLastActivity($sessionId, $timestamp) {
        return $this->db->update($this->table, ['last_activity' => $timestamp], "id = ?", [$sessionId]);
    }
    
    public function deleteExpired($lifetime) {
        $expired = time() - $lifetime;
        return $this->db->delete($this->table, "last_activity < ?", [$expired]);
    }
    
    public function getUserSessions($userId) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE user_id = ? ORDER BY last_activity DESC";
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    public function destroySession($sessionId) {
        return $this->db->delete($this->table, "id = ?", [$sessionId]);
    }
    
    public function destroyUserSessions($userId, $exceptSessionId = null) {
        if ($exceptSessionId) {
            $sql = "DELETE FROM `" . $this->db->getPrefix() . $this->table . "` WHERE user_id = ? AND id != ?";
            return $this->db->query($sql, [$userId, $exceptSessionId])->rowCount();
        }
        return $this->db->delete($this->table, "user_id = ?", [$userId]);
    }
}

