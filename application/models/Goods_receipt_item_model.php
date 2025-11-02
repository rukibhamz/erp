<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_receipt_item_model extends Base_Model {
    protected $table = 'goods_receipt_items';
    
    public function getByGRN($grnId) {
        try {
            return $this->db->fetchAll(
                "SELECT gri.*, i.item_name, i.sku, i.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` gri
                 JOIN `" . $this->db->getPrefix() . "items` i ON gri.item_id = i.id
                 WHERE gri.grn_id = ?
                 ORDER BY gri.id",
                [$grnId]
            );
        } catch (Exception $e) {
            error_log('Goods_receipt_item_model getByGRN error: ' . $e->getMessage());
            return [];
        }
    }
}

