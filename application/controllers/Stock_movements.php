<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_movements extends Base_Controller {
    private $transactionModel;
    private $stockLevelModel;
    private $itemModel;
    private $locationModel;
    private $transactionModelAccounting;
    private $accountModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->transactionModel = $this->loadModel('Stock_transaction_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->transactionModelAccounting = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function receive() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = intval($_POST['item_id'] ?? 0);
            $locationId = intval($_POST['location_id'] ?? 0);
            $quantity = floatval($_POST['quantity'] ?? 0);
            $unitCost = floatval($_POST['unit_cost'] ?? 0);
            $referenceType = sanitize_input($_POST['reference_type'] ?? '');
            $referenceId = !empty($_POST['reference_id']) ? intval($_POST['reference_id']) : null;
            $referenceNumber = sanitize_input($_POST['reference_number'] ?? '');
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($itemId && $locationId && $quantity > 0) {
                try {
                    // Create stock transaction
                    $transactionData = [
                        'transaction_type' => 'receipt',
                        'item_id' => $itemId,
                        'location_to_id' => $locationId,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'total_cost' => $quantity * $unitCost,
                        'reference_type' => $referenceType,
                        'reference_id' => $referenceId,
                        'reference_number' => $referenceNumber,
                        'notes' => $notes,
                        'transaction_date' => date('Y-m-d H:i:s'),
                        'created_by' => $this->session['user_id']
                    ];
                    
                    $transactionId = $this->transactionModel->create($transactionData);
                    
                    if ($transactionId) {
                        // Update stock level
                        $this->stockLevelModel->updateStock($itemId, $locationId, $quantity);
                        
                        // Update item average cost (weighted average)
                        $item = $this->itemModel->getById($itemId);
                        if ($item) {
                            $currentStock = $this->stockLevelModel->getItemStock($itemId);
                            $currentQty = floatval($currentStock['total_qty'] ?? 0);
                            $currentAvgCost = floatval($item['average_cost']);
                            
                            if ($currentQty > 0) {
                                $newAvgCost = (($currentAvgCost * ($currentQty - $quantity)) + ($unitCost * $quantity)) / $currentQty;
                            } else {
                                $newAvgCost = $unitCost;
                            }
                            
                            $this->itemModel->update($itemId, [
                                'average_cost' => $newAvgCost,
                                'cost_price' => $unitCost,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                            
                            // Post to accounting
                            $this->postReceiptToAccounting($transactionId, $transactionData, $item);
                        }
                        
                        $this->activityModel->log($this->session['user_id'], 'create', 'Stock Movements', 'Stock received: ' . $transactionData['transaction_number']);
                        $this->setFlashMessage('success', 'Stock received successfully.');
                        redirect('inventory/receive');
                    }
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error receiving stock: ' . $e->getMessage());
                }
            } else {
                $this->setFlashMessage('danger', 'Please fill all required fields.');
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
            $locationsRaw = $this->locationModel->getActive();
            // Map locations for view compatibility
            $locations = [];
            foreach ($locationsRaw as $loc) {
                $mapped = $this->locationModel->mapFieldsForView($loc);
                $locations[] = [
                    'id' => $mapped['id'],
                    'location_name' => $mapped['Location_name'] ?? $mapped['property_name'] ?? 'N/A',
                    'location_code' => $mapped['Location_code'] ?? $mapped['property_code'] ?? ''
                ];
            }
        } catch (Exception $e) {
            $items = [];
            $locations = [];
        }
        
        $selectedItemId = $_GET['item_id'] ?? null;
        
        $data = [
            'page_title' => 'Receive Stock',
            'items' => $items,
            'locations' => $locations,
            'selected_item_id' => $selectedItemId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/movements/receive', $data);
    }
    
    public function issue() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = intval($_POST['item_id'] ?? 0);
            $locationId = intval($_POST['location_id'] ?? 0);
            $quantity = floatval($_POST['quantity'] ?? 0);
            $issueType = sanitize_input($_POST['issue_type'] ?? 'sale');
            $referenceType = sanitize_input($_POST['reference_type'] ?? '');
            $referenceId = !empty($_POST['reference_id']) ? intval($_POST['reference_id']) : null;
            $referenceNumber = sanitize_input($_POST['reference_number'] ?? '');
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($itemId && $locationId && $quantity > 0) {
                try {
                    // Check available stock
                    $stock = $this->stockLevelModel->getItemStock($itemId, $locationId);
                    if (!$stock || floatval($stock['available_qty']) < $quantity) {
                        $this->setFlashMessage('danger', 'Insufficient stock available.');
                        redirect('inventory/issue?item_id=' . $itemId . '&location_id=' . $locationId);
                    }
                    
                    $item = $this->itemModel->getById($itemId);
                    $unitCost = floatval($item['average_cost'] ?? 0);
                    
                    // Create stock transaction
                    $transactionData = [
                        'transaction_type' => 'issue',
                        'item_id' => $itemId,
                        'location_from_id' => $locationId,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'total_cost' => $quantity * $unitCost,
                        'reference_type' => $referenceType,
                        'reference_id' => $referenceId,
                        'reference_number' => $referenceNumber,
                        'notes' => $notes,
                        'transaction_date' => date('Y-m-d H:i:s'),
                        'created_by' => $this->session['user_id']
                    ];
                    
                    $transactionId = $this->transactionModel->create($transactionData);
                    
                    if ($transactionId) {
                        // Update stock level (subtract)
                        $this->stockLevelModel->updateStock($itemId, $locationId, -$quantity);
                        
                        // Post to accounting
                        $this->postIssueToAccounting($transactionId, $transactionData, $item, $issueType);
                        
                        $this->activityModel->log($this->session['user_id'], 'create', 'Stock Movements', 'Stock issued: ' . $transactionData['transaction_number']);
                        $this->setFlashMessage('success', 'Stock issued successfully.');
                        redirect('inventory/issue');
                    }
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error issuing stock: ' . $e->getMessage());
                }
            } else {
                $this->setFlashMessage('danger', 'Please fill all required fields.');
            }
        }
        
        try {
            $items = $this->itemModel->getByType('inventory');
            $locations = $this->locationModel->getActive();
        } catch (Exception $e) {
            $items = [];
            $locations = [];
        }
        
        $selectedItemId = $_GET['item_id'] ?? null;
        $selectedLocationId = $_GET['location_id'] ?? null;
        
        $data = [
            'page_title' => 'Issue Stock',
            'items' => $items,
            'locations' => $locations,
            'selected_item_id' => $selectedItemId,
            'selected_location_id' => $selectedLocationId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/movements/issue', $data);
    }
    
    public function transfer() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = intval($_POST['item_id'] ?? 0);
            $locationFromId = intval($_POST['location_from_id'] ?? 0);
            $locationToId = intval($_POST['location_to_id'] ?? 0);
            $quantity = floatval($_POST['quantity'] ?? 0);
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($itemId && $locationFromId && $locationToId && $quantity > 0 && $locationFromId != $locationToId) {
                try {
                    // Check available stock
                    $stock = $this->stockLevelModel->getItemStock($itemId, $locationFromId);
                    if (!$stock || floatval($stock['available_qty']) < $quantity) {
                        $this->setFlashMessage('danger', 'Insufficient stock available in source location.');
                        redirect('inventory/transfer');
                    }
                    
                    $item = $this->itemModel->getById($itemId);
                    $unitCost = floatval($item['average_cost'] ?? 0);
                    
                    // Create stock transaction
                    $transactionData = [
                        'transaction_type' => 'transfer',
                        'item_id' => $itemId,
                        'location_from_id' => $locationFromId,
                        'location_to_id' => $locationToId,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'total_cost' => $quantity * $unitCost,
                        'notes' => $notes,
                        'transaction_date' => date('Y-m-d H:i:s'),
                        'created_by' => $this->session['user_id']
                    ];
                    
                    $transactionId = $this->transactionModel->create($transactionData);
                    
                    if ($transactionId) {
                        // Update stock levels (subtract from source, add to destination)
                        $this->stockLevelModel->updateStock($itemId, $locationFromId, -$quantity);
                        $this->stockLevelModel->updateStock($itemId, $locationToId, $quantity);
                        
                        $this->activityModel->log($this->session['user_id'], 'create', 'Stock Movements', 'Stock transferred: ' . $transactionData['transaction_number']);
                        $this->setFlashMessage('success', 'Stock transferred successfully.');
                        redirect('inventory/transfer');
                    }
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error transferring stock: ' . $e->getMessage());
                }
            } else {
                $this->setFlashMessage('danger', 'Please fill all required fields correctly.');
            }
        }
        
        try {
            $items = $this->itemModel->getByType('inventory');
            $locations = $this->locationModel->getActive();
        } catch (Exception $e) {
            $items = [];
            $locations = [];
        }
        
        $selectedItemId = $_GET['item_id'] ?? null;
        
        $data = [
            'page_title' => 'Transfer Stock',
            'items' => $items,
            'locations' => $locations,
            'selected_item_id' => $selectedItemId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/movements/transfer', $data);
    }
    
    private function postReceiptToAccounting($transactionId, $transactionData, $item) {
        try {
            // Find Inventory Asset account
            $assetAccounts = $this->accountModel->getByType('Assets');
            $inventoryAccount = null;
            foreach ($assetAccounts as $acc) {
                if (stripos($acc['account_name'], 'inventory') !== false || 
                    stripos($acc['account_name'], 'stock') !== false) {
                    $inventoryAccount = $acc;
                    break;
                }
            }
            if (!$inventoryAccount && !empty($assetAccounts)) {
                $inventoryAccount = $assetAccounts[0]; // Fallback
            }
            
            // Find Cash/Accounts Payable (depending on payment status)
            $cashAccount = null;
            $assetAccounts2 = $this->accountModel->getByType('Assets');
            foreach ($assetAccounts2 as $acc) {
                if (stripos($acc['account_name'], 'cash') !== false || 
                    stripos($acc['account_name'], 'bank') !== false) {
                    $cashAccount = $acc;
                    break;
                }
            }
            
            if (!$inventoryAccount || !$cashAccount) {
                error_log('Inventory or Cash account not found for receipt accounting entry.');
                return;
            }
            
            // Debit Inventory Asset
            $this->transactionModelAccounting->create([
                'transaction_number' => $transactionData['transaction_number'] . '-INV',
                'transaction_date' => $transactionData['transaction_date'],
                'transaction_type' => 'stock_receipt',
                'reference_id' => $transactionId,
                'reference_type' => 'stock_transaction',
                'account_id' => $inventoryAccount['id'],
                'description' => 'Stock receipt - ' . $item['item_name'],
                'debit' => $transactionData['total_cost'],
                'credit' => 0,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($inventoryAccount['id'], $transactionData['total_cost'], 'debit');
            
            // Credit Cash/AP (simplified - using cash for now)
            $this->transactionModelAccounting->create([
                'transaction_number' => $transactionData['transaction_number'] . '-CASH',
                'transaction_date' => $transactionData['transaction_date'],
                'transaction_type' => 'stock_receipt',
                'reference_id' => $transactionId,
                'reference_type' => 'stock_transaction',
                'account_id' => $cashAccount['id'],
                'description' => 'Stock receipt payment - ' . $item['item_name'],
                'debit' => 0,
                'credit' => $transactionData['total_cost'],
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($cashAccount['id'], $transactionData['total_cost'], 'credit');
        } catch (Exception $e) {
            error_log('Stock_movements postReceiptToAccounting error: ' . $e->getMessage());
        }
    }
    
    private function postIssueToAccounting($transactionId, $transactionData, $item, $issueType) {
        try {
            // Find Inventory Asset account
            $assetAccounts = $this->accountModel->getByType('Assets');
            $inventoryAccount = null;
            foreach ($assetAccounts as $acc) {
                if (stripos($acc['account_name'], 'inventory') !== false || 
                    stripos($acc['account_name'], 'stock') !== false) {
                    $inventoryAccount = $acc;
                    break;
                }
            }
            
            // Find COGS or Expense account
            $expenseAccounts = $this->accountModel->getByType('Expenses');
            $cogsAccount = null;
            foreach ($expenseAccounts as $acc) {
                if (stripos($acc['account_name'], 'cogs') !== false || 
                    stripos($acc['account_name'], 'cost of goods') !== false ||
                    stripos($acc['account_name'], 'cost of sales') !== false) {
                    $cogsAccount = $acc;
                    break;
                }
            }
            if (!$cogsAccount && !empty($expenseAccounts)) {
                $cogsAccount = $expenseAccounts[0]; // Fallback
            }
            
            if (!$inventoryAccount || !$cogsAccount) {
                error_log('Inventory or COGS account not found for issue accounting entry.');
                return;
            }
            
            // Credit Inventory Asset
            $this->transactionModelAccounting->create([
                'transaction_number' => $transactionData['transaction_number'] . '-INV',
                'transaction_date' => $transactionData['transaction_date'],
                'transaction_type' => 'stock_issue',
                'reference_id' => $transactionId,
                'reference_type' => 'stock_transaction',
                'account_id' => $inventoryAccount['id'],
                'description' => 'Stock issue - ' . $item['item_name'],
                'debit' => 0,
                'credit' => $transactionData['total_cost'],
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($inventoryAccount['id'], $transactionData['total_cost'], 'credit');
            
            // Debit COGS/Expense
            $this->transactionModelAccounting->create([
                'transaction_number' => $transactionData['transaction_number'] . '-COGS',
                'transaction_date' => $transactionData['transaction_date'],
                'transaction_type' => 'stock_issue',
                'reference_id' => $transactionId,
                'reference_type' => 'stock_transaction',
                'account_id' => $cogsAccount['id'],
                'description' => 'Stock issue COGS - ' . $item['item_name'],
                'debit' => $transactionData['total_cost'],
                'credit' => 0,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($cogsAccount['id'], $transactionData['total_cost'], 'debit');
        } catch (Exception $e) {
            error_log('Stock_movements postIssueToAccounting error: ' . $e->getMessage());
        }
    }
}

