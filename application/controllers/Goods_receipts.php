<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_receipts extends Base_Controller {
    private $grnModel;
    private $grnItemModel;
    private $poModel;
    private $poItemModel;
    private $supplierModel;
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
        $this->grnModel = $this->loadModel('Goods_receipt_model');
        $this->grnItemModel = $this->loadModel('Goods_receipt_item_model');
        $this->poModel = $this->loadModel('Purchase_order_model');
        $this->poItemModel = $this->loadModel('Purchase_order_item_model');
        $this->supplierModel = $this->loadModel('Supplier_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->transactionModel = $this->loadModel('Stock_transaction_model');
        $this->transactionModelAccounting = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $grns = $this->grnModel->getAll();
        } catch (Exception $e) {
            $grns = [];
        }
        
        $data = [
            'page_title' => 'Goods Receipts',
            'grns' => $grns,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/goods_receipts/index', $data);
    }
    
    public function create() {
        $this->requirePermission('inventory', 'create');
        
        $poId = $_GET['po_id'] ?? null;
        $po = null;
        $poItems = [];
        
        if ($poId) {
            try {
                $po = $this->poModel->getWithSupplier($poId);
                if ($po) {
                    $poItems = $this->poItemModel->getByPO($poId);
                    // Filter to show only pending items
                    $poItems = array_filter($poItems, function($item) {
                        return floatval($item['quantity_pending']) > 0;
                    });
                }
            } catch (Exception $e) {
                // Continue without PO
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $supplierId = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
            $receiptPoId = !empty($_POST['po_id']) ? intval($_POST['po_id']) : null;
            $receiptDate = sanitize_input($_POST['receipt_date'] ?? date('Y-m-d'));
            $locationId = intval($_POST['location_id'] ?? 0);
            $items = $_POST['items'] ?? [];
            
            if ($locationId && !empty($items)) {
                try {
                    $grnData = [
                        'grn_number' => $this->grnModel->getNextGRNNumber(),
                        'po_id' => $receiptPoId,
                        'supplier_id' => $supplierId,
                        'receipt_date' => $receiptDate,
                        'location_id' => $locationId,
                        'received_by' => $this->session['user_id'],
                        'quality_inspection' => sanitize_input($_POST['quality_inspection'] ?? ''),
                        'notes' => sanitize_input($_POST['notes'] ?? ''),
                        'status' => 'completed',
                        'created_by' => $this->session['user_id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $grnId = $this->grnModel->create($grnData);
                    
                    if ($grnId) {
                        // Create GRN items and update stock
                        foreach ($items as $item) {
                            if (empty($item['item_id']) || floatval($item['quantity'] ?? 0) <= 0) {
                                continue;
                            }
                            
                            $itemId = intval($item['item_id']);
                            $quantity = floatval($item['quantity']);
                            $unitCost = floatval($item['unit_cost'] ?? 0);
                            $poItemId = !empty($item['po_item_id']) ? intval($item['po_item_id']) : null;
                            
                            // Create GRN item
                            $grnItemData = [
                                'grn_id' => $grnId,
                                'item_id' => $itemId,
                                'po_item_id' => $poItemId,
                                'quantity' => $quantity,
                                'unit_cost' => $unitCost,
                                'batch_number' => !empty($item['batch_number']) ? sanitize_input($item['batch_number']) : null,
                                'expiry_date' => !empty($item['expiry_date']) ? sanitize_input($item['expiry_date']) : null,
                                'location_id' => $locationId
                            ];
                            
                            $this->grnItemModel->create($grnItemData);
                            
                            // Update PO item received quantity
                            if ($poItemId) {
                                $this->poItemModel->updateReceived($poItemId, $quantity);
                            }
                            
                            // Create stock transaction
                            $transactionData = [
                                'transaction_type' => 'receipt',
                                'item_id' => $itemId,
                                'location_to_id' => $locationId,
                                'quantity' => $quantity,
                                'unit_cost' => $unitCost,
                                'total_cost' => $quantity * $unitCost,
                                'reference_type' => 'goods_receipt',
                                'reference_id' => $grnId,
                                'reference_number' => $grnData['grn_number'],
                                'batch_number' => $grnItemData['batch_number'],
                                'expiry_date' => $grnItemData['expiry_date'],
                                'notes' => 'GRN: ' . $grnData['grn_number'],
                                'transaction_date' => $receiptDate,
                                'created_by' => $this->session['user_id']
                            ];
                            
                            $this->transactionModel->create($transactionData);
                            
                            // Update stock level
                            $this->stockLevelModel->updateStock($itemId, $locationId, $quantity);
                            
                            // Update item average cost
                            $itemData = $this->itemModel->getById($itemId);
                            if ($itemData) {
                                $currentStock = $this->stockLevelModel->getItemStock($itemId);
                                $currentQty = floatval($currentStock['total_qty'] ?? 0);
                                $currentAvgCost = floatval($itemData['average_cost']);
                                
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
                                $this->postGRNToAccounting($grnId, $transactionData, $itemData);
                            }
                        }
                        
                        // Update PO status
                        if ($receiptPoId) {
                            $po = $this->poModel->getById($receiptPoId);
                            if ($po) {
                                $allItems = $this->poItemModel->getByPO($receiptPoId);
                                $allReceived = true;
                                $partialReceived = false;
                                
                                foreach ($allItems as $poItem) {
                                    if (floatval($poItem['quantity_pending']) > 0) {
                                        $allReceived = false;
                                        if (floatval($poItem['quantity_received']) > 0) {
                                            $partialReceived = true;
                                        }
                                    }
                                }
                                
                                $newStatus = $allReceived ? 'received' : ($partialReceived ? 'partial' : $po['status']);
                                $this->poModel->update($receiptPoId, ['status' => $newStatus]);
                            }
                        }
                        
                        $this->activityModel->log($this->session['user_id'], 'create', 'Goods Receipts', 'Created GRN: ' . $grnData['grn_number']);
                        $this->setFlashMessage('success', 'Goods receipt created successfully.');
                        redirect('inventory/goods-receipts/view/' . $grnId);
                    }
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error creating goods receipt: ' . $e->getMessage());
                }
            } else {
                $this->setFlashMessage('danger', 'Please select a location and add at least one item.');
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
            'page_title' => 'Create Goods Receipt',
            'po' => $po,
            'po_items' => $poItems,
            'locations' => $locations,
            'suppliers' => $suppliers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/goods_receipts/create', $data);
    }
    
    public function view($id) {
        try {
            $grn = $this->grnModel->getWithDetails($id);
            if (!$grn) {
                $this->setFlashMessage('danger', 'Goods receipt not found.');
                redirect('inventory/goods-receipts');
            }
            
            $grnItems = $this->grnItemModel->getByGRN($id);
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading goods receipt.');
            redirect('inventory/goods-receipts');
        }
        
        $data = [
            'page_title' => 'Goods Receipt: ' . $grn['grn_number'],
            'grn' => $grn,
            'grn_items' => $grnItems,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/goods_receipts/view', $data);
    }
    
    private function postGRNToAccounting($grnId, $transactionData, $item) {
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
            
            // Find Accounts Payable account
            $liabilityAccounts = $this->accountModel->getByType('Liabilities');
            $apAccount = null;
            foreach ($liabilityAccounts as $acc) {
                if (stripos($acc['account_name'], 'payable') !== false || 
                    stripos($acc['account_name'], 'ap') !== false) {
                    $apAccount = $acc;
                    break;
                }
            }
            if (!$apAccount && !empty($liabilityAccounts)) {
                $apAccount = $liabilityAccounts[0]; // Fallback
            }
            
            if (!$inventoryAccount || !$apAccount) {
                error_log('Inventory or AP account not found for GRN accounting entry.');
                return;
            }
            
            // Debit Inventory Asset
            $this->transactionModelAccounting->create([
                'transaction_number' => $transactionData['reference_number'] . '-INV',
                'transaction_date' => $transactionData['transaction_date'],
                'transaction_type' => 'stock_receipt',
                'reference_id' => $grnId,
                'reference_type' => 'goods_receipt',
                'account_id' => $inventoryAccount['id'],
                'description' => 'GRN - ' . $item['item_name'],
                'debit' => $transactionData['total_cost'],
                'credit' => 0,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($inventoryAccount['id'], $transactionData['total_cost'], 'debit');
            
            // Credit Accounts Payable
            $this->transactionModelAccounting->create([
                'transaction_number' => $transactionData['reference_number'] . '-AP',
                'transaction_date' => $transactionData['transaction_date'],
                'transaction_type' => 'stock_receipt',
                'reference_id' => $grnId,
                'reference_type' => 'goods_receipt',
                'account_id' => $apAccount['id'],
                'description' => 'GRN Payable - ' . $item['item_name'],
                'debit' => 0,
                'credit' => $transactionData['total_cost'],
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($apAccount['id'], $transactionData['total_cost'], 'credit');
        } catch (Exception $e) {
            error_log('Goods_receipts postGRNToAccounting error: ' . $e->getMessage());
        }
    }
}

