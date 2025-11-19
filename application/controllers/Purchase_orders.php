<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase_orders extends Base_Controller {
    private $poModel;
    private $poItemModel;
    private $supplierModel;
    private $itemModel;
    private $grnModel;
    private $stockLevelModel;
    private $transactionModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->poModel = $this->loadModel('Purchase_order_model');
        $this->poItemModel = $this->loadModel('Purchase_order_item_model');
        $this->supplierModel = $this->loadModel('Supplier_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->grnModel = $this->loadModel('Goods_receipt_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->transactionModel = $this->loadModel('Stock_transaction_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $status = $_GET['status'] ?? 'all';
        
        try {
            if ($status === 'all') {
                $pos = $this->poModel->getAll();
            } else {
                $pos = $this->poModel->getByStatus($status);
            }
        } catch (Exception $e) {
            $pos = [];
        }
        
        $data = [
            'page_title' => 'Purchase Orders',
            'purchase_orders' => $pos,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/purchase_orders/index', $data);
    }
    
    public function create() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $supplierId = intval($_POST['supplier_id'] ?? 0);
            $orderDate = sanitize_input($_POST['order_date'] ?? date('Y-m-d'));
            $expectedDate = sanitize_input($_POST['expected_date'] ?? '');
            $items = $_POST['items'] ?? [];
            
            if ($supplierId && !empty($items)) {
                try {
                    $poData = [
                        'po_number' => $this->poModel->getNextPONumber(),
                        'supplier_id' => $supplierId,
                        'order_date' => $orderDate,
                        'expected_date' => $expectedDate ?: null,
                        'status' => 'draft',
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'total_amount' => 0,
                        'notes' => sanitize_input($_POST['notes'] ?? ''),
                        'created_by' => $this->session['user_id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Calculate totals
                    foreach ($items as $item) {
                        $quantity = floatval($item['quantity'] ?? 0);
                        $unitPrice = floatval($item['unit_price'] ?? 0);
                        $poData['subtotal'] += $quantity * $unitPrice;
                    }
                    $poData['total_amount'] = $poData['subtotal'] + $poData['tax_amount'];
                    
                    $poId = $this->poModel->create($poData);
                    
                    if ($poId) {
                        // Create PO items
                        foreach ($items as $item) {
                            if (empty($item['item_id']) || floatval($item['quantity'] ?? 0) <= 0) {
                                continue;
                            }
                            
                            $poItemData = [
                                'po_id' => $poId,
                                'item_id' => intval($item['item_id']),
                                'quantity' => floatval($item['quantity']),
                                'unit_price' => floatval($item['unit_price'] ?? 0),
                                'quantity_received' => 0,
                                'quantity_pending' => floatval($item['quantity']),
                                'line_total' => floatval($item['quantity']) * floatval($item['unit_price'] ?? 0),
                                'notes' => sanitize_input($item['notes'] ?? '')
                            ];
                            
                            $this->poItemModel->create($poItemData);
                        }
                        
                        $this->activityModel->log($this->session['user_id'], 'create', 'Purchase Orders', 'Created PO: ' . $poData['po_number']);
                        $this->setFlashMessage('success', 'Purchase order created successfully.');
                        redirect('inventory/purchase-orders/view/' . $poId);
                    }
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error creating purchase order: ' . $e->getMessage());
                }
            } else {
                $this->setFlashMessage('danger', 'Please select a supplier and add at least one item.');
            }
        }
        
        try {
            $suppliers = $this->supplierModel->getActive();
            $items = $this->itemModel->getInventoryItems();
        } catch (Exception $e) {
            error_log('Purchase_orders create error: ' . $e->getMessage());
            $suppliers = [];
            $items = [];
        }
        
        $selectedItemId = $_GET['item_id'] ?? null;
        
        $data = [
            'page_title' => 'Create Purchase Order',
            'suppliers' => $suppliers,
            'items' => $items,
            'selected_item_id' => $selectedItemId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/purchase_orders/create', $data);
    }
    
    public function view($id) {
        try {
            $po = $this->poModel->getWithSupplier($id);
            if (!$po) {
                $this->setFlashMessage('danger', 'Purchase order not found.');
                redirect('inventory/purchase-orders');
            }
            
            $poItems = $this->poItemModel->getByPO($id);
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading purchase order.');
            redirect('inventory/purchase-orders');
        }
        
        $data = [
            'page_title' => 'Purchase Order: ' . $po['po_number'],
            'po' => $po,
            'po_items' => $poItems,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/purchase_orders/view', $data);
    }
}

