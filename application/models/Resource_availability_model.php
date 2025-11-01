<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Resource_availability_model extends Base_Model {
    protected $table = 'resource_availability';
    
    public function getByResource($resourceId) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE resource_id = ? 
                 ORDER BY day_of_week ASC",
                [$resourceId]
            );
        } catch (Exception $e) {
            error_log('Resource_availability_model getByResource error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function setDayAvailability($resourceId, $dayOfWeek, $isAvailable, $startTime = null, $endTime = null, $breakStart = null, $breakEnd = null) {
        try {
            $existing = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE resource_id = ? AND day_of_week = ?",
                [$resourceId, $dayOfWeek]
            );
            
            $data = [
                'resource_id' => $resourceId,
                'day_of_week' => $dayOfWeek,
                'is_available' => $isAvailable ? 1 : 0,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'break_start' => $breakStart,
                'break_end' => $breakEnd
            ];
            
            if ($existing) {
                return $this->update($existing['id'], $data);
            } else {
                return $this->db->insert($this->table, $data);
            }
        } catch (Exception $e) {
            error_log('Resource_availability_model setDayAvailability error: ' . $e->getMessage());
            return false;
        }
    }
}

