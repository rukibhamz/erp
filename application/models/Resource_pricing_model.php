<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Resource_pricing_model extends Base_Model {
    protected $table = 'resource_pricing';
    
    public function getByResource($resourceId, $date = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE resource_id = ?";
            $params = [$resourceId];
            
            if ($date) {
                $sql .= " AND (start_date IS NULL OR start_date <= ?)
                         AND (end_date IS NULL OR end_date >= ?)";
                $params[] = $date;
                $params[] = $date;
            }
            
            $sql .= " ORDER BY start_date DESC, day_of_week DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Resource_pricing_model getByResource error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getApplicablePrice($resourceId, $rateType, $date, $dayOfWeek = null) {
        try {
            $dayOfWeek = $dayOfWeek ?? date('w', strtotime($date));
            
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE resource_id = ? AND rate_type = ?
                    AND (start_date IS NULL OR start_date <= ?)
                    AND (end_date IS NULL OR end_date >= ?)
                    AND (day_of_week IS NULL OR day_of_week = ?)
                    ORDER BY day_of_week DESC, start_date DESC
                    LIMIT 1";
            
            return $this->db->fetchOne($sql, [$resourceId, $rateType, $date, $date, $dayOfWeek]);
        } catch (Exception $e) {
            error_log('Resource_pricing_model getApplicablePrice error: ' . $e->getMessage());
            return false;
        }
    }
}

