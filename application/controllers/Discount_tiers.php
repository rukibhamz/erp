<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Discount_tiers extends Base_Controller {
    private $tierModel;
    private $itemModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('wholesale_pricing', 'read');
        $this->tierModel = $this->loadModel('Discount_tier_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        redirect('inventory/items');
    }
    
    public function item($itemId) {
        $item = $this->itemModel->getById($itemId);
        $tiers = $this->tierModel->getByItem($itemId);
        
        $data = [
            'page_title' => 'Discount Tiers: ' . $item['item_name'],
            'item' => $item,
            'tiers' => $tiers,
            'flash' => $this->getFlashMessage()
        ];
        $this->loadView('wholesale_pricing/tiers', $data);
    }
    
    public function save($itemId) {
        $this->requirePermission('wholesale_pricing', 'write');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $minQty = floatval($_POST['min_quantity'] ?? 0);
            $val = floatval($_POST['discount_value'] ?? 0);
            $type = $_POST['discount_type'] ?? 'percentage';
            
            $data = [
                'item_id' => $itemId,
                'min_quantity' => $minQty,
                'discount_type' => $type,
                'discount_value' => $val
            ];
            
            if ($this->tierModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Pricing', 'Added discount tier for item ' . $itemId);
                $this->setFlashMessage('success', 'Discount tier added.');
            }
        }
        redirect('discount_tiers/item/' . $itemId);
    }
    
    public function delete($id) {
        $this->requirePermission('wholesale_pricing', 'delete');
        $tier = $this->tierModel->getById($id);
        if ($tier && $this->tierModel->delete($id)) {
            $this->setFlashMessage('success', 'Tier deleted.');
            redirect('discount_tiers/item/' . $tier['item_id']);
        }
        redirect('inventory/items');
    }
}
