<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_modification_model extends Base_Model {
    protected $table = 'booking_modifications';
    
    public function getByBooking($bookingId) {
        try {
            return $this->db->fetchAll(
                "SELECT bm.*, u.first_name, u.last_name, u.username 
                 FROM `" . $this->db->getPrefix() . $this->table . "` bm
                 LEFT JOIN `" . $this->db->getPrefix() . "users` u ON bm.changed_by = u.id
                 WHERE bm.booking_id = ? 
                 ORDER BY bm.created_at DESC",
                [$bookingId]
            );
        } catch (Exception $e) {
            error_log('Booking_modification_model getByBooking error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function logModification($bookingId, $changeType, $oldValue, $newValue, $reason = null, $changedBy = null) {
        try {
            return $this->db->insert($this->table, [
                'booking_id' => $bookingId,
                'changed_by' => $changedBy,
                'change_type' => $changeType,
                'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
                'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
                'reason' => $reason
            ]);
        } catch (Exception $e) {
            error_log('Booking_modification_model logModification error: ' . $e->getMessage());
            return false;
        }
    }
}

