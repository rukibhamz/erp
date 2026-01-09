<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_transaction_model extends Base_Model {
    protected $table = 'stock_transactions';
    
    public function getNextTransactionNumber($type) {
        try {
            $prefix = strtoupper(substr($type, 0, 3));
            $year = date('Y');
            $lastNumber = $this->db->fetchOne(
                "SELECT transaction_number FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE transaction_number LIKE '{$prefix}-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastNumber) {
                $parts = explode('-', $lastNumber['transaction_number']);
                $number = intval($parts[2] ?? 0) + 1;
                return "{$prefix}-{$year}-" . str_pad($number, 6, '0', STR_PAD_LEFT);
            }
            return "{$prefix}-{$year}-000001";
        } catch (Exception $e) {
            error_log('Stock_transaction_model getNextTransactionNumber error: ' . $e->getMessage());
            return 'TXN-' . date('Y') . '-000001';
        }
    }
    
    public function getByItem($itemId, $limit = 100) {
        try {
            return $this->db->fetchAll(
                "SELECT st.*, lf.property_name as location_from_name, lt.property_name as location_to_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` st
                 LEFT JOIN `" . $this->db->getPrefix() . "properties` lf ON st.location_from_id = lf.id
                 LEFT JOIN `" . $this->db->getPrefix() . "properties` lt ON st.location_to_id = lt.id
                 WHERE st.item_id = ?
                 ORDER BY st.transaction_date DESC, st.id DESC
                 LIMIT ?",
                [$itemId, $limit]
            );
        } catch (Exception $e) {
            error_log('Stock_transaction_model getByItem error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByLocation($locationId, $type = null, $limit = 100) {
        try {
            $sql = "SELECT st.*, i.item_name, i.sku
                    FROM `" . $this->db->getPrefix() . $this->table . "` st
                    JOIN `" . $this->db->getPrefix() . "items` i ON st.item_id = i.id
                    WHERE (st.location_from_id = ? OR st.location_to_id = ?)";
            
            $params = [$locationId, $locationId];
            
            if ($type) {
                $sql .= " AND st.transaction_type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY st.transaction_date DESC, st.id DESC LIMIT ?";
            $params[] = $limit;
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Stock_transaction_model getByLocation error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function create($data) {
        try {
            // Auto-generate transaction number if not provided
            if (empty($data['transaction_number'])) {
                $data['transaction_number'] = $this->getNextTransactionNumber($data['transaction_type']);
            }
            
            return parent::create($data);
        } catch (Exception $e) {
            error_log('Stock_transaction_model create error: ' . $e->getMessage());
            return false;
        }
    }
}

