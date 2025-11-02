<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meter_reading_model extends Base_Model {
    protected $table = 'meter_readings';
    
    public function create($data) {
        try {
            // Get previous reading if not provided
            if (!isset($data['previous_reading']) || $data['previous_reading'] === null) {
                $lastReading = $this->db->fetchOne(
                    "SELECT reading_value FROM `" . $this->db->getPrefix() . $this->table . "` 
                     WHERE meter_id = ? 
                     ORDER BY reading_date DESC, id DESC 
                     LIMIT 1",
                    [$data['meter_id']]
                );
                $data['previous_reading'] = $lastReading ? $lastReading['reading_value'] : 0;
            }
            
            // Calculate consumption
            $currentReading = floatval($data['reading_value']);
            $previousReading = floatval($data['previous_reading']);
            $data['consumption'] = max(0, $currentReading - $previousReading);
            
            // Handle meter rollover (assume max value of 999999)
            if ($currentReading < $previousReading && $previousReading < 999999) {
                // Possible rollover
                $data['consumption'] = $currentReading + (999999 - $previousReading);
            }
            
            $readingId = parent::create($data);
            
            // Update meter's last reading
            if ($readingId) {
                require_once BASEPATH . 'models/Meter_model.php';
                $meterModel = new Meter_model($this->db);
                $meterModel->updateLastReading($data['meter_id'], $currentReading, $data['reading_date']);
            }
            
            return $readingId;
        } catch (Exception $e) {
            error_log('Meter_reading_model create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByMeter($meterId, $startDate = null, $endDate = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE meter_id = ?";
            $params = [$meterId];
            
            if ($startDate) {
                $sql .= " AND reading_date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND reading_date <= ?";
                $params[] = $endDate;
            }
            
            $sql .= " ORDER BY reading_date DESC, id DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Meter_reading_model getByMeter error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getLatestReading($meterId) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE meter_id = ? 
                 ORDER BY reading_date DESC, id DESC 
                 LIMIT 1",
                [$meterId]
            );
        } catch (Exception $e) {
            error_log('Meter_reading_model getLatestReading error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getConsumptionByPeriod($meterId, $startDate, $endDate) {
        try {
            $readings = $this->getByMeter($meterId, $startDate, $endDate);
            return array_sum(array_column($readings, 'consumption'));
        } catch (Exception $e) {
            error_log('Meter_reading_model getConsumptionByPeriod error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        try {
            $orderBy = $orderBy ?: 'reading_date DESC, id DESC';
            return parent::getAll($limit, $offset, $orderBy);
        } catch (Exception $e) {
            error_log('Meter_reading_model getAll error: ' . $e->getMessage());
            return [];
        }
    }
}

