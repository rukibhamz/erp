<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meter_alert_model extends Base_Model {
    protected $table = 'meter_alerts';
    
    public function getUnresolved() {
        try {
            return $this->db->fetchAll(
                "SELECT a.*, m.meter_number, ut.name as utility_type_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` a
                 JOIN `" . $this->db->getPrefix() . "meters` m ON a.meter_id = m.id
                 JOIN `" . $this->db->getPrefix() . "utility_types` ut ON m.utility_type_id = ut.id
                 WHERE a.is_resolved = 0
                 ORDER BY a.severity DESC, a.alert_date DESC"
            );
        } catch (Exception $e) {
            error_log('Meter_alert_model getUnresolved error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function createAlert($meterId, $alertType, $description, $severity = 'medium') {
        try {
            return $this->create([
                'meter_id' => $meterId,
                'alert_type' => $alertType,
                'alert_date' => date('Y-m-d H:i:s'),
                'description' => $description,
                'severity' => $severity,
                'is_resolved' => 0
            ]);
        } catch (Exception $e) {
            error_log('Meter_alert_model createAlert error: ' . $e->getMessage());
            return false;
        }
    }
}

