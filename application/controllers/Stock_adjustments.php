<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_adjustments extends Base_Controller {
    private $adjustmentModel;
    private $itemModel;
    private $locationModel;
    private $stockLevelModel;
    private $transactionModel;
    private $transactionModelAccounting;
    private $accountModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->adjustmentModel = $this->loadModel('Stock_adjustment_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->transactionModel = $this->loadModel('Stock_transaction_model');
        $this->transactionModelAccounting = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $status = $_GET['status'] ?? 'all';
        
        try {
            if ($status === 'all') {
                $adjustments = $this->adjustmentModel->getAll();
            } else {
                $adjustments = $this->adjustmentModel->getByStatus($status);
            }
        } catch (Exception $e) {
            $adjustments = [];
        }
        
        $data = [
            'page_title' => 'Stock Adjustments',
            'adjustments' => $adjustments,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/adjustments/index', $data);
    }
    
    public function create() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = intval($_POST['item_id'] ?? 0);
            $locationId = intval($_POST['location_id'] ?? 0);
            $quantityAfter = floatval($_POST['quantity_after'] ?? 0);
            $reason = sanitize_input($_POST['reason'] ?? 'correction');
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($itemId && $locationId) {
                try {
                    // Get current stock
                    $currentStock = $this->stockLevelModel->getItemStock($itemId, $locationId);
                    $quantityBefore = floatval($currentStock['quantity'] ?? 0);
                    $adjustmentQty = $quantityAfter - $quantityBefore;
                    
                    $adjustmentData = [
                        'adjustment_number' => $this->adjustmentModel->getNextAdjustmentNumber(),
                        'item_id' => $itemId,
                        'location_id' => $locationId,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityAfter,
                        'adjustment_qty' => $adjustmentQty,
                        'reason' => $reason,
                        'notes' => $notes,
                        'status' => 'pending',
                        'adjusted_by' => $this->session['user_id'],
                        'adjustment_date' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $adjustmentId = $this->adjustmentModel->create($adjustmentData);
                    
                    if ($adjustmentId) {
                        $this->activityModel->log($this->session['user_id'], 'create', 'Stock Adjustments', 'Created adjustment: ' . $adjustmentData['adjustment_number']);
                        $this->setFlashMessage('success', 'Stock adjustment created successfully. Pending approval.');
                        redirect('inventory/adjustments/view/' . $adjustmentId);
                    }
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error creating adjustment: ' . $e->getMessage());
                }
            } else {
                $this->setFlashMessage('danger', 'Please select item and location.');
            }
        }
        
        try {
            // Get all active inventory items
            $items = $this->itemModel->getByType('inventory');
            // Fallback to all active items if no inventory items found
            if (empty($items)) {
                $allItems = $this->itemModel->getAllActive();
                $items = array_filter($allItems, function($item) {
                    return ($item['item_type'] ?? '') === 'inventory';
                });
            }
            $locations = $this->locationModel->getActive();
        } catch (Exception $e) {
            $items = [];
            $locations = [];
        }
        
        $selectedItemId = $_GET['item_id'] ?? null;
        $selectedLocationId = $_GET['location_id'] ?? null;
        
        $data = [
            'page_title' => 'Create Stock Adjustment',
            'items' => $items,
            'locations' => $locations,
            'selected_item_id' => $selectedItemId,
            'selected_location_id' => $selectedLocationId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/adjustments/create', $data);
    }
    
    public function view($id) {
        try {
            $adjustment = $this->adjustmentModel->getById($id);
            if (!$adjustment) {
                $this->setFlashMessage('danger', 'Adjustment not found.');
                redirect('inventory/adjustments');
            }
            
            // Get item and location details
            $item = $this->itemModel->getById($adjustment['item_id']);
            $location = $this->locationModel->getById($adjustment['location_id']);
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading adjustment.');
            redirect('inventory/adjustments');
        }
        
        $data = [
            'page_title' => 'Adjustment: ' . $adjustment['adjustment_number'],
            'adjustment' => $adjustment,
            'item' => $item,
            'location' => $location,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/adjustments/view', $data);
    }
    
    public function approve($id) {
        $this->requirePermission('inventory', 'update');
        
        try {
            $adjustment = $this->adjustmentModel->getById($id);
            if (!$adjustment) {
                $this->setFlashMessage('danger', 'Adjustment not found.');
                redirect('inventory/adjustments');
            }
            
            if ($adjustment['status'] !== 'pending') {
                $this->setFlashMessage('danger', 'Adjustment is not pending approval.');
                redirect('inventory/adjustments/view/' . $id);
            }
            
            // Update adjustment status
            $this->adjustmentModel->update($id, [
                'status' => 'approved',
                'approved_by' => $this->session['user_id'],
                'approved_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update stock level
            $this->stockLevelModel->updateStock(
                $adjustment['item_id'],
                $adjustment['location_id'],
                $adjustment['adjustment_qty']
            );
            
            // Create stock transaction
            $item = $this->itemModel->getById($adjustment['item_id']);
            $unitCost = floatval($item['average_cost'] ?? 0);
            
            $transactionData = [
                'transaction_type' => 'adjustment',
                'item_id' => $adjustment['item_id'],
                'location_to_id' => $adjustment['location_id'],
                'quantity' => abs($adjustment['adjustment_qty']),
                'unit_cost' => $unitCost,
                'total_cost' => abs($adjustment['adjustment_qty']) * $unitCost,
                'reference_type' => 'stock_adjustment',
                'reference_id' => $id,
                'reference_number' => $adjustment['adjustment_number'],
                'notes' => 'Adjustment: ' . $adjustment['reason'],
                'transaction_date' => $adjustment['adjustment_date'],
                'created_by' => $this->session['user_id']
            ];
            
            $this->transactionModel->create($transactionData);
            
            // Post to accounting if adjustment increases stock
            if ($adjustment['adjustment_qty'] > 0) {
                $this->postAdjustmentToAccounting($id, $transactionData, $item);
            }
            
            $this->activityModel->log($this->session['user_id'], 'update', 'Stock Adjustments', 'Approved adjustment: ' . $adjustment['adjustment_number']);
            $this->setFlashMessage('success', 'Adjustment approved and stock updated.');
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error approving adjustment: ' . $e->getMessage());
        }
        
        redirect('inventory/adjustments/view/' . $id);
    }
    
    public function getStockLevel() {
        header('Content-Type: application/json');
        
        $itemId = intval($_GET['item_id'] ?? 0);
        $locationId = intval($_GET['location_id'] ?? 0);
        
        if ($itemId && $locationId) {
            try {
                $stock = $this->stockLevelModel->getItemStock($itemId, $locationId);
                echo json_encode([
                    'quantity' => floatval($stock['quantity'] ?? 0),
                    'available_qty' => floatval($stock['available_qty'] ?? 0),
                    'reserved_qty' => floatval($stock['reserved_qty'] ?? 0)
                ]);
            } catch (Exception $e) {
                echo json_encode(['quantity' => 0, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['quantity' => 0]);
        }
        exit;
    }
    
    private function postAdjustmentToAccounting($adjustmentId, $transactionData, $item) {
        try {
            // Find Inventory Asset account
            $assetAccounts = $this->accountModel->getByType('Assets');
            $inventoryAccount = null;
            foreach ($assetAccounts as $acc) {
                if (stripos($acc['account_name'], 'inventory') !== false) {
                    $inventoryAccount = $acc;
                    break;
                }
            }
            
            // Find Inventory Adjustment Expense account
            $expenseAccounts = $this->accountModel->getByType('Expenses');
            $adjustmentExpenseAccount = null;
            foreach ($expenseAccounts as $acc) {
                if (stripos($acc['account_name'], 'adjustment') !== false || 
                    stripos($acc['account_name'], 'inventory') !== false) {
                    $adjustmentExpenseAccount = $acc;
                    break;
                }
            }
            if (!$adjustmentExpenseAccount && !empty($expenseAccounts)) {
                $adjustmentExpenseAccount = $expenseAccounts[0];
            }
            
            if (!$inventoryAccount || !$adjustmentExpenseAccount) {
                error_log('Accounts not found for adjustment accounting entry.');
                return;
            }
            
            // Debit Inventory
            $this->transactionModelAccounting->create([
                'transaction_number' => $transactionData['reference_number'] . '-INV',
                'transaction_date' => $transactionData['transaction_date'],
                'transaction_type' => 'stock_adjustment',
                'reference_id' => $adjustmentId,
                'reference_type' => 'stock_adjustment',
                'account_id' => $inventoryAccount['id'],
                'description' => 'Stock adjustment - ' . $item['item_name'],
                'debit' => $transactionData['total_cost'],
                'credit' => 0,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($inventoryAccount['id'], $transactionData['total_cost'], 'debit');
            
            // Credit Adjustment Expense
            $this->transactionModelAccounting->create([
                'transaction_number' => $transactionData['reference_number'] . '-EXP',
                'transaction_date' => $transactionData['transaction_date'],
                'transaction_type' => 'stock_adjustment',
                'reference_id' => $adjustmentId,
                'reference_type' => 'stock_adjustment',
                'account_id' => $adjustmentExpenseAccount['id'],
                'description' => 'Stock adjustment expense - ' . $item['item_name'],
                'debit' => 0,
                'credit' => $transactionData['total_cost'],
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($adjustmentExpenseAccount['id'], $transactionData['total_cost'], 'credit');
        } catch (Exception $e) {
            error_log('Stock_adjustments postAdjustmentToAccounting error: ' . $e->getMessage());
        }
    }
}

