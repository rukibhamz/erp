<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fixed_asset_model extends Base_Model {
    protected $table = 'fixed_assets';
    
    public function getNextAssetTag($prefix = 'AST') {
        try {
            $year = date('Y');
            $lastTag = $this->db->fetchOne(
                "SELECT asset_tag FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE asset_tag LIKE '{$prefix}-{$year}-%' 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if ($lastTag) {
                $parts = explode('-', $lastTag['asset_tag']);
                $number = intval($parts[2] ?? 0) + 1;
                return "{$prefix}-{$year}-" . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
            return "{$prefix}-{$year}-00001";
        } catch (Exception $e) {
            error_log('Fixed_asset_model getNextAssetTag error: ' . $e->getMessage());
            return $prefix . '-' . date('Y') . '-00001';
        }
    }
    
    public function getWithDetails($id) {
        try {
            return $this->db->fetchOne(
                "SELECT fa.*, l.location_name, i.item_name, i.sku, s.supplier_name
                 FROM `" . $this->db->getPrefix() . $this->table . "` fa
                 LEFT JOIN `" . $this->db->getPrefix() . "locations` l ON fa.location_id = l.id
                 LEFT JOIN `" . $this->db->getPrefix() . "items` i ON fa.item_id = i.id
                 LEFT JOIN `" . $this->db->getPrefix() . "suppliers` s ON fa.supplier_id = s.id
                 WHERE fa.id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Fixed_asset_model getWithDetails error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByCategory($category) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE asset_category = ? AND asset_status = 'active'
                 ORDER BY asset_name",
                [$category]
            );
        } catch (Exception $e) {
            error_log('Fixed_asset_model getByCategory error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function calculateDepreciation($assetId) {
        try {
            $asset = $this->getById($assetId);
            if (!$asset) {
                return false;
            }
            
            $purchaseCost = floatval($asset['purchase_cost']);
            $salvageValue = floatval($asset['salvage_value']);
            $usefulLifeYears = intval($asset['useful_life_years']);
            $purchaseDate = $asset['purchase_date'];
            
            if ($usefulLifeYears <= 0) {
                return false;
            }
            
            // Calculate months since purchase
            $purchaseDateTime = new DateTime($purchaseDate);
            $now = new DateTime();
            $monthsSincePurchase = ($now->diff($purchaseDateTime)->y * 12) + $now->diff($purchaseDateTime)->m;
            
            if ($asset['depreciation_method'] === 'straight_line') {
                $monthlyDepreciation = ($purchaseCost - $salvageValue) / ($usefulLifeYears * 12);
                $accumulatedDepreciation = $monthlyDepreciation * min($monthsSincePurchase, $usefulLifeYears * 12);
                $netBookValue = $purchaseCost - $accumulatedDepreciation;
            } else {
                // Declining balance method
                $rate = 2 / $usefulLifeYears; // 200% declining balance
                $netBookValue = $purchaseCost;
                $accumulatedDepreciation = 0;
                
                for ($i = 0; $i < min($monthsSincePurchase, $usefulLifeYears * 12); $i++) {
                    $monthlyDepreciation = $netBookValue * ($rate / 12);
                    if ($netBookValue - $monthlyDepreciation < $salvageValue) {
                        $monthlyDepreciation = $netBookValue - $salvageValue;
                    }
                    $accumulatedDepreciation += $monthlyDepreciation;
                    $netBookValue -= $monthlyDepreciation;
                }
            }
            
            // Update current value
            $this->update($assetId, [
                'current_value' => max($netBookValue, $salvageValue),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'monthly_depreciation' => $monthlyDepreciation ?? 0,
                'accumulated_depreciation' => $accumulatedDepreciation,
                'net_book_value' => max($netBookValue, $salvageValue)
            ];
        } catch (Exception $e) {
            error_log('Fixed_asset_model calculateDepreciation error: ' . $e->getMessage());
            return false;
        }
    }
}

