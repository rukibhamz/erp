<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_resource_model extends Base_Model {
    protected $table = 'booking_resources';
    
    public function getByBooking($bookingId) {
        try {
            return $this->db->fetchAll(
                "SELECT br.*, f.facility_name, f.facility_code 
                 FROM `" . $this->db->getPrefix() . $this->table . "` br
                 JOIN `" . $this->db->getPrefix() . "facilities` f ON br.resource_id = f.id
                 WHERE br.booking_id = ?
                 ORDER BY br.start_time ASC",
                [$bookingId]
            );
        } catch (Exception $e) {
            error_log('Booking_resource_model getByBooking error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function addResource($bookingId, $resourceId, $startDateTime, $endDateTime, $quantity, $rate, $rateType) {
        try {
            // Calculate amount
            $start = new DateTime($startDateTime);
            $end = new DateTime($endDateTime);
            $duration = $end->diff($start);
            $hours = $duration->h + ($duration->i / 60);
            
            $amount = $rate * $hours * $quantity;
            
            return $this->db->insert($this->table, [
                'booking_id' => $bookingId,
                'resource_id' => $resourceId,
                'start_time' => date('Y-m-d H:i:s', strtotime($startDateTime)),
                'end_time' => date('Y-m-d H:i:s', strtotime($endDateTime)),
                'quantity' => $quantity,
                'rate' => $rate,
                'rate_type' => $rateType,
                'amount' => $amount
            ]);
        } catch (Exception $e) {
            error_log('Booking_resource_model addResource error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getTotalByBooking($bookingId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(amount) as total 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_id = ?",
                [$bookingId]
            );
            return $result ? floatval($result['total']) : 0;
        } catch (Exception $e) {
            error_log('Booking_resource_model getTotalByBooking error: ' . $e->getMessage());
            return 0;
        }
    }
}

