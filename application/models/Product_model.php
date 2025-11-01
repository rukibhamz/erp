<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends Base_Model {
    protected $table = 'products';
    
    public function getNextProductCode() {
        try {
            $result = $this->db->fetchOne(
                "SELECT MAX(CAST(SUBSTRING(product_code, 5) AS UNSIGNED)) as max_code
                 FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE product_code LIKE 'PROD-%'"
            );
            $nextNum = ($result && isset($result['max_code'])) ? intval($result['max_code']) + 1 : 1;
            return 'PROD-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log('Product_model getNextProductCode error: ' . $e->getMessage());
            return 'PROD-00001';
        }
    }
    
    public function getByType($type) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE type = ? AND status = 'active' 
                 ORDER BY product_name",
                [$type]
            );
        } catch (Exception $e) {
            error_log('Product_model getByType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByCategory($category) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE category = ? AND status = 'active' 
                 ORDER BY product_name",
                [$category]
            );
        } catch (Exception $e) {
            error_log('Product_model getByCategory error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function search($query) {
        try {
            $searchTerm = "%{$query}%";
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE (product_code LIKE ? OR product_name LIKE ? OR description LIKE ?) 
                 AND status = 'active'
                 ORDER BY product_name
                 LIMIT 50",
                [$searchTerm, $searchTerm, $searchTerm]
            );
        } catch (Exception $e) {
            error_log('Product_model search error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getCategories() {
        try {
            $result = $this->db->fetchAll(
                "SELECT DISTINCT category FROM `" . $this->db->getPrefix() . $this->table . "`
                 WHERE category IS NOT NULL AND category != '' AND status = 'active'
                 ORDER BY category"
            );
            return array_column($result, 'category');
        } catch (Exception $e) {
            error_log('Product_model getCategories error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updateStock($productId, $quantity, $operation = 'subtract') {
        try {
            $product = $this->getById($productId);
            if (!$product || !$product['inventory_tracked']) {
                return false;
            }
            
            $currentStock = floatval($product['stock_quantity']);
            $newStock = $operation === 'add' 
                ? $currentStock + $quantity 
                : $currentStock - $quantity;
            
            return $this->update($productId, ['stock_quantity' => max(0, $newStock)]);
        } catch (Exception $e) {
            error_log('Product_model updateStock error: ' . $e->getMessage());
            return false;
        }
    }
}

