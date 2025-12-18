<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wholesale_pricing extends Base_Controller {
    private $pricingModel;
    private $itemModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('wholesale_pricing', 'read');
        $this->pricingModel = $this->loadModel('Wholesale_pricing_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $items = $this->itemModel->getAll();
        $wholesaleItems = array_filter($items, function($i) {
            return !empty($i['is_wholesale_enabled']);
        });
        
        $data = [
            'page_title' => 'Wholesale Pricing Overview',
            'items' => $wholesaleItems,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('wholesale_pricing/index', $data);
    }
    
    public function setup($itemId) {
        $this->requirePermission('wholesale_pricing', 'update');
        $item = $this->itemModel->getById($itemId);
        if (!$item) {
            $this->setFlashMessage('danger', 'Item not found.');
            redirect('inventory/items');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $data = [
                'wholesale_price' => floatval($_POST['wholesale_price'] ?? 0),
                'wholesale_moq' => floatval($_POST['wholesale_moq'] ?? 0),
                'is_wholesale_enabled' => isset($_POST['is_wholesale_enabled']) ? 1 : 0,
                'price_change_reason' => sanitize_input($_POST['change_reason'] ?? 'Updated wholesale settings')
            ];
            
            if ($this->itemModel->update($itemId, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Wholesale', 'Updated wholesale pricing for: ' . $item['item_name']);
                $this->setFlashMessage('success', 'Wholesale pricing updated successfully.');
                redirect('inventory/items/view/' . $itemId);
            }
        }
        
        $data = [
            'page_title' => 'Setup Wholesale Pricing: ' . $item['item_name'],
            'item' => $item,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('wholesale_pricing/form', $data);
    }
}
