<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_takes extends Base_Controller {
    private $stockTakeModel;
    private $stockTakeItemModel;
    private $locationModel;
    private $itemModel;
    private $stockLevelModel;
    private $adjustmentModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->stockTakeModel = $this->loadModel('Stock_take_model');
        $this->stockTakeItemModel = $this->loadModel('Stock_take_item_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->adjustmentModel = $this->loadModel('Stock_adjustment_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $status = $_GET['status'] ?? 'all';
        
        try {
            if ($status === 'all') {
                $stockTakes = $this->stockTakeModel->getAll();
            } else {
                $stockTakes = $this->stockTakeModel->getByStatus($status);
            }
        } catch (Exception $e) {
            $stockTakes = [];
        }
        
        $data = [
            'page_title' => 'Stock Takes',
            'stock_takes' => $stockTakes,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/stock_takes/index', $data);
    }
    
    public function create() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $locationId = intval($_POST['location_id'] ?? 0);
            $scheduledDate = sanitize_input($_POST['scheduled_date'] ?? date('Y-m-d'));
            $type = sanitize_input($_POST['type'] ?? 'full');
            $notes = sanitize_input($_POST['notes'] ?? '');
            
            if ($locationId) {
                try {
                    $stockTakeData = [
                        'stock_take_number' => $this->stockTakeModel->getNextStockTakeNumber(),
                        'location_id' => $locationId,
                        'scheduled_date' => $scheduledDate,
                        'type' => $type,
                        'status' => 'scheduled',
                        'notes' => $notes,
                        'created_by' => $this->session['user_id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $stockTakeId = $this->stockTakeModel->create($stockTakeData);
                    
                    if ($stockTakeId) {
                        // Generate count sheet items
                        $this->generateCountSheet($stockTakeId, $locationId);
                        
                        $this->activityModel->log($this->session['user_id'], 'create', 'Stock Takes', 'Created stock take: ' . $stockTakeData['stock_take_number']);
                        $this->setFlashMessage('success', 'Stock take created successfully.');
                        redirect('inventory/stock-takes/view/' . $stockTakeId);
                    }
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error creating stock take: ' . $e->getMessage());
                }
            } else {
                $this->setFlashMessage('danger', 'Please select a location.');
            }
        }
        
        try {
            $locations = $this->locationModel->getActive();
        } catch (Exception $e) {
            $locations = [];
        }
        
        $data = [
            'page_title' => 'Create Stock Take',
            'locations' => $locations,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/stock_takes/create', $data);
    }
    
    private function generateCountSheet($stockTakeId, $locationId) {
        try {
            // Get all items with stock at this location
            $items = $this->stockLevelModel->getByLocation($locationId);
            
            foreach ($items as $itemStock) {
                if (floatval($itemStock['quantity']) > 0) {
                    $this->stockTakeItemModel->create([
                        'stock_take_id' => $stockTakeId,
                        'item_id' => $itemStock['item_id'],
                        'expected_qty' => floatval($itemStock['quantity']),
                        'counted_qty' => 0,
                        'variance' => 0
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log('Stock_takes generateCountSheet error: ' . $e->getMessage());
        }
    }
    
    public function view($id) {
        try {
            $stockTake = $this->stockTakeModel->getWithDetails($id);
            if (!$stockTake) {
                $this->setFlashMessage('danger', 'Stock take not found.');
                redirect('inventory/stock-takes');
            }
            
            $items = $this->stockTakeItemModel->getByStockTake($id);
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading stock take.');
            redirect('inventory/stock-takes');
        }
        
        $data = [
            'page_title' => 'Stock Take: ' . $stockTake['stock_take_number'],
            'stock_take' => $stockTake,
            'items' => $items,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/stock_takes/view', $data);
    }
    
    public function start($id) {
        $this->requirePermission('inventory', 'update');
        
        try {
            $stockTake = $this->stockTakeModel->getById($id);
            if (!$stockTake) {
                $this->setFlashMessage('danger', 'Stock take not found.');
                redirect('inventory/stock-takes');
            }
            
            $this->stockTakeModel->update($id, [
                'status' => 'in_progress',
                'counted_by' => $this->session['user_id'],
                'started_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->activityModel->log($this->session['user_id'], 'update', 'Stock Takes', 'Started stock take: ' . $stockTake['stock_take_number']);
            $this->setFlashMessage('success', 'Stock take started.');
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error starting stock take: ' . $e->getMessage());
        }
        
        redirect('inventory/stock-takes/view/' . $id);
    }
    
    public function updateCount() {
        $this->requirePermission('inventory', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $itemId = intval($_POST['item_id'] ?? 0);
            $stockTakeId = intval($_POST['stock_take_id'] ?? 0);
            $countedQty = floatval($_POST['counted_qty'] ?? 0);
            
            if ($itemId && $stockTakeId) {
                try {
                    $this->stockTakeItemModel->updateCount($itemId, $countedQty, $this->session['user_id']);
                    echo json_encode(['success' => true]);
                    exit;
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    exit;
                }
            }
        }
        
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    public function complete($id) {
        $this->requirePermission('inventory', 'update');
        
        try {
            $stockTake = $this->stockTakeModel->getById($id);
            if (!$stockTake) {
                $this->setFlashMessage('danger', 'Stock take not found.');
                redirect('inventory/stock-takes');
            }
            
            if ($stockTake['status'] !== 'in_progress') {
                $this->setFlashMessage('danger', 'Stock take is not in progress.');
                redirect('inventory/stock-takes/view/' . $id);
            }
            
            // Get all items with variances
            $items = $this->stockTakeItemModel->getByStockTake($id);
            $hasVariances = false;
            
            foreach ($items as $item) {
                if (floatval($item['variance']) != 0) {
                    $hasVariances = true;
                    break;
                }
            }
            
            $this->stockTakeModel->update($id, [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create adjustments for variances
            if ($hasVariances) {
                foreach ($items as $item) {
                    $variance = floatval($item['variance']);
                    if ($variance != 0) {
                        $this->createAdjustmentFromVariance($id, $item, $stockTake);
                    }
                }
            }
            
            $this->activityModel->log($this->session['user_id'], 'update', 'Stock Takes', 'Completed stock take: ' . $stockTake['stock_take_number']);
            $this->setFlashMessage('success', 'Stock take completed. Adjustments created for variances.');
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error completing stock take: ' . $e->getMessage());
        }
        
        redirect('inventory/stock-takes/view/' . $id);
    }
    
    private function createAdjustmentFromVariance($stockTakeId, $item, $stockTake) {
        try {
            $adjustmentData = [
                'adjustment_number' => $this->adjustmentModel->getNextAdjustmentNumber(),
                'item_id' => $item['item_id'],
                'location_id' => $stockTake['location_id'],
                'quantity_before' => floatval($item['expected_qty']),
                'quantity_after' => floatval($item['counted_qty']),
                'adjustment_qty' => floatval($item['variance']),
                'reason' => 'stock_take',
                'notes' => 'From stock take: ' . $stockTake['stock_take_number'],
                'status' => 'approved',
                'adjusted_by' => $this->session['user_id'],
                'approved_by' => $this->session['user_id'],
                'adjustment_date' => date('Y-m-d H:i:s'),
                'approved_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $adjustmentId = $this->adjustmentModel->create($adjustmentData);
            
            // Update stock level
            if ($adjustmentId) {
                $this->stockLevelModel->updateStock(
                    $item['item_id'],
                    $stockTake['location_id'],
                    $item['variance']
                );
            }
        } catch (Exception $e) {
            error_log('Stock_takes createAdjustmentFromVariance error: ' . $e->getMessage());
        }
    }
}

