<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Item_model extends Base_Model {
    protected $table = 'items';
    
    public function getNextSKU($prefix = 'ITEM') {
        try {
            $year = date('Y');
            $lastSKU = $this->db->fetchOne(
                "SELECT sku FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE sku LIKE '{$prefix}-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastSKU) {
                $parts = explode('-', $lastSKU['sku']);
                $number = intval($parts[2] ?? 0) + 1;
                return "{$prefix}-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "{$prefix}-{$year}-00001";
        } catch (Exception $e) {
            error_log('Item_model getNextSKU error: ' . $e->getMessage());
            return $prefix . '-' . date('Y') . '-00001';
        }
    }
    
    public function getByType($type) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE item_type = ? AND (item_status = 'active' OR status = 'active')
                 ORDER BY item_name",
                [$type]
            );
        } catch (Exception $e) {
            error_log('Item_model getByType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getAllActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE (item_status = 'active' OR status = 'active')
                 ORDER BY item_name"
            );
        } catch (Exception $e) {
            error_log('Item_model getAllActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getLowStock($locationId = null) {
        try {
            $sql = "SELECT i.*, sl.location_id, sl.quantity, sl.reorder_point,
                           (sl.quantity - sl.reserved_qty) as available_qty
                    FROM `" . $this->db->getPrefix() . $this->table . "` i
                    JOIN `" . $this->db->getPrefix() . "stock_levels` sl ON i.id = sl.item_id
                    WHERE i.item_status = 'active' 
                    AND (sl.quantity - sl.reserved_qty) <= sl.reorder_point";
            
            $params = [];
            if ($locationId) {
                $sql .= " AND sl.location_id = ?";
                $params[] = $locationId;
            }
            
            $sql .= " ORDER BY (sl.quantity - sl.reserved_qty) ASC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Item_model getLowStock error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getOutOfStock($locationId = null) {
        try {
            $sql = "SELECT i.*, sl.location_id, sl.quantity
                    FROM `" . $this->db->getPrefix() . $this->table . "` i
                    JOIN `" . $this->db->getPrefix() . "stock_levels` sl ON i.id = sl.item_id
                    WHERE i.item_status = 'active' 
                    AND sl.quantity <= 0";
            
            $params = [];
            if ($locationId) {
                $sql .= " AND sl.location_id = ?";
                $params[] = $locationId;
            }
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Item_model getOutOfStock error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function search($term) {
        try {
            $searchTerm = '%' . $term . '%';
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE (sku LIKE ? OR item_name LIKE ? OR barcode = ?)
                 AND item_status = 'active'
                 ORDER BY item_name
                 LIMIT 50",
                [$searchTerm, $searchTerm, $term]
            );
        } catch (Exception $e) {
            error_log('Item_model search error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalInventoryValue() {
        try {
            $result = $this->db->fetchOne(
                "SELECT SUM(sl.quantity * i.average_cost) as total_value
                 FROM `" . $this->db->getPrefix() . "stock_levels` sl
                 JOIN `" . $this->db->getPrefix() . $this->table . "` i ON sl.item_id = i.id
                 WHERE i.item_type = 'inventory'"
            );
            return floatval($result['total_value'] ?? 0);
        } catch (Exception $e) {
            error_log('Item_model getTotalInventoryValue error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get all active items
     * @param int $limit Optional limit on number of items to return
     * @return array
     */
    public function getAllActive($limit = null) {
        try {
            // Check both item_status and status columns (table has both)
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE item_status = 'active' AND status = 'active'
                    ORDER BY item_name";
            
            if ($limit !== null && is_numeric($limit) && $limit > 0) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Item_model getAllActive error: ' . $e->getMessage());
            // Fallback: try without status column (for older installations)
            try {
                $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                        WHERE item_status = 'active'
                        ORDER BY item_name";
                if ($limit !== null && is_numeric($limit) && $limit > 0) {
                    $sql .= " LIMIT " . intval($limit);
                }
                return $this->db->fetchAll($sql);
            } catch (Exception $e2) {
                error_log('Item_model getAllActive fallback error: ' . $e2->getMessage());
                return [];
            }
        }
    }
}

