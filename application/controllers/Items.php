<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Items extends Base_Controller {
    private $itemModel;
    private $stockLevelModel;
    private $locationModel;
    private $supplierModel;
    private $activityModel;
    private $transactionModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->itemModel = $this->loadModel('Item_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->supplierModel = $this->loadModel('Supplier_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->transactionModel = $this->loadModel('Stock_transaction_model');
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
                // If search returns empty, try getAll as fallback
                if (empty($allItems)) {
                    $allItems = $this->itemModel->getAll();
                }
            } else {
                // Use getAll() which returns all items regardless of status
                $allItems = $this->itemModel->getAll();
            }
            
            error_log('Items index: Retrieved ' . count($allItems) . ' items');
            
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
            check_csrf(); // CSRF Protection
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
                'wholesale_moq' => floatval($_POST['wholesale_moq'] ?? 0),
                'is_wholesale_enabled' => isset($_POST['is_wholesale_enabled']) ? 1 : 0,
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
            $id = intval($id);
            if (!$id) {
                $this->setFlashMessage('danger', 'Invalid item ID.');
                redirect('inventory/items');
            }
            
            $item = $this->itemModel->getById($id);
            if (!$item) {
                error_log('Items view: Item not found for ID: ' . $id);
                $this->setFlashMessage('danger', 'Item not found.');
                redirect('inventory/items');
            }
            
            error_log('Items view: Successfully loaded item ID: ' . $id . ', Name: ' . ($item['item_name'] ?? 'N/A'));
            
            $item['specifications'] = json_decode($item['specifications'] ?? '{}', true);
            
            // Get stock levels - handle errors gracefully
            try {
                $stockLevels = $this->stockLevelModel->getByItem($id);
            } catch (Exception $e) {
                error_log('Items view: Error loading stock levels: ' . $e->getMessage());
                $stockLevels = [];
            }
            
            // Get transactions - handle errors gracefully
            try {
                $transactions = $this->transactionModel->getByItem($id, 20);
            } catch (Exception $e) {
                error_log('Items view: Error loading transactions: ' . $e->getMessage());
                $transactions = [];
            }
            
        } catch (Exception $e) {
            error_log('Items view error: ' . $e->getMessage());
            error_log('Items view stack trace: ' . $e->getTraceAsString());
            $this->setFlashMessage('danger', 'Error loading item: ' . $e->getMessage());
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
            check_csrf(); // CSRF Protection
            
            try {
                // Get current item data for comparison
                $currentItem = $this->itemModel->getById($id);
                if (!$currentItem) {
                    $this->setFlashMessage('danger', 'Item not found.');
                    redirect('inventory/items');
                }
                
                $data = [
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
                
                // Sync status column with item_status
                $itemStatus = $data['item_status'];
                if ($itemStatus === 'active') {
                    $data['status'] = 'active';
                } elseif ($itemStatus === 'discontinued' || $itemStatus === 'out_of_stock') {
                    $data['status'] = 'inactive';
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
                
                // Update item using model's update method
                $updated = $this->itemModel->update($id, $data);
                
                if ($updated) {
                    // Sync with stock levels if reorder_point changed
                    if ($currentItem['reorder_point'] != $data['reorder_point']) {
                        $this->syncStockLevelsReorderPoint($id, $data['reorder_point']);
                    }
                    
                    // Log activity
                    $this->activityModel->log(
                        $this->session['user_id'], 
                        'update', 
                        'Items', 
                        'Updated item: ' . $currentItem['item_name'] . ' (ID: ' . $id . ')'
                    );
                    
                    $this->setFlashMessage('success', 'Item updated successfully.');
                    redirect('inventory/items/view/' . $id);
                } else {
                    $this->setFlashMessage('danger', 'Failed to update item. Please try again.');
                }
            } catch (Exception $e) {
                error_log('Items edit error: ' . $e->getMessage());
                error_log('Items edit stack trace: ' . $e->getTraceAsString());
                $this->setFlashMessage('danger', 'Error updating item: ' . $e->getMessage());
            }
        }
        
        try {
            $id = intval($id);
            if (!$id) {
                $this->setFlashMessage('danger', 'Invalid item ID.');
                redirect('inventory/items');
            }
            
            // Load complete item data with all columns
            $item = $this->itemModel->getById($id);
            if (!$item) {
                error_log('Items edit: Item not found for ID: ' . $id);
                $this->setFlashMessage('danger', 'Item not found.');
                redirect('inventory/items');
            }
            
            // Ensure all item fields are present with defaults
            $item['item_code'] = $item['item_code'] ?? '';
            $item['sku'] = $item['sku'] ?? '';
            $item['item_name'] = $item['item_name'] ?? '';
            $item['item_type'] = $item['item_type'] ?? 'inventory';
            $item['category'] = $item['category'] ?? '';
            $item['unit_of_measure'] = $item['unit_of_measure'] ?? 'pcs';
            $item['purchase_price'] = $item['purchase_price'] ?? 0;
            $item['selling_price'] = $item['selling_price'] ?? 0;
            $item['average_cost'] = $item['average_cost'] ?? 0;
            $item['reorder_point'] = $item['reorder_point'] ?? 0;
            $item['reorder_quantity'] = $item['reorder_quantity'] ?? 0;
            $item['description'] = $item['description'] ?? '';
            $item['specifications'] = json_decode($item['specifications'] ?? '{}', true);
            $item['tax_rate'] = $item['tax_rate'] ?? 0;
            $item['currency'] = $item['currency'] ?? 'USD';
            
            // Ensure status columns are consistent
            if (empty($item['status']) && !empty($item['item_status'])) {
                $item['status'] = ($item['item_status'] === 'active') ? 'active' : 'inactive';
            } else {
                $item['status'] = $item['status'] ?? 'active';
            }
            
            error_log('Items edit: Successfully loaded item ID: ' . $id . ', Name: ' . ($item['item_name'] ?? 'N/A') . ' with all fields');
            
        } catch (Exception $e) {
            error_log('Items edit load error: ' . $e->getMessage());
            error_log('Items edit load stack trace: ' . $e->getTraceAsString());
            $this->setFlashMessage('danger', 'Error loading item: ' . $e->getMessage());
            redirect('inventory/items');
        }
        
        $data = [
            'page_title' => 'Edit Item',
            'item' => $item,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/items/edit', $data);
    }
    
    private function syncStockLevelsReorderPoint($itemId, $reorderPoint) {
        try {
            $stockLevels = $this->stockLevelModel->getByItem($itemId);
            foreach ($stockLevels as $stockLevel) {
                $this->stockLevelModel->update($stockLevel['id'], [
                    'reorder_point' => $reorderPoint
                ]);
            }
        } catch (Exception $e) {
            error_log('Items syncStockLevelsReorderPoint error: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete an item
     * Prevents deletion if item has stock or active transactions
     */
    public function delete($id) {
        $this->requirePermission('inventory', 'delete');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('items');
        }
        
        check_csrf(); // CSRF Protection
        
        try {
            $id = intval($id);
            if (!$id) {
                $this->setFlashMessage('danger', 'Invalid item ID.');
                redirect('items');
            }
            
            $item = $this->itemModel->getById($id);
            if (!$item) {
                $this->setFlashMessage('danger', 'Item not found.');
                redirect('items');
            }
            
            // Check if item has stock levels with quantity > 0
            $stockLevels = $this->stockLevelModel->getByItem($id);
            $totalStock = 0;
            foreach ($stockLevels as $level) {
                $totalStock += floatval($level['quantity'] ?? 0);
            }
            
            if ($totalStock > 0) {
                $this->setFlashMessage('danger', 'Cannot delete item with existing stock (' . number_format($totalStock, 2) . ' units). Please adjust stock to zero first.');
                redirect('items/view/' . $id);
            }
            
            // Check for recent transactions (within last 30 days)
            try {
                $recentTransactions = $this->transactionModel->getByItem($id, 1);
                if (!empty($recentTransactions)) {
                    // Soft delete instead - mark as discontinued
                    $this->itemModel->update($id, [
                        'item_status' => 'discontinued',
                        'status' => 'inactive',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $this->activityModel->log(
                        $this->session['user_id'], 
                        'update', 
                        'Items', 
                        'Discontinued item (has transaction history): ' . $item['item_name']
                    );
                    
                    $this->setFlashMessage('warning', 'Item has transaction history and was marked as discontinued instead of deleted.');
                    redirect('items');
                }
            } catch (Exception $e) {
                // If transaction check fails, proceed with delete attempt
                error_log('Items delete transaction check error: ' . $e->getMessage());
            }
            
            // Delete stock level records first
            foreach ($stockLevels as $level) {
                $this->stockLevelModel->delete($level['id']);
            }
            
            // Delete the item
            $deleted = $this->itemModel->delete($id);
            
            if ($deleted) {
                $this->activityModel->log(
                    $this->session['user_id'], 
                    'delete', 
                    'Items', 
                    'Deleted item: ' . $item['item_name'] . ' (SKU: ' . ($item['sku'] ?? 'N/A') . ')'
                );
                
                $this->setFlashMessage('success', 'Item deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete item.');
            }
            
        } catch (Exception $e) {
            error_log('Items delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting item: ' . $e->getMessage());
        }
        
        redirect('items');
    }
}

