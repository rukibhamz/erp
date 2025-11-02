<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_level_model extends Base_Model {
    protected $table = 'stock_levels';
    
    public function getByItem($itemId) {
        try {
            return $this->db->fetchAll(
                "SELECT sl.*, l.location_name, l.location_code
                 FROM `" . $this->db->getPrefix() . $this->table . "` sl
                 JOIN `" . $this->db->getPrefix() . "locations` l ON sl.location_id = l.id
                 WHERE sl.item_id = ? AND l.is_active = 1
                 ORDER BY l.location_name",
                [$itemId]
            );
        } catch (Exception $e) {
            error_log('Stock_level_model getByItem error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByLocation($locationId) {
        try {
            return $this->db->fetchAll(
                "SELECT sl.*, i.item_name, i.sku, i.barcode, i.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` sl
                 JOIN `" . $this->db->getPrefix() . "items` i ON sl.item_id = i.id
                 WHERE sl.location_id = ? AND i.item_status = 'active'
                 ORDER BY i.item_name",
                [$locationId]
            );
        } catch (Exception $e) {
            error_log('Stock_level_model getByLocation error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getItemStock($itemId, $locationId = null) {
        try {
            if ($locationId) {
                $result = $this->db->fetchOne(
                    "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                     WHERE item_id = ? AND location_id = ?",
                    [$itemId, $locationId]
                );
                return $result ?: null;
            } else {
                $result = $this->db->fetchOne(
                    "SELECT SUM(quantity) as total_qty, SUM(reserved_qty) as total_reserved, 
                            SUM(available_qty) as total_available
                     FROM `" . $this->db->getPrefix() . $this->table . "` 
                     WHERE item_id = ?",
                    [$itemId]
                );
                return $result;
            }
        } catch (Exception $e) {
            error_log('Stock_level_model getItemStock error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function updateStock($itemId, $locationId, $quantityChange, $reservedChange = 0) {
        try {
            // Get or create stock level record
            $stock = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE item_id = ? AND location_id = ?",
                [$itemId, $locationId]
            );
            
            if ($stock) {
                $newQuantity = floatval($stock['quantity']) + floatval($quantityChange);
                $newReserved = floatval($stock['reserved_qty']) + floatval($reservedChange);
                $newAvailable = $newQuantity - $newReserved;
                
                return $this->update($stock['id'], [
                    'quantity' => $newQuantity,
                    'reserved_qty' => $newReserved,
                    'available_qty' => $newAvailable,
                    'last_movement_date' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Create new stock level record
                return $this->create([
                    'item_id' => $itemId,
                    'location_id' => $locationId,
                    'quantity' => floatval($quantityChange),
                    'reserved_qty' => floatval($reservedChange),
                    'available_qty' => floatval($quantityChange) - floatval($reservedChange),
                    'last_movement_date' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Exception $e) {
            error_log('Stock_level_model updateStock error: ' . $e->getMessage());
            return false;
        }
    }
}

