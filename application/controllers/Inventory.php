<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory extends Base_Controller {
    private $itemModel;
    private $stockLevelModel;
    private $locationModel;
    private $transactionModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->itemModel = $this->loadModel('Item_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->transactionModel = $this->loadModel('Stock_transaction_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $totalItems = count($this->itemModel->getByType('inventory'));
            $totalValue = $this->itemModel->getTotalInventoryValue();
            $lowStockItems = $this->itemModel->getLowStock();
            $outOfStockItems = $this->itemModel->getOutOfStock();
            $locations = $this->locationModel->getActive();
        } catch (Exception $e) {
            $totalItems = 0;
            $totalValue = 0;
            $lowStockItems = [];
            $outOfStockItems = [];
            $locations = [];
        }
        
        $data = [
            'page_title' => 'Inventory Dashboard',
            'stats' => [
                'total_items' => $totalItems,
                'total_value' => $totalValue,
                'low_stock_count' => count($lowStockItems),
                'out_of_stock_count' => count($outOfStockItems)
            ],
            'low_stock_items' => array_slice($lowStockItems, 0, 10),
            'out_of_stock_items' => array_slice($outOfStockItems, 0, 10),
            'locations' => $locations,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/index', $data);
    }
}

