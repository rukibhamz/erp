<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase_order_item_model extends Base_Model {
    protected $table = 'purchase_order_items';
    
    public function getByPO($poId) {
        try {
            return $this->db->fetchAll(
                "SELECT poi.*, i.item_name, i.sku, i.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` poi
                 JOIN `" . $this->db->getPrefix() . "items` i ON poi.item_id = i.id
                 WHERE poi.po_id = ?
                 ORDER BY poi.id",
                [$poId]
            );
        } catch (Exception $e) {
            error_log('Purchase_order_item_model getByPO error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updateReceived($id, $quantityReceived) {
        try {
            $item = $this->getById($id);
            if ($item) {
                $newReceived = floatval($item['quantity_received']) + floatval($quantityReceived);
                $newPending = floatval($item['quantity']) - $newReceived;
                
                return $this->update($id, [
                    'quantity_received' => $newReceived,
                    'quantity_pending' => $newPending > 0 ? $newPending : 0
                ]);
            }
            return false;
        } catch (Exception $e) {
            error_log('Purchase_order_item_model updateReceived error: ' . $e->getMessage());
            return false;
        }
    }
}

