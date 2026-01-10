<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wholesale_pricing_model extends Base_Model {
    protected $table = 'price_history';
    private $itemModel;
    private $customerTypeModel;
    private $discountTierModel;
    
    public function __construct() {
        parent::__construct();
        // Lazy load models - don't instantiate in constructor to avoid circular dependencies
    }
    
    /**
     * Get or create Item_model instance
     */
    private function getItemModel() {
        if ($this->itemModel === null) {
            require_once BASEPATH . '../application/models/Item_model.php';
            $this->itemModel = new Item_model();
        }
        return $this->itemModel;
    }
    
    /**
     * Get or create Customer_type_model instance
     */
    private function getCustomerTypeModel() {
        if ($this->customerTypeModel === null) {
            require_once BASEPATH . '../application/models/Customer_type_model.php';
            $this->customerTypeModel = new Customer_type_model();
        }
        return $this->customerTypeModel;
    }
    
    /**
     * Get or create Discount_tier_model instance
     */
    private function getDiscountTierModel() {
        if ($this->discountTierModel === null) {
            require_once BASEPATH . '../application/models/Discount_tier_model.php';
            $this->discountTierModel = new Discount_tier_model();
        }
        return $this->discountTierModel;
    }
    
    /**
     * Calculate the applicable price for an item based on customer and quantity
     * Price priority: Specific discount tier > Customer type discount > Wholesale price > Retail price
     * 
     * @param int $itemId
     * @param int $customerId
     * @param float $quantity
     * @return array [price, unit_discount, final_price, source, moq_valid]
     */
    public function calculatePricing($itemId, $customerId, $quantity) {
        $item = $this->getItemModel()->getById($itemId);
        if (!$item) return null;
        
        $customer = $this->db->fetchOne(
            "SELECT c.*, ct.code as type_code, ct.discount_percentage 
             FROM `" . $this->db->getPrefix() . "customers` c 
             LEFT JOIN `" . $this->db->getPrefix() . "customer_types` ct ON c.customer_type_id = ct.id 
             WHERE c.id = ?",
            [$customerId]
        );
        
        $retailPrice = floatval($item['retail_price'] ?? 0);
        $wholesalePrice = floatval($item['wholesale_price'] ?? 0);
        $wholesaleMOQ = floatval($item['wholesale_moq'] ?? 0);
        $isWholesaleEnabled = !empty($item['is_wholesale_enabled']);
        
        $price = $retailPrice;
        $discount = 0;
        $source = 'Retail Price';
        $moqValid = true;
        
        // 1. Check if it's a wholesale customer and check MOQ
        if ($customer && $customer['type_code'] === 'WHOLESALE' && $isWholesaleEnabled) {
            if ($quantity < $wholesaleMOQ) {
                $moqValid = false;
            } else {
                $price = $wholesalePrice;
                $source = 'Wholesale Price';
            }
        }
        
        // 1b. Check for Discount MOQ (applies even to RETAIL if met)
        $discountMOQ = floatval($item['discount_moq'] ?? 0);
        if ($discountMOQ > 0 && $quantity >= $discountMOQ && $price > $wholesalePrice) {
            // If discount MOQ is met and current price is still higher than wholesale, use wholesale
            $price = $wholesalePrice;
            $source = 'Volume Discount (MOQ ' . $discountMOQ . ' met)';
        }
        
        // 2. Apply customer type percentage discount if applicable (on the current price)
        if ($customer && floatval($customer['discount_percentage']) > 0) {
            $typeDiscount = $price * ($customer['discount_percentage'] / 100);
            $price -= $typeDiscount;
            $source .= " (incl. " . $customer['type_code'] . " discount)";
        }
        
        // 3. Check for Quantity-based Discount Tiers (Overwrites previous price if tier is met)
        $tier = $this->getDiscountTierModel()->getBestTier($itemId, $quantity);
        if ($tier) {
            if ($tier['discount_type'] === 'fixed_price') {
                $price = floatval($tier['discount_value']);
                $source = 'Tiered Fixed Price';
            } else {
                $tierDiscount = $retailPrice * (floatval($tier['discount_value']) / 100);
                $price = $retailPrice - $tierDiscount;
                $source = 'Tiered Discount (' . $tier['discount_value'] . '%)';
            }
        }
        
        return [
            'unit_price' => $retailPrice,
            'final_price' => $price,
            'discount_amount' => $retailPrice - $price,
            'source' => $source,
            'moq_valid' => $moqValid,
            'moq_required' => $wholesaleMOQ
        ];
    }
    
    public function logPriceChange($itemId, $oldRetail, $newRetail, $oldWholesale, $newWholesale, $userId, $reason = '') {
        $data = [
            'item_id' => $itemId,
            'old_retail_price' => $oldRetail,
            'new_retail_price' => $newRetail,
            'old_wholesale_price' => $oldWholesale,
            'new_wholesale_price' => $newWholesale,
            'changed_by' => $userId,
            'change_reason' => $reason,
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert('price_history', $data);
    }
}
