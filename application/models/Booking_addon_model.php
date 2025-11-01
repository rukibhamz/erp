<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_addon_model extends Base_Model {
    protected $table = 'booking_addons';
    
    public function getByBooking($bookingId) {
        try {
            return $this->db->fetchAll(
                "SELECT ba.*, a.name, a.description, a.addon_type 
                 FROM `" . $this->db->getPrefix() . $this->table . "` ba
                 JOIN `" . $this->db->getPrefix() . "addons` a ON ba.addon_id = a.id
                 WHERE ba.booking_id = ?
                 ORDER BY a.display_order ASC, a.name ASC",
                [$bookingId]
            );
        } catch (Exception $e) {
            error_log('Booking_addon_model getByBooking error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function addAddon($bookingId, $addonId, $quantity, $unitPrice) {
        try {
            return $this->db->insert($this->table, [
                'booking_id' => $bookingId,
                'addon_id' => $addonId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity
            ]);
        } catch (Exception $e) {
            error_log('Booking_addon_model addAddon error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function removeAddon($id) {
        try {
            return $this->delete($id);
        } catch (Exception $e) {
            error_log('Booking_addon_model removeAddon error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getTotalByBooking($bookingId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(total_price) as total 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_id = ?",
                [$bookingId]
            );
            return $result ? floatval($result['total']) : 0;
        } catch (Exception $e) {
            error_log('Booking_addon_model getTotalByBooking error: ' . $e->getMessage());
            return 0;
        }
    }
}

