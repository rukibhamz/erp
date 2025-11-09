<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Items extends Base_Controller {
    private $itemModel;
    private $stockLevelModel;
    private $locationModel;
    private $supplierModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->itemModel = $this->loadModel('Item_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->supplierModel = $this->loadModel('Supplier_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $filter = $_GET['filter'] ?? 'all';
        $itemType = $_GET['item_type'] ?? null;
        $category = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? null;
        
        try {
            $allItems = [];
            if ($search) {
                $allItems = $this->itemModel->search($search);
            } else {
                $allItems = $this->itemModel->getAll();
            }
            
            // Apply filters
            $items = $allItems;
            if ($itemType && $itemType !== 'all') {
                $items = array_filter($items, function($i) use ($itemType) {
                    return $i['item_type'] === $itemType;
                });
            }
            
            if ($category) {
                $items = array_filter($items, function($i) use ($category) {
                    return $i['category'] === $category;
                });
            }
            
            if ($filter === 'low_stock') {
                $lowStockItems = $this->itemModel->getLowStock();
                $lowStockIds = array_column($lowStockItems, 'id');
                $items = array_filter($items, function($i) use ($lowStockIds) {
                    return in_array($i['id'], $lowStockIds);
                });
            } elseif ($filter === 'out_of_stock') {
                $outOfStockItems = $this->itemModel->getOutOfStock();
                $outOfStockIds = array_column($outOfStockItems, 'id');
                $items = array_filter($items, function($i) use ($outOfStockIds) {
                    return in_array($i['id'], $outOfStockIds);
                });
            }
            
            // Get unique categories
            $categories = array_unique(array_column($allItems, 'category'));
            $categories = array_filter($categories);
            
        } catch (Exception $e) {
            $items = [];
            $categories = [];
        }
        
        $data = [
            'page_title' => 'Items',
            'items' => $items,
            'categories' => $categories,
            'selected_filter' => $filter,
            'selected_item_type' => $itemType,
            'selected_category' => $category,
            'search_term' => $search,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/items/index', $data);
    }
    
    public function create() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'sku' => sanitize_input($_POST['sku'] ?? ''),
                'item_name' => sanitize_input($_POST['item_name'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'item_type' => sanitize_input($_POST['item_type'] ?? 'inventory'),
                'category' => sanitize_input($_POST['category'] ?? ''),
                'subcategory' => sanitize_input($_POST['subcategory'] ?? ''),
                'brand' => sanitize_input($_POST['brand'] ?? ''),
                'manufacturer' => sanitize_input($_POST['manufacturer'] ?? ''),
                'model_number' => sanitize_input($_POST['model_number'] ?? ''),
                'barcode' => sanitize_input($_POST['barcode'] ?? ''),
                'unit_of_measure' => sanitize_input($_POST['unit_of_measure'] ?? 'each'),
                'reorder_point' => floatval($_POST['reorder_point'] ?? 0),
                'reorder_quantity' => floatval($_POST['reorder_quantity'] ?? 0),
                'safety_stock' => floatval($_POST['safety_stock'] ?? 0),
                'max_stock' => !empty($_POST['max_stock']) ? floatval($_POST['max_stock']) : null,
                'lead_time_days' => intval($_POST['lead_time_days'] ?? 0),
                'item_status' => sanitize_input($_POST['item_status'] ?? 'active'),
                'cost_price' => floatval($_POST['cost_price'] ?? 0),
                'selling_price' => floatval($_POST['selling_price'] ?? 0),
                'retail_price' => floatval($_POST['retail_price'] ?? 0),
                'wholesale_price' => floatval($_POST['wholesale_price'] ?? 0),
                'costing_method' => sanitize_input($_POST['costing_method'] ?? 'weighted_average'),
                'track_serial' => !empty($_POST['track_serial']) ? 1 : 0,
                'track_batch' => !empty($_POST['track_batch']) ? 1 : 0,
                'expiry_tracking' => !empty($_POST['expiry_tracking']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Auto-generate SKU if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['sku'])) {
                $data['sku'] = $this->itemModel->getNextSKU();
            }
            
            // Handle specifications JSON
            if (!empty($_POST['specifications'])) {
                $specs = [];
                foreach ($_POST['specifications'] as $key => $value) {
                    if (!empty($value)) {
                        $specs[$key] = sanitize_input($value);
                    }
                }
                $data['specifications'] = json_encode($specs);
            }
            
            $itemId = $this->itemModel->create($data);
            
            if ($itemId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Items', 'Created item: ' . $data['item_name']);
                $this->setFlashMessage('success', 'Item created successfully.');
                redirect('inventory/items/view/' . $itemId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create item.');
            }
        }
        
        try {
            $locations = $this->locationModel->getActive();
            $suppliers = $this->supplierModel->getActive();
        } catch (Exception $e) {
            $locations = [];
            $suppliers = [];
        }
        
        $data = [
            'page_title' => 'Create Item',
            'locations' => $locations,
            'suppliers' => $suppliers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/items/create', $data);
    }
    
    public function view($id) {
        try {
            $item = $this->itemModel->getById($id);
            if (!$item) {
                $this->setFlashMessage('danger', 'Item not found.');
                redirect('inventory/items');
            }
            
            $item['specifications'] = json_decode($item['specifications'] ?? '{}', true);
            
            $stockLevels = $this->stockLevelModel->getByItem($id);
            $transactions = $this->transactionModel->getByItem($id, 20);
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading item.');
            redirect('inventory/items');
        }
        
        $data = [
            'page_title' => 'Item: ' . $item['item_name'],
            'item' => $item,
            'stock_levels' => $stockLevels,
            'transactions' => $transactions,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/items/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('inventory', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'sku' => sanitize_input($_POST['sku'] ?? ''),
                'item_name' => sanitize_input($_POST['item_name'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'item_type' => sanitize_input($_POST['item_type'] ?? 'inventory'),
                'category' => sanitize_input($_POST['category'] ?? ''),
                'subcategory' => sanitize_input($_POST['subcategory'] ?? ''),
                'brand' => sanitize_input($_POST['brand'] ?? ''),
                'manufacturer' => sanitize_input($_POST['manufacturer'] ?? ''),
                'model_number' => sanitize_input($_POST['model_number'] ?? ''),
                'barcode' => sanitize_input($_POST['barcode'] ?? ''),
                'unit_of_measure' => sanitize_input($_POST['unit_of_measure'] ?? 'each'),
                'reorder_point' => floatval($_POST['reorder_point'] ?? 0),
                'reorder_quantity' => floatval($_POST['reorder_quantity'] ?? 0),
                'safety_stock' => floatval($_POST['safety_stock'] ?? 0),
                'max_stock' => !empty($_POST['max_stock']) ? floatval($_POST['max_stock']) : null,
                'lead_time_days' => intval($_POST['lead_time_days'] ?? 0),
                'item_status' => sanitize_input($_POST['item_status'] ?? 'active'),
                'cost_price' => floatval($_POST['cost_price'] ?? 0),
                'selling_price' => floatval($_POST['selling_price'] ?? 0),
                'retail_price' => floatval($_POST['retail_price'] ?? 0),
                'wholesale_price' => floatval($_POST['wholesale_price'] ?? 0),
                'costing_method' => sanitize_input($_POST['costing_method'] ?? 'weighted_average'),
                'track_serial' => !empty($_POST['track_serial']) ? 1 : 0,
                'track_batch' => !empty($_POST['track_batch']) ? 1 : 0,
                'expiry_tracking' => !empty($_POST['expiry_tracking']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Handle specifications JSON
            if (!empty($_POST['specifications'])) {
                $specs = [];
                foreach ($_POST['specifications'] as $key => $value) {
                    if (!empty($value)) {
                        $specs[$key] = sanitize_input($value);
                    }
                }
                $data['specifications'] = json_encode($specs);
            }
            
            if ($this->itemModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Items', 'Updated item: ' . $id);
                $this->setFlashMessage('success', 'Item updated successfully.');
                redirect('inventory/items/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update item.');
            }
        }
        
        try {
            $item = $this->itemModel->getById($id);
            if (!$item) {
                $this->setFlashMessage('danger', 'Item not found.');
                redirect('inventory/items');
            }
            
            $item['specifications'] = json_decode($item['specifications'] ?? '{}', true);
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading item.');
            redirect('inventory/items');
        }
        
        $data = [
            'page_title' => 'Edit Item',
            'item' => $item,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/items/edit', $data);
    }
}

