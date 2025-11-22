<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fixed_assets extends Base_Controller {
    private $assetModel;
    private $itemModel;
    private $locationModel;
    private $supplierModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->assetModel = $this->loadModel('Fixed_asset_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->supplierModel = $this->loadModel('Supplier_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $category = $_GET['category'] ?? 'all';
        
        try {
            if ($category === 'all') {
                $assets = $this->assetModel->getAll();
            } else {
                $assets = $this->assetModel->getByCategory($category);
            }
        } catch (Exception $e) {
            $assets = [];
        }
        
        $data = [
            'page_title' => 'Fixed Assets',
            'assets' => $assets,
            'selected_category' => $category,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/assets/index', $data);
    }
    
    public function create() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $assetName = sanitize_input($_POST['asset_name'] ?? '');
            $assetCategory = sanitize_input($_POST['asset_category'] ?? 'equipment');
            $purchaseCost = floatval($_POST['purchase_cost'] ?? 0);
            $purchaseDate = sanitize_input($_POST['purchase_date'] ?? date('Y-m-d'));
            $supplierId = intval($_POST['supplier_id'] ?? 0);
            $locationId = intval($_POST['location_id'] ?? 0);
            $itemId = intval($_POST['item_id'] ?? 0);
            $custodianId = intval($_POST['custodian_id'] ?? 0);
            $depreciationMethod = sanitize_input($_POST['depreciation_method'] ?? 'straight_line');
            $usefulLifeYears = intval($_POST['useful_life_years'] ?? 5);
            $salvageValue = floatval($_POST['salvage_value'] ?? 0);
            $description = sanitize_input($_POST['description'] ?? '');
            
            if ($assetName && $purchaseCost > 0) {
                try {
                    $assetData = [
                        'asset_tag' => $this->assetModel->getNextAssetTag(),
                        'asset_name' => $assetName,
                        'asset_category' => $assetCategory,
                        'item_id' => $itemId ?: null,
                        'supplier_id' => $supplierId ?: null,
                        'location_id' => $locationId ?: null,
                        'custodian_id' => $custodianId ?: null,
                        'purchase_cost' => $purchaseCost,
                        'purchase_date' => $purchaseDate,
                        'current_value' => $purchaseCost,
                        'salvage_value' => $salvageValue,
                        'depreciation_method' => $depreciationMethod,
                        'useful_life_years' => $usefulLifeYears,
                        'description' => $description,
                        'asset_status' => 'active',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $assetId = $this->assetModel->create($assetData);
                    
                    if ($assetId) {
                        $this->activityModel->log($this->session['user_id'], 'create', 'Fixed Assets', 'Created asset: ' . $assetData['asset_tag']);
                        $this->setFlashMessage('success', 'Fixed asset created successfully.');
                        redirect('inventory/assets/view/' . $assetId);
                    }
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error creating asset: ' . $e->getMessage());
                }
            } else {
                $this->setFlashMessage('danger', 'Please fill in all required fields.');
            }
        }
        
        try {
            $items = $this->itemModel->getByType('fixed_asset');
            $locations = $this->locationModel->getActive();
            $suppliers = $this->supplierModel->getActive();
        } catch (Exception $e) {
            $items = [];
            $locations = [];
            $suppliers = [];
        }
        
        $data = [
            'page_title' => 'Create Fixed Asset',
            'items' => $items,
            'locations' => $locations,
            'suppliers' => $suppliers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/assets/create', $data);
    }
    
    public function view($id) {
        try {
            $asset = $this->assetModel->getWithDetails($id);
            if (!$asset) {
                $this->setFlashMessage('danger', 'Asset not found.');
                redirect('inventory/assets');
            }
            
            // Calculate current depreciation
            $depreciation = $this->assetModel->calculateDepreciation($id);
            $asset = $this->assetModel->getWithDetails($id); // Refresh after calculation
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading asset.');
            redirect('inventory/assets');
        }
        
        $data = [
            'page_title' => 'Asset: ' . $asset['asset_tag'],
            'asset' => $asset,
            'depreciation' => $depreciation,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/assets/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('inventory', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $assetName = sanitize_input($_POST['asset_name'] ?? '');
            $assetCategory = sanitize_input($_POST['asset_category'] ?? 'equipment');
            $locationId = intval($_POST['location_id'] ?? 0);
            $custodianId = intval($_POST['custodian_id'] ?? 0);
            $assetStatus = sanitize_input($_POST['asset_status'] ?? 'active');
            $description = sanitize_input($_POST['description'] ?? '');
            
            try {
                $updateData = [
                    'asset_name' => $assetName,
                    'asset_category' => $assetCategory,
                    'location_id' => $locationId ?: null,
                    'custodian_id' => $custodianId ?: null,
                    'asset_status' => $assetStatus,
                    'description' => $description,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $this->assetModel->update($id, $updateData);
                
                $asset = $this->assetModel->getById($id);
                $this->activityModel->log($this->session['user_id'], 'update', 'Fixed Assets', 'Updated asset: ' . $asset['asset_tag']);
                $this->setFlashMessage('success', 'Asset updated successfully.');
                redirect('inventory/assets/view/' . $id);
                
            } catch (Exception $e) {
                $this->setFlashMessage('danger', 'Error updating asset: ' . $e->getMessage());
            }
        }
        
        try {
            $asset = $this->assetModel->getWithDetails($id);
            if (!$asset) {
                $this->setFlashMessage('danger', 'Asset not found.');
                redirect('inventory/assets');
            }
            
            $locations = $this->locationModel->getActive();
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading asset.');
            redirect('inventory/assets');
        }
        
        $data = [
            'page_title' => 'Edit Asset: ' . $asset['asset_tag'],
            'asset' => $asset,
            'locations' => $locations,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/assets/edit', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('inventory', 'delete');
        
        try {
            $asset = $this->assetModel->getById($id);
            if (!$asset) {
                $this->setFlashMessage('danger', 'Asset not found.');
                redirect('inventory/assets');
            }
            
            // Check if asset has associated records (depreciation, maintenance, etc.)
            // TODO: Add validation to prevent deletion if asset has associated records
            
            if ($this->assetModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Fixed Assets', 'Deleted asset: ' . $asset['asset_tag']);
                $this->setFlashMessage('success', 'Asset deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete asset.');
            }
        } catch (Exception $e) {
            error_log('Fixed_assets delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting asset: ' . $e->getMessage());
        }
        
        redirect('inventory/assets');
    }
}

