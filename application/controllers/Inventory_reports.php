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
}

