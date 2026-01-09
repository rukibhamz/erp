<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fixed_assets extends Base_Controller {
    private $assetModel;
    private $itemModel;
    private $locationModel;
    private $supplierModel;
    private $activityModel;
    private $accountModel;
    private $transactionService;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->assetModel = $this->loadModel('Fixed_asset_model');
        $this->itemModel = $this->loadModel('Item_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->supplierModel = $this->loadModel('Supplier_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->accountModel = $this->loadModel('Account_model');
        
        // Load Transaction Service
        require_once BASEPATH . 'services/Transaction_service.php';
        $this->transactionService = new Transaction_service();
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
                        // Post asset acquisition to accounting
                        $paymentStatus = sanitize_input($_POST['payment_status'] ?? 'paid');
                        $assetData['payment_status'] = $paymentStatus;
                        $this->postAssetAcquisition($assetId, $assetData);
                        
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
            $financialRecords = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "journal_entries` 
                 WHERE (reference_type = 'fixed_asset_acquisition' OR reference_type = 'asset_disposal' OR reference_type = 'depreciation')
                 AND reference_id = ?",
                [$id]
            );

            if ($financialRecords && $financialRecords['count'] > 0) {
                $this->setFlashMessage('danger', 'Cannot delete asset with associated financial records. Please dispose of the asset instead.');
                redirect('inventory/assets/view/' . $id);
            }

            // Check if asset is disposed (preserve history)
            if ($asset['asset_status'] === 'disposed') {
                $this->setFlashMessage('danger', 'Cannot delete disposed asset. History must be preserved.');
                redirect('inventory/assets/view/' . $id);
            }
            
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
    
    /**
     * Post asset acquisition to accounting
     */
    private function postAssetAcquisition($assetId, $assetData) {
        try {
            // Determine asset account based on category
            $assetAccountCode = $this->getAssetAccountCode($assetData['asset_category']);
            $assetAccount = $this->accountModel->getByCode($assetAccountCode);
            
            if (!$assetAccount) {
                // Fallback to generic Fixed Assets (1500)
                $assetAccount = $this->accountModel->getByCode('1500');
            }
            
            if (!$assetAccount) {
                // Ultimate fallback - search by type
                $assetAccounts = $this->accountModel->getByType('Assets');
                foreach ($assetAccounts as $acc) {
                    if (stripos($acc['account_name'], 'fixed') !== false || 
                        stripos($acc['account_name'], 'asset') !== false) {
                        $assetAccount = $acc;
                        break;
                    }
                }
            }
            
            // Determine payment account (Cash or AP)
            $paymentAccount = null;
            if ($assetData['payment_status'] === 'paid') {
                // Cash payment
                $paymentAccount = $this->accountModel->getByCode('1000');
                if (!$paymentAccount) {
                    $assetAccounts = $this->accountModel->getByType('Assets');
                    foreach ($assetAccounts as $acc) {
                        if (stripos($acc['account_name'], 'cash') !== false) {
                            $paymentAccount = $acc;
                            break;
                        }
                    }
                }
            } else {
                // On credit - Accounts Payable
                $paymentAccount = $this->accountModel->getByCode('2100');
                if (!$paymentAccount) {
                    $liabilityAccounts = $this->accountModel->getByType('Liabilities');
                    foreach ($liabilityAccounts as $acc) {
                        if (stripos($acc['account_name'], 'payable') !== false) {
                            $paymentAccount = $acc;
                            break;
                        }
                    }
                }
            }
            
            if ($assetAccount && $paymentAccount) {
                $journalData = [
                    'date' => $assetData['purchase_date'],
                    'reference_type' => 'fixed_asset_acquisition',
                    'reference_id' => $assetId,
                    'description' => 'Asset Acquisition - ' . $assetData['asset_tag'],
                    'journal_type' => 'general',
                    'entries' => [
                        // Debit Fixed Asset
                        [
                            'account_id' => $assetAccount['id'],
                            'debit' => $assetData['purchase_cost'],
                            'credit' => 0.00,
                            'description' => 'Fixed Asset - ' . $assetData['asset_name']
                        ],
                        // Credit Cash or AP
                        [
                            'account_id' => $paymentAccount['id'],
                            'debit' => 0.00,
                            'credit' => $assetData['purchase_cost'],
                            'description' => $assetData['payment_status'] === 'paid' ? 'Cash Payment' : 'Accounts Payable'
                        ]
                    ],
                    'created_by' => $this->session['user_id'],
                    'auto_post' => true
                ];
                
                $this->transactionService->postJournalEntry($journalData);
            }
            
        } catch (Exception $e) {
            error_log('Fixed_assets postAssetAcquisition error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get asset account code based on category
     */
    private function getAssetAccountCode($category) {
        $mapping = [
            'building' => '1500',
            'furniture' => '1510',
            'equipment' => '1520',
            'vehicle' => '1530',
            'computer' => '1540',
            'leasehold' => '1550'
        ];
        
        return $mapping[$category] ?? '1500';
    }
    
    /**
     * Calculate and post monthly depreciation for all active assets
     */
    public function calculateDepreciation() {
        $this->requirePermission('inventory', 'update');
        
        try {
            // Get all active assets
            $assets = $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . "fixed_assets` 
                 WHERE asset_status = 'active' 
                 AND depreciation_method != 'none'"
            );
            
            $totalDepreciation = 0;
            $assetsDepreciated = 0;
            
            foreach ($assets as $asset) {
                $monthlyDepreciation = $this->calculateMonthlyDepreciation($asset);
                
                if ($monthlyDepreciation > 0) {
                    // Update asset current value
                    $newValue = max(
                        $asset['current_value'] - $monthlyDepreciation,
                        $asset['salvage_value']
                    );
                    
                    $this->assetModel->update($asset['id'], [
                        'current_value' => $newValue,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $totalDepreciation += $monthlyDepreciation;
                    $assetsDepreciated++;
                }
            }
            
            // Post consolidated depreciation entry
            if ($totalDepreciation > 0) {
                $this->postDepreciationEntry($totalDepreciation);
            }
            
            $this->activityModel->log(
                $this->session['user_id'], 
                'update', 
                'Fixed Assets', 
                'Calculated depreciation for ' . $assetsDepreciated . ' assets: ' . format_currency($totalDepreciation)
            );
            
            $this->setFlashMessage('success', 
                'Depreciation calculated and posted: ' . format_currency($totalDepreciation) . 
                ' for ' . $assetsDepreciated . ' assets.'
            );
            
        } catch (Exception $e) {
            error_log('Fixed_assets calculateDepreciation error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error calculating depreciation: ' . $e->getMessage());
        }
        
        redirect('inventory/assets');
    }
    
    /**
     * Calculate monthly depreciation for a single asset
     */
    private function calculateMonthlyDepreciation($asset) {
        // Check if asset has reached salvage value
        if ($asset['current_value'] <= $asset['salvage_value']) {
            return 0;
        }
        
        if ($asset['depreciation_method'] === 'straight_line') {
            $depreciableAmount = $asset['purchase_cost'] - $asset['salvage_value'];
            $totalMonths = $asset['useful_life_years'] * 12;
            $monthlyDepreciation = $depreciableAmount / $totalMonths;
            
            // Don't depreciate below salvage value
            $maxDepreciation = $asset['current_value'] - $asset['salvage_value'];
            return min($monthlyDepreciation, $maxDepreciation);
        }
        
        // Add other depreciation methods here (declining balance, etc.)
        return 0;
    }
    
    /**
     * Post depreciation entry to accounting
     */
    private function postDepreciationEntry($amount) {
        try {
            // Get Depreciation Expense account (6200)
            $expenseAccount = $this->accountModel->getByCode('6200');
            if (!$expenseAccount) {
                $expenseAccounts = $this->accountModel->getByType('Expenses');
                foreach ($expenseAccounts as $acc) {
                    if (stripos($acc['account_name'], 'depreciation') !== false) {
                        $expenseAccount = $acc;
                        break;
                    }
                }
            }
            
            // Get Accumulated Depreciation account (1590)
            $accumulatedDepAccount = $this->accountModel->getByCode('1590');
            if (!$accumulatedDepAccount) {
                $assetAccounts = $this->accountModel->getByType('Assets');
                foreach ($assetAccounts as $acc) {
                    if (stripos($acc['account_name'], 'accumulated') !== false ||
                        stripos($acc['account_name'], 'depreciation') !== false) {
                        $accumulatedDepAccount = $acc;
                        break;
                    }
                }
            }
            
            if ($expenseAccount && $accumulatedDepAccount) {
                $journalData = [
                    'date' => date('Y-m-d'),
                    'reference_type' => 'depreciation',
                    'reference_id' => 0,
                    'description' => 'Monthly Depreciation - ' . date('F Y'),
                    'journal_type' => 'general',
                    'entries' => [
                        // Debit Depreciation Expense
                        [
                            'account_id' => $expenseAccount['id'],
                            'debit' => $amount,
                            'credit' => 0.00,
                            'description' => 'Depreciation Expense'
                        ],
                        // Credit Accumulated Depreciation
                        [
                            'account_id' => $accumulatedDepAccount['id'],
                            'debit' => 0.00,
                            'credit' => $amount,
                            'description' => 'Accumulated Depreciation'
                        ]
                    ],
                    'created_by' => $this->session['user_id'],
                    'auto_post' => true
                ];
                
                $this->transactionService->postJournalEntry($journalData);
            }
            
        } catch (Exception $e) {
            error_log('Fixed_assets postDepreciationEntry error: ' . $e->getMessage());
        }
    }
    
    /**
     * Dispose of an asset (sale, scrap, retirement)
     */
    public function dispose($id) {
        $this->requirePermission('inventory', 'delete');
        
        try {
            $asset = $this->assetModel->getById($id);
            if (!$asset) {
                $this->setFlashMessage('danger', 'Asset not found.');
                redirect('inventory/assets');
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                check_csrf();
                
                $disposalDate = sanitize_input($_POST['disposal_date'] ?? date('Y-m-d'));
                $disposalMethod = sanitize_input($_POST['disposal_method'] ?? 'sold');
                $saleProceeds = floatval($_POST['sale_proceeds'] ?? 0);
                $notes = sanitize_input($_POST['notes'] ?? '');
                
                // Calculate accumulated depreciation
                $accumulatedDep = $asset['purchase_cost'] - $asset['current_value'];
                
                // Calculate gain/loss
                $bookValue = $asset['current_value'];
                $gainLoss = $saleProceeds - $bookValue;
                
                // Post disposal entry
                $this->postAssetDisposal($asset, $disposalDate, $saleProceeds, $accumulatedDep, $gainLoss);
                
                // Update asset status
                $this->assetModel->update($id, [
                    'asset_status' => 'disposed',
                    'disposal_date' => $disposalDate,
                    'disposal_method' => $disposalMethod,
                    'sale_proceeds' => $saleProceeds,
                    'disposal_notes' => $notes,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $this->activityModel->log(
                    $this->session['user_id'], 
                    'delete', 
                    'Fixed Assets', 
                    'Disposed asset: ' . $asset['asset_tag'] . ' - ' . $disposalMethod
                );
                
                $this->setFlashMessage('success', 'Asset disposed successfully.');
                redirect('inventory/assets');
            }
            
        } catch (Exception $e) {
            error_log('Fixed_assets dispose error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error disposing asset: ' . $e->getMessage());
            redirect('inventory/assets');
        }
        
        $data = [
            'page_title' => 'Dispose Asset',
            'asset' => $asset,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/assets/dispose', $data);
    }
    
    /**
     * Post asset disposal to accounting
     */
    private function postAssetDisposal($asset, $disposalDate, $saleProceeds, $accumulatedDep, $gainLoss) {
        try {
            // Get asset account
            $assetAccountCode = $this->getAssetAccountCode($asset['asset_category']);
            $assetAccount = $this->accountModel->getByCode($assetAccountCode);
            if (!$assetAccount) {
                $assetAccount = $this->accountModel->getByCode('1500');
            }
            
            // Get cash account
            $cashAccount = $this->accountModel->getByCode('1000');
            
            // Get accumulated depreciation account
            $accDepAccount = $this->accountModel->getByCode('1590');
            
            // Get gain/loss account
            $gainLossAccount = null;
            if ($gainLoss >= 0) {
                $gainLossAccount = $this->accountModel->getByCode('4900'); // Gain on Asset Disposal
                if (!$gainLossAccount) {
                    $revenueAccounts = $this->accountModel->getByType('Revenue');
                    foreach ($revenueAccounts as $acc) {
                        if (stripos($acc['account_name'], 'gain') !== false) {
                            $gainLossAccount = $acc;
                            break;
                        }
                    }
                }
            } else {
                $gainLossAccount = $this->accountModel->getByCode('7000'); // Loss on Asset Disposal
                if (!$gainLossAccount) {
                    $expenseAccounts = $this->accountModel->getByType('Expenses');
                    foreach ($expenseAccounts as $acc) {
                        if (stripos($acc['account_name'], 'loss') !== false) {
                            $gainLossAccount = $acc;
                            break;
                        }
                    }
                }
            }
            
            if ($assetAccount && $cashAccount && $accDepAccount) {
                $entries = [];
                
                // DR: Cash (proceeds)
                if ($saleProceeds > 0) {
                    $entries[] = [
                        'account_id' => $cashAccount['id'],
                        'debit' => $saleProceeds,
                        'credit' => 0.00,
                        'description' => 'Sale Proceeds'
                    ];
                }
                
                // DR: Accumulated Depreciation
                if ($accumulatedDep > 0) {
                    $entries[] = [
                        'account_id' => $accDepAccount['id'],
                        'debit' => $accumulatedDep,
                        'credit' => 0.00,
                        'description' => 'Accumulated Depreciation Removal'
                    ];
                }
                
                // CR: Fixed Asset (original cost)
                $entries[] = [
                    'account_id' => $assetAccount['id'],
                    'debit' => 0.00,
                    'credit' => $asset['purchase_cost'],
                    'description' => 'Asset Disposal - ' . $asset['asset_tag']
                ];
                
                // Add gain/loss entry
                if ($gainLossAccount && abs($gainLoss) > 0.01) {
                    if ($gainLoss > 0) {
                        // Gain - Credit
                        $entries[] = [
                            'account_id' => $gainLossAccount['id'],
                            'debit' => 0.00,
                            'credit' => abs($gainLoss),
                            'description' => 'Gain on Asset Disposal'
                        ];
                    } else {
                        // Loss - Debit
                        $entries[] = [
                            'account_id' => $gainLossAccount['id'],
                            'debit' => abs($gainLoss),
                            'credit' => 0.00,
                            'description' => 'Loss on Asset Disposal'
                        ];
                    }
                }
                
                $journalData = [
                    'date' => $disposalDate,
                    'reference_type' => 'asset_disposal',
                    'reference_id' => $asset['id'],
                    'description' => 'Asset Disposal - ' . $asset['asset_tag'],
                    'journal_type' => 'general',
                    'entries' => $entries,
                    'created_by' => $this->session['user_id'],
                    'auto_post' => true
                ];
                
                $this->transactionService->postJournalEntry($journalData);
            }
            
        } catch (Exception $e) {
            error_log('Fixed_assets postAssetDisposal error: ' . $e->getMessage());
        }
    }
}

