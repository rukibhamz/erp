<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Resource_blockout_model extends Base_Model {
    protected $table = 'resource_blockouts';
    
    public function getByResource($resourceId, $startDate = null, $endDate = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE resource_id = ?";
            $params = [$resourceId];
            
            if ($startDate) {
                $sql .= " AND end_date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND start_date <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY start_date ASC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Resource_blockout_model getByResource error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function isBlocked($resourceId, $date, $startTime = null, $endTime = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE resource_id = ?
                    AND start_date <= ? AND end_date >= ?";
            $params = [$resourceId, $date, $date];
            
            if ($startTime && $endTime) {
                $sql .= " AND (
                    (start_time IS NULL OR start_time <= ?)
                    AND (end_time IS NULL OR end_time >= ?)
                )";
                $params[] = $endTime;
                $params[] = $startTime;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return $result && intval($result['count']) > 0;
        } catch (Exception $e) {
            error_log('Resource_blockout_model isBlocked error: ' . $e->getMessage());
            return false;
        }
    }
}

