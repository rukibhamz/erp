<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_take_item_model extends Base_Model {
    protected $table = 'stock_take_items';
    
    public function getByStockTake($stockTakeId) {
        try {
            return $this->db->fetchAll(
                "SELECT sti.*, i.item_name, i.sku, i.unit_of_measure
                 FROM `" . $this->db->getPrefix() . $this->table . "` sti
                 JOIN `" . $this->db->getPrefix() . "items` i ON sti.item_id = i.id
                 WHERE sti.stock_take_id = ?
                 ORDER BY i.item_name",
                [$stockTakeId]
            );
        } catch (Exception $e) {
            error_log('Stock_take_item_model getByStockTake error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function updateCount($id, $countedQty, $countedBy) {
        try {
            $item = $this->getById($id);
            if ($item) {
                $variance = floatval($countedQty) - floatval($item['expected_qty']);
                return $this->update($id, [
                    'counted_qty' => floatval($countedQty),
                    'variance' => $variance,
                    'counted_by' => $countedBy,
                    'counted_at' => date('Y-m-d H:i:s')
                ]);
            }
            return false;
        } catch (Exception $e) {
            error_log('Stock_take_item_model updateCount error: ' . $e->getMessage());
            return false;
        }
    }
}

