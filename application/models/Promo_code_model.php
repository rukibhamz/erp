<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Promo_code_model extends Base_Model {
    protected $table = 'promo_codes';
    
    public function validateCode($code, $amount = 0, $resourceIds = [], $addonIds = [], $baseAmount = null, $addonsTotal = null) {
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
            
            $resourceAmount = ($baseAmount !== null) ? floatval($baseAmount) : floatval($amount);
            $addonsAmount = ($addonsTotal !== null) ? floatval($addonsTotal) : 0.0;
            $appliesToAddons = intval($promo['apply_to_addons'] ?? 0) === 1;
            $discountBaseAmount = $resourceAmount + ($appliesToAddons ? $addonsAmount : 0.0);

            // Check minimum amount against effective discount base.
            if ($promo['minimum_amount'] && $discountBaseAmount < floatval($promo['minimum_amount'])) {
                return ['valid' => false, 'message' => 'Minimum amount not met for this promo code'];
            }
            
            // Check applicable to
            if ($promo['applicable_to'] !== 'all') {
                $applicableIds = json_decode($promo['applicable_ids'] ?? '[]', true);
                
                if ($promo['applicable_to'] === 'resource') {
                    if (empty($resourceIds)) {
                        return ['valid' => false, 'message' => 'Promo code requires a valid resource selection'];
                    }
                    $intersect = array_intersect($resourceIds, $applicableIds);
                    if (empty($intersect)) {
                        return ['valid' => false, 'message' => 'Promo code not applicable to selected resources'];
                    }
                } elseif ($promo['applicable_to'] === 'addon') {
                    if (empty($addonIds)) {
                        return ['valid' => false, 'message' => 'Promo code applies only to specific add-ons'];
                    }
                    $intersect = array_intersect($addonIds, $applicableIds);
                    if (empty($intersect)) {
                        return ['valid' => false, 'message' => 'Promo code not applicable to selected add-ons'];
                    }
                }
            }
            
            // Calculate discount
            $discountAmount = 0;
            if ($promo['discount_type'] === 'percentage') {
                $discountAmount = ($discountBaseAmount * floatval($promo['discount_value'])) / 100;
                if ($promo['maximum_discount']) {
                    $discountAmount = min($discountAmount, floatval($promo['maximum_discount']));
                }
            } else {
                $discountAmount = min(floatval($promo['discount_value']), $discountBaseAmount);
            }
            
            return [
                'valid' => true,
                'promo' => $promo,
                'discount_amount' => $discountAmount,
                'discount_base_amount' => $discountBaseAmount,
                'apply_to_addons' => $appliesToAddons
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

