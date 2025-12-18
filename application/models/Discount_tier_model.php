<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Discount_tier_model extends Base_Model {
    protected $table = 'discount_tiers';
    
    public function getByItem($itemId) {
        return $this->db->fetchAll(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE item_id = ? ORDER BY min_quantity ASC",
            [$itemId]
        );
    }
    
    /**
     * Get the best applicable tier for a given quantity
     */
    public function getBestTier($itemId, $quantity) {
        return $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE item_id = ? AND min_quantity <= ? 
             ORDER BY min_quantity DESC LIMIT 1",
            [$itemId, $quantity]
        );
    }
}
