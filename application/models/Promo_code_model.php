<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Promo_code_model extends Base_Model {
    protected $table = 'promo_codes';
    
    public function validateCode($code, $amount = 0, $resourceIds = [], $addonIds = []) {
        try {
            $promo = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE code = ? AND is_active = 1
                 AND valid_from <= CURDATE() AND valid_to >= CURDATE()",
                [strtoupper($code)]
            );
            
            if (!$promo) {
                return ['valid' => false, 'message' => 'Invalid or expired promo code'];
            }
            
            // Check usage limit
            if ($promo['usage_limit'] && $promo['used_count'] >= $promo['usage_limit']) {
                return ['valid' => false, 'message' => 'Promo code usage limit reached'];
            }
            
            // Check minimum amount
            if ($promo['minimum_amount'] && $amount < floatval($promo['minimum_amount'])) {
                return ['valid' => false, 'message' => 'Minimum amount not met for this promo code'];
            }
            
            // Check applicable to
            if ($promo['applicable_to'] !== 'all') {
                $applicableIds = json_decode($promo['applicable_ids'] ?? '[]', true);
                
                if ($promo['applicable_to'] === 'resource' && !empty($resourceIds)) {
                    $intersect = array_intersect($resourceIds, $applicableIds);
                    if (empty($intersect)) {
                        return ['valid' => false, 'message' => 'Promo code not applicable to selected resources'];
                    }
                } elseif ($promo['applicable_to'] === 'addon' && !empty($addonIds)) {
                    $intersect = array_intersect($addonIds, $applicableIds);
                    if (empty($intersect)) {
                        return ['valid' => false, 'message' => 'Promo code not applicable to selected add-ons'];
                    }
                }
            }
            
            // Calculate discount
            $discountAmount = 0;
            if ($promo['discount_type'] === 'percentage') {
                $discountAmount = ($amount * floatval($promo['discount_value'])) / 100;
                if ($promo['maximum_discount']) {
                    $discountAmount = min($discountAmount, floatval($promo['maximum_discount']));
                }
            } else {
                $discountAmount = floatval($promo['discount_value']);
            }
            
            return [
                'valid' => true,
                'promo' => $promo,
                'discount_amount' => $discountAmount
            ];
        } catch (Exception $e) {
            error_log('Promo_code_model validateCode error: ' . $e->getMessage());
            return ['valid' => false, 'message' => 'Error validating promo code'];
        }
    }
    
    public function incrementUsage($codeId) {
        try {
            return $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . $this->table . "` 
                 SET used_count = used_count + 1 
                 WHERE id = ?",
                [$codeId]
            );
        } catch (Exception $e) {
            error_log('Promo_code_model incrementUsage error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_active = 1 
                 AND valid_from <= CURDATE() 
                 AND valid_to >= CURDATE()
                 AND (usage_limit IS NULL OR used_count < usage_limit)
                 ORDER BY code ASC"
            );
        } catch (Exception $e) {
            error_log('Promo_code_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
}

