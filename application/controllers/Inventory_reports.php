<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_reports extends Base_Controller {
    private $itemModel;
    private $stockLevelModel;
    private $transactionModel;
    private $poModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->itemModel = $this->loadModel('Item_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->transactionModel = $this->loadModel('Stock_transaction_model');
        $this->poModel = $this->loadModel('Purchase_order_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Inventory Reports',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/reports/index', $data);
    }
    
    /**
     * Stock Valuation Report
     */
    public function valuation() {
        try {
            $data = [
                'page_title' => 'Inventory Valuation Report',
                'valuation' => $this->db->fetchAll("SELECT * FROM `" . $this->db->getPrefix() . "vw_inventory_valuation` ORDER BY total_value DESC"),
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('inventory/reports/valuation', $data);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading valuation report: ' . $e->getMessage());
            redirect('inventory/reports');
        }
    }
    
    /**
     * Stock on Hand Report
     */
    public function stock() {
        try {
            $sql = "SELECT i.*, 
                    COALESCE((SELECT SUM(quantity) FROM `" . $this->db->getPrefix() . "stock_levels` WHERE item_id = i.id), 0) as total_stock
                    FROM `" . $this->db->getPrefix() . "items` i 
                    ORDER BY i.item_name ASC";
            
            $data = [
                'page_title' => 'Stock on Hand Report',
                'items' => $this->db->fetchAll($sql),
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('inventory/reports/stock', $data);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading stock report: ' . $e->getMessage());
            redirect('inventory/reports');
        }
    }

    /**
     * Stock Movements Report
     */
    public function movements() {
        try {
            $sql = "SELECT st.*, i.item_name, i.sku, 
                    lf.location_name as location_from_name, 
                    lt.location_name as location_to_name
                    FROM `" . $this->db->getPrefix() . "stock_transactions` st
                    LEFT JOIN `" . $this->db->getPrefix() . "items` i ON st.item_id = i.id
                    LEFT JOIN `" . $this->db->getPrefix() . "locations` lf ON st.location_from_id = lf.id
                    LEFT JOIN `" . $this->db->getPrefix() . "locations` lt ON st.location_to_id = lt.id
                    ORDER BY st.transaction_date DESC, st.id DESC
                    LIMIT 200";
            
            $data = [
                'page_title' => 'Stock Movements Report',
                'transactions' => $this->db->fetchAll($sql),
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('inventory/reports/movements', $data);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading movements report: ' . $e->getMessage());
            redirect('inventory/reports');
        }
    }
    
    /**
     * Reorder Level Report
     */
    public function reorder() {
        try {
            $sql = "SELECT i.*, 
                    COALESCE((SELECT SUM(quantity) FROM `" . $this->db->getPrefix() . "stock_levels` WHERE item_id = i.id), 0) as total_stock
                    FROM `" . $this->db->getPrefix() . "items` i 
                    WHERE i.reorder_point > 0
                    HAVING total_stock <= i.reorder_point
                    ORDER BY i.item_name ASC";
            
            $data = [
                'page_title' => 'Reorder Level Report',
                'items' => $this->db->fetchAll($sql),
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('inventory/reports/reorder', $data);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading reorder report: ' . $e->getMessage());
            redirect('inventory/reports');
        }
    }
    
    /**
     * Fast/Slow Moving Items Analysis
     */
    public function movementAnalysis() {
        try {
            // Fast moving (Top 10 items by transaction count in last 90 days)
            $fastSql = "SELECT i.id, i.item_name, i.sku, 
                        COUNT(st.id) as txn_count, 
                        SUM(ABS(st.quantity)) as total_qty
                        FROM `" . $this->db->getPrefix() . "items` i
                        JOIN `" . $this->db->getPrefix() . "stock_transactions` st ON i.id = st.item_id
                        WHERE st.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                        GROUP BY i.id
                        ORDER BY txn_count DESC
                        LIMIT 10";
            
            // Slow moving (Items with stock but NO transactions in last 90 days, or lowest count)
            $slowSql = "SELECT i.id, i.item_name, i.sku, 
                        (SELECT COUNT(st.id) FROM `" . $this->db->getPrefix() . "stock_transactions` st WHERE st.item_id = i.id AND st.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)) as txn_count,
                        (SELECT SUM(ABS(st.quantity)) FROM `" . $this->db->getPrefix() . "stock_transactions` st WHERE st.item_id = i.id AND st.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)) as total_qty
                        FROM `" . $this->db->getPrefix() . "items` i
                        HAVING total_qty > 0 OR txn_count = 0
                        ORDER BY txn_count ASC, i.item_name ASC
                        LIMIT 10";
            
            $data = [
                'page_title' => 'Fast/Slow Moving Items',
                'fast_moving' => $this->db->fetchAll($fastSql),
                'slow_moving' => $this->db->fetchAll($slowSql),
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('inventory/reports/movement_analysis', $data);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading movement analysis: ' . $e->getMessage());
            redirect('inventory/reports');
        }
    }
    
    /**
     * Purchase Analysis Report
     */
    public function purchases() {
        try {
            $sql = "SELECT po.*, s.supplier_name
                    FROM `" . $this->db->getPrefix() . "purchase_orders` po
                    LEFT JOIN `" . $this->db->getPrefix() . "suppliers` s ON po.supplier_id = s.id
                    ORDER BY po.order_date DESC
                    LIMIT 100";
            
            $data = [
                'page_title' => 'Purchase Analysis Report',
                'orders' => $this->db->fetchAll($sql),
                'flash' => $this->getFlashMessage()
            ];
            
            $this->loadView('inventory/reports/purchases', $data);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading purchase report: ' . $e->getMessage());
            redirect('inventory/reports');
        }
    }
}

