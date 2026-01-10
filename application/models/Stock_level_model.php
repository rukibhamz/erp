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
    
    /**
     * Decrease stock for an item (used by POS sales)
     * 
     * @param int $itemId Item ID
     * @param float $quantity Quantity to decrease
     * @param string $reason Reason for the decrease (e.g., 'POS Sale')
     * @param int|null $referenceId Optional reference ID (e.g., sale ID)
     * @return bool Success status
     */
    public function decreaseStock($itemId, $quantity, $reason = 'Sale', $referenceId = null) {
        try {
            // Get existing stock levels for this item across all locations
            $stocks = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE item_id = ? AND quantity > 0
                 ORDER BY quantity DESC",
                [$itemId]
            );
            
            if (empty($stocks)) {
                // No stock exists - create a negative stock entry at default location (ID 1)
                error_log("decreaseStock: No stock found for item {$itemId}, creating negative stock");
                return $this->updateStock($itemId, 1, -$quantity);
            }
            
            $remainingQty = $quantity;
            
            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;
                
                $availableQty = floatval($stock['quantity']);
                $deductQty = min($availableQty, $remainingQty);
                
                // Update this stock level
                $newQuantity = $availableQty - $deductQty;
                $newAvailable = $newQuantity - floatval($stock['reserved_qty'] ?? 0);
                
                $this->update($stock['id'], [
                    'quantity' => $newQuantity,
                    'available_qty' => $newAvailable,
                    'last_movement_date' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $remainingQty -= $deductQty;
            }
            
            // If there's still remaining quantity to deduct (overselling), log it
            if ($remainingQty > 0) {
                error_log("decreaseStock: Oversold item {$itemId} by {$remainingQty} units (Reason: {$reason})");
                // Deduct from first location with negative balance
                $this->updateStock($itemId, $stocks[0]['location_id'], -$remainingQty);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Stock_level_model decreaseStock error: ' . $e->getMessage());
            return false;
        }
    }
}

