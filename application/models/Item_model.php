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
            // Try with both status columns first
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE item_type = ? 
                    AND (item_status = 'active' OR status = 'active' OR item_status IS NULL OR status IS NULL)
                    ORDER BY item_name";
            $items = $this->db->fetchAll($sql, [$type]);
            
            // If no results, try with just item_status
            if (empty($items)) {
                $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                        WHERE item_type = ? 
                        AND (item_status = 'active' OR item_status IS NULL)
                        ORDER BY item_name";
                $items = $this->db->fetchAll($sql, [$type]);
            }
            
            // If still no results, try with just status
            if (empty($items)) {
                $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                        WHERE item_type = ? 
                        AND (status = 'active' OR status IS NULL)
                        ORDER BY item_name";
                $items = $this->db->fetchAll($sql, [$type]);
            }
            
            // Last resort: get all items of this type regardless of status
            if (empty($items)) {
                $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                        WHERE item_type = ? 
                        ORDER BY item_name";
                $items = $this->db->fetchAll($sql, [$type]);
            }
            
            error_log('Item_model getByType(' . $type . ') returned ' . count($items) . ' items');
            return $items;
        } catch (Exception $e) {
            error_log('Item_model getByType error: ' . $e->getMessage());
            error_log('Item_model getByType stack trace: ' . $e->getTraceAsString());
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
            // Try with both status columns first
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE (item_status = 'active' OR status = 'active' OR item_status IS NULL OR status IS NULL)
                    ORDER BY item_name";
            
            if ($limit !== null && is_numeric($limit) && $limit > 0) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            $items = $this->db->fetchAll($sql);
            
            // If no results, try with just item_status
            if (empty($items)) {
                $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                        WHERE (item_status = 'active' OR item_status IS NULL)
                        ORDER BY item_name";
                if ($limit !== null && is_numeric($limit) && $limit > 0) {
                    $sql .= " LIMIT " . intval($limit);
                }
                $items = $this->db->fetchAll($sql);
            }
            
            // If still no results, try with just status
            if (empty($items)) {
                $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                        WHERE (status = 'active' OR status IS NULL)
                        ORDER BY item_name";
                if ($limit !== null && is_numeric($limit) && $limit > 0) {
                    $sql .= " LIMIT " . intval($limit);
                }
                $items = $this->db->fetchAll($sql);
            }
            
            // Last resort: get all items regardless of status
            if (empty($items)) {
                $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                        ORDER BY item_name";
                if ($limit !== null && is_numeric($limit) && $limit > 0) {
                    $sql .= " LIMIT " . intval($limit);
                }
                $items = $this->db->fetchAll($sql);
            }
            
            error_log('Item_model getAllActive returned ' . count($items) . ' items');
            return $items;
        } catch (Exception $e) {
            error_log('Item_model getAllActive error: ' . $e->getMessage());
            error_log('Item_model getAllActive stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Get inventory items - unified method for all inventory-related operations
     * @return array
     */
    public function getInventoryItems() {
        try {
            // First try getByType
            $items = $this->getByType('inventory');
            
            // If empty, try getAllActive and filter
            if (empty($items)) {
                $allItems = $this->getAllActive();
                $items = array_filter($allItems, function($item) {
                    $itemType = strtolower($item['item_type'] ?? '');
                    return $itemType === 'inventory' || $itemType === '';
                });
                $items = array_values($items); // Re-index array
            }
            
            // If still empty, get all items and filter
            if (empty($items)) {
                try {
                    $allItems = $this->db->fetchAll("SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` ORDER BY item_name");
                    $items = array_filter($allItems, function($item) {
                        $itemType = strtolower($item['item_type'] ?? '');
                        return $itemType === 'inventory' || $itemType === '';
                    });
                    $items = array_values($items);
                } catch (Exception $e) {
                    error_log('Item_model getInventoryItems fallback error: ' . $e->getMessage());
                }
            }
            
            error_log('Item_model getInventoryItems returned ' . count($items) . ' items');
            return $items;
        } catch (Exception $e) {
            error_log('Item_model getInventoryItems error: ' . $e->getMessage());
            return [];
        }
    }
}

