<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_rental_model extends Base_Model {
    protected $table = 'booking_rentals';
    
    /**
     * Get all rental items for a booking (with item details)
     */
    public function getByBooking($bookingId) {
        try {
            return $this->db->fetchAll(
                "SELECT br.*, i.item_name, i.sku, i.rental_rate_type, i.category
                 FROM `" . $this->db->getPrefix() . $this->table . "` br
                 JOIN `" . $this->db->getPrefix() . "items` i ON br.item_id = i.id
                 WHERE br.booking_id = ?
                 ORDER BY i.item_name ASC",
                [$bookingId]
            );
        } catch (Exception $e) {
            error_log('Booking_rental_model getByBooking error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add a rental item to a booking and reserve stock
     */
    public function addRental($bookingId, $itemId, $quantity, $rentalRate) {
        try {
            // Check available stock first
            $available = $this->getAvailableStock($itemId);
            if ($available !== null && $available < $quantity) {
                error_log("Booking_rental_model: Insufficient stock for item {$itemId}. Available: {$available}, Requested: {$quantity}");
                return false;
            }
            
            $rentalTotal = $rentalRate * $quantity;
            
            $rentalId = $this->db->insert($this->table, [
                'booking_id' => $bookingId,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'rental_rate' => $rentalRate,
                'rental_total' => $rentalTotal,
                'status' => 'reserved',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($rentalId) {
                // Reserve stock
                $this->reserveStock($itemId, $quantity);
            }
            
            return $rentalId;
        } catch (Exception $e) {
            error_log('Booking_rental_model addRental error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove a rental item and unreserve stock
     */
    public function removeRental($id) {
        try {
            // Get rental details first to unreserve stock
            $rental = $this->getById($id);
            if (!$rental) return false;
            
            // Unreserve stock (only if still reserved, not checked out)
            if ($rental['status'] === 'reserved') {
                $this->unreserveStock($rental['item_id'], $rental['quantity']);
            }
            
            return $this->delete($id);
        } catch (Exception $e) {
            error_log('Booking_rental_model removeRental error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total rental cost for a booking
     */
    public function getTotalByBooking($bookingId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(rental_total) as total 
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE booking_id = ?",
                [$bookingId]
            );
            return $result ? floatval($result['total']) : 0;
        } catch (Exception $e) {
            error_log('Booking_rental_model getTotalByBooking error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get all rentable items with available stock
     */
    public function getRentableItems() {
        try {
            return $this->db->fetchAll(
                "SELECT i.*, 
                        COALESCE(SUM(sl.available_qty), 0) as available_stock,
                        COALESCE(SUM(sl.reserved_qty), 0) as reserved_stock,
                        COALESCE(SUM(sl.quantity), 0) as total_stock
                 FROM `" . $this->db->getPrefix() . "items` i
                 LEFT JOIN `" . $this->db->getPrefix() . "stock_levels` sl ON i.id = sl.item_id
                 WHERE i.is_rentable = 1 AND i.item_status = 'active'
                 GROUP BY i.id
                 HAVING available_stock > 0
                 ORDER BY i.category, i.item_name"
            );
        } catch (Exception $e) {
            error_log('Booking_rental_model getRentableItems error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Checkout rental items (mark as physically given to customer)
     */
    public function checkoutItems($bookingId) {
        try {
            $rentals = $this->getByBooking($bookingId);
            $checkedOut = 0;
            
            foreach ($rentals as $rental) {
                if ($rental['status'] === 'reserved') {
                    $this->update($rental['id'], [
                        'status' => 'checked_out',
                        'checked_out_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Move from reserved to actually issued
                    // Decrease reserved_qty (it was reserved earlier)
                    $this->unreserveStock($rental['item_id'], $rental['quantity']);
                    // Decrease available_qty (now physically out)
                    $this->decreaseAvailableStock($rental['item_id'], $rental['quantity']);
                    
                    $checkedOut++;
                }
            }
            
            return $checkedOut;
        } catch (Exception $e) {
            error_log('Booking_rental_model checkoutItems error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Return rental items (mark as returned and restore stock)
     */
    public function returnItems($bookingId, $condition = null) {
        try {
            $rentals = $this->getByBooking($bookingId);
            $returned = 0;
            
            foreach ($rentals as $rental) {
                if ($rental['status'] === 'checked_out') {
                    $status = 'returned';
                    // If condition indicates damage, mark as damaged
                    if ($condition && stripos($condition, 'damaged') !== false) {
                        $status = 'damaged';
                    }
                    
                    $this->update($rental['id'], [
                        'status' => $status,
                        'returned_at' => date('Y-m-d H:i:s'),
                        'return_condition' => $condition
                    ]);
                    
                    // Restore available stock (items are back)
                    $this->restoreAvailableStock($rental['item_id'], $rental['quantity']);
                    
                    $returned++;
                }
            }
            
            return $returned;
        } catch (Exception $e) {
            error_log('Booking_rental_model returnItems error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get available stock for an item across all locations
     */
    private function getAvailableStock($itemId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT COALESCE(SUM(available_qty), 0) as available
                 FROM `" . $this->db->getPrefix() . "stock_levels`
                 WHERE item_id = ?",
                [$itemId]
            );
            return $result ? floatval($result['available']) : null;
        } catch (Exception $e) {
            error_log('Booking_rental_model getAvailableStock error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Reserve stock (increase reserved_qty, decrease available_qty)
     */
    private function reserveStock($itemId, $quantity) {
        try {
            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . "stock_levels` 
                 SET reserved_qty = reserved_qty + ?, 
                     available_qty = GREATEST(0, available_qty - ?)
                 WHERE item_id = ?",
                [$quantity, $quantity, $itemId]
            );
        } catch (Exception $e) {
            error_log('Booking_rental_model reserveStock error: ' . $e->getMessage());
        }
    }
    
    /**
     * Unreserve stock (decrease reserved_qty, increase available_qty)
     */
    private function unreserveStock($itemId, $quantity) {
        try {
            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . "stock_levels` 
                 SET reserved_qty = GREATEST(0, reserved_qty - ?), 
                     available_qty = available_qty + ?
                 WHERE item_id = ?",
                [$quantity, $quantity, $itemId]
            );
        } catch (Exception $e) {
            error_log('Booking_rental_model unreserveStock error: ' . $e->getMessage());
        }
    }
    
    /**
     * Decrease available stock (for checkout — items physically leave)
     */
    private function decreaseAvailableStock($itemId, $quantity) {
        try {
            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . "stock_levels` 
                 SET available_qty = GREATEST(0, available_qty - ?)
                 WHERE item_id = ?",
                [$quantity, $itemId]
            );
        } catch (Exception $e) {
            error_log('Booking_rental_model decreaseAvailableStock error: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore available stock (for return — items physically come back)
     */
    private function restoreAvailableStock($itemId, $quantity) {
        try {
            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . "stock_levels` 
                 SET available_qty = available_qty + ?
                 WHERE item_id = ?",
                [$quantity, $itemId]
            );
        } catch (Exception $e) {
            error_log('Booking_rental_model restoreAvailableStock error: ' . $e->getMessage());
        }
    }
}
