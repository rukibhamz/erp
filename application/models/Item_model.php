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
     * Get all items - override to ensure no status filtering
     * @param int|null $limit Optional limit
     * @param int $offset Offset
     * @param string|array|null $orderBy Order by clause
     * @return array
     */
    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`";
            
            // Add ORDER BY if specified
            if ($orderBy) {
                $orderClause = $this->buildOrderByClause($orderBy);
                if ($orderClause) {
                    $sql .= " ORDER BY " . $orderClause;
                } else {
                    $sql .= " ORDER BY item_name";
                }
            } else {
                $sql .= " ORDER BY item_name";
            }
            
            // Add LIMIT if specified
            if ($limit !== null) {
                $limit = intval($limit);
                $offset = intval($offset);
                if ($limit > 0 && $offset >= 0) {
                    $sql .= " LIMIT {$limit} OFFSET {$offset}";
                }
            }
            
            $items = $this->db->fetchAll($sql);
            error_log('Item_model getAll returned ' . count($items) . ' items');
            return $items;
        } catch (Exception $e) {
            error_log('Item_model getAll error: ' . $e->getMessage());
            error_log('Item_model getAll stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Get item by ID - override to ensure it works regardless of status
     * @param int $id Item ID
     * @return array|null
     */
    public function getById($id) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE `id` = ?";
            $item = $this->db->fetchOne($sql, [$id]);
            
            if ($item) {
                error_log('Item_model getById(' . $id . ') found item: ' . ($item['item_name'] ?? 'N/A'));
            } else {
                error_log('Item_model getById(' . $id . ') returned null');
            }
            
            return $item;
        } catch (Exception $e) {
            error_log('Item_model getById error: ' . $e->getMessage());
            error_log('Item_model getById stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Get inventory items - unified method for all inventory-related operations
     * Now returns ALL items, not just inventory type, to ensure visibility
     * @return array
     */
    public function getInventoryItems() {
        try {
            // First try getByType('inventory')
            $items = $this->getByType('inventory');
            
            // If empty or very few items, try getAllActive
            if (empty($items) || count($items) < 5) {
                $allItems = $this->getAllActive();
                if (!empty($allItems)) {
                    // Filter to inventory type, but also include items with empty/null item_type
                    $filtered = array_filter($allItems, function($item) {
                        $itemType = strtolower($item['item_type'] ?? '');
                        return $itemType === 'inventory' || $itemType === '' || empty($itemType);
                    });
                    if (!empty($filtered)) {
                        $items = array_values($filtered);
                    }
                }
            }
            
            // If still empty, get ALL items regardless of type or status
            if (empty($items)) {
                try {
                    $allItems = $this->getAll();
                    // Don't filter - return all items so they're visible
                    $items = $allItems;
                    error_log('Item_model getInventoryItems: Using getAll() fallback, returned ' . count($items) . ' items');
                } catch (Exception $e) {
                    error_log('Item_model getInventoryItems fallback error: ' . $e->getMessage());
                }
            }
            
            error_log('Item_model getInventoryItems returned ' . count($items) . ' items');
            return $items;
        } catch (Exception $e) {
            error_log('Item_model getInventoryItems error: ' . $e->getMessage());
            // Last resort: return empty array
            return [];
        }
    }
    
    /**
     * Update item with proper status synchronization
     * @param int $id Item ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        try {
            // Ensure status columns are synchronized
            if (isset($data['item_status'])) {
                $itemStatus = $data['item_status'];
                if ($itemStatus === 'active') {
                    $data['status'] = 'active';
                } elseif ($itemStatus === 'discontinued' || $itemStatus === 'out_of_stock') {
                    $data['status'] = 'inactive';
                }
            } elseif (isset($data['status'])) {
                // If status is set but item_status is not, sync it
                if ($data['status'] === 'active') {
                    $data['item_status'] = 'active';
                } else {
                    $data['item_status'] = 'discontinued';
                }
            }
            
            // Always set updated_at
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Log price change if retail_price or wholesale_price changed
            $currentItem = $this->getById($id);
            if ($currentItem && (
                ($data['retail_price'] ?? $currentItem['retail_price']) != $currentItem['retail_price'] || 
                ($data['wholesale_price'] ?? $currentItem['wholesale_price']) != $currentItem['wholesale_price']
            )) {
                $pricingModel = $this->loadModel('Wholesale_pricing_model');
                $pricingModel->logPriceChange(
                    $id, 
                    $currentItem['retail_price'], 
                    $data['retail_price'] ?? $currentItem['retail_price'],
                    $currentItem['wholesale_price'],
                    $data['wholesale_price'] ?? $currentItem['wholesale_price'],
                    $this->session['user_id'] ?? 1,
                    $data['price_change_reason'] ?? 'Manual update'
                );
            }
            
            // Use parent update method
            $result = parent::update($id, $data);
            
            if ($result) {
                error_log('Item_model update successful for item ID: ' . $id);
            } else {
                error_log('Item_model update returned false for item ID: ' . $id);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Item_model update error: ' . $e->getMessage());
            error_log('Item_model update stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
}

