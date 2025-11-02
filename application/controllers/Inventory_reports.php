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
}

