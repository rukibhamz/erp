<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_config extends Base_Controller {
    private $taxTypeModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->taxTypeModel = $this->loadModel('Tax_type_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        // Redirect to tax/settings (duplicate functionality removed)
        redirect('tax/settings');
        $this->requirePermission('tax', 'read');
        
        try {
            // Ensure default taxes exist
            $this->ensureDefaultTaxes();
            
            // Get all tax types
            $taxTypes = $this->taxTypeModel->getAll();
        } catch (Exception $e) {
            error_log('Tax_config index error: ' . $e->getMessage());
            $taxTypes = [];
        }
        
        $data = [
            'page_title' => 'Tax Configuration',
            'tax_types' => $taxTypes,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/config/index', $data);
    }
    
    /**
     * Ensure default taxes (VAT, WHT, CIT, PAYE) exist in the database
     */
    private function ensureDefaultTaxes() {
        $defaultTaxes = [
            [
                'code' => 'VAT',
                'name' => 'Value Added Tax',
                'rate' => 7.5,
                'calculation_method' => 'percentage',
                'authority' => 'FIRS',
                'filing_frequency' => 'monthly',
                'description' => 'Value Added Tax (VAT) on goods and services',
                'is_active' => 1,
                'tax_inclusive' => 0
            ],
            [
                'code' => 'WHT',
                'name' => 'Withholding Tax',
                'rate' => 10.0,
                'calculation_method' => 'percentage',
                'authority' => 'FIRS',
                'filing_frequency' => 'monthly',
                'description' => 'Withholding Tax on payments',
                'is_active' => 1,
                'tax_inclusive' => 0
            ],
            [
                'code' => 'CIT',
                'name' => 'Company Income Tax',
                'rate' => 30.0,
                'calculation_method' => 'percentage',
                'authority' => 'FIRS',
                'filing_frequency' => 'annually',
                'description' => 'Company Income Tax on corporate profits',
                'is_active' => 1,
                'tax_inclusive' => 0
            ],
            [
                'code' => 'PAYE',
                'name' => 'Pay As You Earn',
                'rate' => 0, // Progressive - rate is 0
                'calculation_method' => 'progressive',
                'authority' => 'FIRS',
                'filing_frequency' => 'monthly',
                'description' => 'Pay As You Earn - Progressive tax on employee income',
                'is_active' => 1,
                'tax_inclusive' => 0
            ]
        ];
        
        foreach ($defaultTaxes as $taxData) {
            try {
                $existing = $this->taxTypeModel->getByCode($taxData['code']);
                if (!$existing) {
                    // Create if doesn't exist
                    $taxData['created_at'] = date('Y-m-d H:i:s');
                    $this->taxTypeModel->create($taxData);
                    error_log("Created default tax: {$taxData['code']}");
                }
            } catch (Exception $e) {
                error_log("Error ensuring tax {$taxData['code']}: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Update individual tax rate (admin/super_admin only)
     */
    public function updateRate() {
        // Only admin and super_admin can update rates
        $userRole = $this->session['role'] ?? '';
        if (!in_array($userRole, ['super_admin', 'admin'])) {
            $this->setFlashMessage('danger', 'Only administrators can update tax rates.');
            redirect('tax/config');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $taxId = intval($_POST['tax_id'] ?? 0);
            $taxCode = strtoupper(trim($_POST['tax_code'] ?? ''));
            $rate = floatval($_POST['tax_rate'] ?? 0);
            
            // Skip PAYE if it's progressive (rate should remain 0)
            if ($taxCode === 'PAYE') {
                $this->setFlashMessage('info', 'PAYE is progressive - rate cannot be updated.');
                redirect('tax/config');
            }
            
            // Validate rate
            if ($rate < 0 || $rate > 100) {
                $this->setFlashMessage('danger', 'Tax rate must be between 0 and 100.');
                redirect('tax/config');
            }
            
            // Get tax ID from form, or find by code
            if ($taxId <= 0 && !empty($taxCode)) {
                $existingTax = $this->taxTypeModel->getByCode($taxCode);
                if ($existingTax) {
                    $taxId = intval($existingTax['id']);
                }
            }
            
            if ($taxId > 0) {
                // Update existing tax by ID
                try {
                    $currentTax = $this->taxTypeModel->getById($taxId);
                    if ($currentTax) {
                        $currentRate = floatval($currentTax['rate'] ?? 0);
                        
                        // Update the rate
                        $updateData = [
                            'rate' => $rate,
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                        
                        // Update using model's update method
                        $result = $this->taxTypeModel->update($taxId, $updateData);
                        
                        // Update returns rowCount (0 or more), which is fine even if rate didn't change
                        if ($result !== false) {
                            $this->activityModel->log($this->session['user_id'], 'update', 'Tax', "Updated {$taxCode} rate from {$currentRate}% to {$rate}%");
                            $this->setFlashMessage('success', "{$taxCode} rate updated from " . number_format($currentRate, 2) . "% to " . number_format($rate, 2) . "%");
                        } else {
                            $this->setFlashMessage('danger', "Failed to update {$taxCode} rate");
                            error_log("Tax rate update returned false for {$taxCode} (ID: {$taxId})");
                        }
                    } else {
                        $this->setFlashMessage('danger', "Tax {$taxCode} not found (ID: {$taxId})");
                        error_log("Tax not found for ID {$taxId} (code: {$taxCode})");
                    }
                } catch (Exception $e) {
                    error_log("Tax rate update error for {$taxCode} (ID: {$taxId}): " . $e->getMessage());
                    $this->setFlashMessage('danger', "Failed to update {$taxCode}: " . $e->getMessage());
                }
            } else {
                // Tax doesn't exist, create it
                try {
                    $taxData = [
                        'code' => $taxCode,
                        'name' => $this->getTaxName($taxCode),
                        'rate' => $rate,
                        'calculation_method' => 'percentage',
                        'authority' => 'FIRS',
                        'filing_frequency' => $taxCode === 'CIT' ? 'annually' : 'monthly',
                        'description' => $this->getTaxDescription($taxCode),
                        'is_active' => 1,
                        'tax_inclusive' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $newTaxId = $this->taxTypeModel->create($taxData);
                    if ($newTaxId) {
                        $this->activityModel->log($this->session['user_id'], 'create', 'Tax', "Created {$taxCode} with rate {$rate}%");
                        $this->setFlashMessage('success', "{$taxCode} created with rate " . number_format($rate, 2) . "%");
                    } else {
                        $this->setFlashMessage('danger', "Failed to create {$taxCode}");
                        error_log("Tax creation returned false for {$taxCode}");
                    }
                } catch (Exception $e) {
                    error_log("Tax creation error for {$taxCode}: " . $e->getMessage());
                    $this->setFlashMessage('danger', "Failed to create {$taxCode}: " . $e->getMessage());
                }
            }
        }
        
        redirect('tax/config');
    }
    
    /**
     * Update multiple tax rates in batch (admin/super_admin only)
     * 
     * This method handles batch updates of tax rates.
     * For single rate updates, use updateRate() instead.
     * 
     * Can be called from:
     * - Direct POST from tax/config views
     * - Redirect from Tax::settings() (data passed via session)
     */
    public function updateRates() {
        // Only admin and super_admin can update rates
        $userRole = $this->session['role'] ?? '';
        if (!in_array($userRole, ['super_admin', 'admin'])) {
            $this->setFlashMessage('danger', 'Only administrators can update tax rates.');
            redirect('tax/config');
        }
        
        // Check if data was passed via session (from Tax::settings() redirect)
        if (isset($_SESSION['tax_rate_update_data'])) {
            $_POST['tax_rates'] = $_SESSION['tax_rate_update_data']['tax_rates'] ?? [];
            $_POST['tax_ids'] = $_SESSION['tax_rate_update_data']['tax_ids'] ?? [];
            unset($_SESSION['tax_rate_update_data']);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_POST['tax_rates'])) {
            $updated = 0;
            $errors = [];
            $updatedTaxes = [];
            
            if (isset($_POST['tax_rates']) && is_array($_POST['tax_rates'])) {
                foreach ($_POST['tax_rates'] as $code => $rate) {
                    $code = strtoupper(trim($code));
                    $rate = floatval($rate);
                    
                    // Skip PAYE if it's progressive (rate should remain 0)
                    if ($code === 'PAYE') {
                        continue; // PAYE is progressive, don't update rate
                    }
                    
                    // Get tax ID from form, or find by code
                    $taxId = intval($_POST['tax_ids'][$code] ?? 0);
                    
                    // If no ID provided, try to find by code
                    if ($taxId <= 0) {
                        $existingTax = $this->taxTypeModel->getByCode($code);
                        if ($existingTax) {
                            $taxId = intval($existingTax['id']);
                        }
                    }
                    
                    if ($taxId > 0) {
                        // Update existing tax by ID
                        try {
                            $currentTax = $this->taxTypeModel->getById($taxId);
                            if ($currentTax) {
                                $currentRate = floatval($currentTax['rate'] ?? 0);
                                
                                // Always update the rate (even if same value, to ensure it's saved)
                                $updateData = [
                                    'rate' => $rate,
                                    'updated_at' => date('Y-m-d H:i:s')
                                ];
                                
                                // Update using model's update method
                                $result = $this->taxTypeModel->update($taxId, $updateData);
                                
                                // Update returns rowCount (0 or more), which is fine even if rate didn't change
                                // rowCount can be 0 if value is same, but that's still success
                                if ($result !== false) {
                                    $updated++;
                                    $updatedTaxes[] = $code;
                                    error_log("Tax rate updated: {$code} (ID: {$taxId}) from {$currentRate}% to {$rate}%");
                                } else {
                                    $errors[] = "Failed to update {$code}";
                                    error_log("Tax rate update returned false for {$code} (ID: {$taxId})");
                                }
                            } else {
                                $errors[] = "Tax {$code} not found (ID: {$taxId})";
                                error_log("Tax not found for ID {$taxId} (code: {$code})");
                            }
                        } catch (Exception $e) {
                            error_log("Tax rate update error for {$code} (ID: {$taxId}): " . $e->getMessage());
                            $errors[] = "Failed to update {$code}: " . $e->getMessage();
                        }
                    } else {
                        // Tax doesn't exist, create it
                        try {
                            $taxData = [
                                'code' => $code,
                                'name' => $this->getTaxName($code),
                                'rate' => $rate,
                                'calculation_method' => 'percentage',
                                'authority' => 'FIRS',
                                'filing_frequency' => $code === 'CIT' ? 'annually' : 'monthly',
                                'description' => $this->getTaxDescription($code),
                                'is_active' => 1,
                                'tax_inclusive' => 0,
                                'created_at' => date('Y-m-d H:i:s')
                            ];
                            
                            $newTaxId = $this->taxTypeModel->create($taxData);
                            if ($newTaxId) {
                                $updated++;
                                $updatedTaxes[] = $code;
                                error_log("Tax created: {$code} with rate {$rate}%");
                            } else {
                                $errors[] = "Failed to create {$code}";
                                error_log("Tax creation returned false for {$code}");
                            }
                        } catch (Exception $e) {
                            error_log("Tax creation error for {$code}: " . $e->getMessage());
                            $errors[] = "Failed to create {$code}: " . $e->getMessage();
                        }
                    }
                }
            }
            
            if ($updated > 0) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Tax', 'Updated tax rates: ' . implode(', ', $updatedTaxes));
                $this->setFlashMessage('success', "Updated {$updated} tax rate(s) successfully: " . implode(', ', $updatedTaxes));
            } else if (!empty($errors)) {
                $this->setFlashMessage('danger', implode('; ', $errors));
            } else {
                $this->setFlashMessage('info', 'No changes detected.');
            }
        } else {
            // No POST data and no session data - redirect back
            $this->setFlashMessage('info', 'No tax rate data provided.');
        }
        
        // Redirect to appropriate page based on where request came from
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referrer, 'tax/settings') !== false) {
            redirect('tax/settings');
        } else {
            redirect('tax/config');
        }
    }
    
    private function getTaxName($code) {
        $names = [
            'VAT' => 'Value Added Tax',
            'WHT' => 'Withholding Tax',
            'CIT' => 'Company Income Tax',
            'PAYE' => 'Pay As You Earn'
        ];
        return $names[$code] ?? ucfirst($code);
    }
    
    private function getTaxDescription($code) {
        $descriptions = [
            'VAT' => 'Value Added Tax (VAT) on goods and services',
            'WHT' => 'Withholding Tax on payments',
            'CIT' => 'Company Income Tax on corporate profits',
            'PAYE' => 'Pay As You Earn - Progressive tax on employee income'
        ];
        return $descriptions[$code] ?? '';
    }
    
    public function create() {
        $this->requirePermission('tax', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => sanitize_input($_POST['name'] ?? ''),
                'code' => strtoupper(sanitize_input($_POST['code'] ?? '')),
                'rate' => floatval($_POST['rate'] ?? 0),
                'calculation_method' => sanitize_input($_POST['calculation_method'] ?? 'percentage'),
                'authority' => sanitize_input($_POST['authority'] ?? 'FIRS'),
                'filing_frequency' => sanitize_input($_POST['filing_frequency'] ?? 'monthly'),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'tax_inclusive' => isset($_POST['tax_inclusive']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Validate code uniqueness
            $existing = $this->taxTypeModel->getByCode($data['code']);
            if ($existing) {
                $this->setFlashMessage('danger', 'Tax code already exists.');
            } else {
                if ($this->taxTypeModel->create($data)) {
                    $this->activityModel->log($this->session['user_id'], 'create', 'Tax', 'Created tax type: ' . $data['name']);
                    $this->setFlashMessage('success', 'Tax type created successfully.');
                    redirect('tax/config');
                } else {
                    $this->setFlashMessage('danger', 'Failed to create tax type.');
                }
            }
        }
        
        $data = [
            'page_title' => 'Create Tax Type',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/config/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('tax', 'update');
        
        $taxType = $this->taxTypeModel->getById($id);
        if (!$taxType) {
            $this->setFlashMessage('danger', 'Tax type not found.');
            redirect('tax/config');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => sanitize_input($_POST['name'] ?? ''),
                'code' => strtoupper(sanitize_input($_POST['code'] ?? '')),
                'rate' => floatval($_POST['rate'] ?? 0),
                'calculation_method' => sanitize_input($_POST['calculation_method'] ?? 'percentage'),
                'authority' => sanitize_input($_POST['authority'] ?? 'FIRS'),
                'filing_frequency' => sanitize_input($_POST['filing_frequency'] ?? 'monthly'),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'tax_inclusive' => isset($_POST['tax_inclusive']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Validate code uniqueness (excluding current record)
            if ($data['code'] !== $taxType['code']) {
                $existing = $this->taxTypeModel->getByCode($data['code']);
                if ($existing && $existing['id'] != $id) {
                    $this->setFlashMessage('danger', 'Tax code already exists.');
                } else {
                    // Use correct update method signature: update($id, $data)
                    if ($this->taxTypeModel->update($id, $data)) {
                        $this->activityModel->log($this->session['user_id'], 'update', 'Tax', 'Updated tax type: ' . $data['name']);
                        $this->setFlashMessage('success', 'Tax type updated successfully.');
                        redirect('tax/config');
                    } else {
                        $this->setFlashMessage('danger', 'Failed to update tax type.');
                    }
                }
            } else {
                // Use correct update method signature: update($id, $data)
                if ($this->taxTypeModel->update($id, $data)) {
                    $this->activityModel->log($this->session['user_id'], 'update', 'Tax', 'Updated tax type: ' . $data['name']);
                    $this->setFlashMessage('success', 'Tax type updated successfully.');
                    redirect('tax/config');
                } else {
                    $this->setFlashMessage('danger', 'Failed to update tax type.');
                }
            }
        }
        
        $data = [
            'page_title' => 'Edit Tax Type',
            'tax_type' => $taxType,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/config/edit', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('tax', 'delete');
        
        $taxType = $this->taxTypeModel->getById($id);
        if (!$taxType) {
            $this->setFlashMessage('danger', 'Tax type not found.');
            redirect('tax/config');
        }
        
        // Check if tax type is being used
        try {
            $prefix = $this->db->getPrefix();
            $usage = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `{$prefix}vat_transactions` 
                 WHERE tax_type = ?",
                [$taxType['code']]
            );
            
            if (($usage['count'] ?? 0) > 0) {
                $this->setFlashMessage('danger', 'Cannot delete tax type that is in use.');
                redirect('tax/config');
            }
            
            // Instead of delete, deactivate
            if ($this->taxTypeModel->update($id, ['is_active' => 0])) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Tax', 'Deactivated tax type: ' . $taxType['name']);
                $this->setFlashMessage('success', 'Tax type deactivated successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to deactivate tax type.');
            }
        } catch (Exception $e) {
            error_log('Tax_config delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error checking tax type usage.');
        }
        
        redirect('tax/config');
    }
    
    public function toggleStatus($id) {
        $this->requirePermission('tax', 'update');
        
        $taxType = $this->taxTypeModel->getById($id);
        if (!$taxType) {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'message' => 'Tax type not found']);
                exit;
            }
            $this->setFlashMessage('danger', 'Tax type not found.');
            redirect('tax/config');
        }
        
        $newStatus = $taxType['is_active'] ? 0 : 1;
        
        if ($this->taxTypeModel->update($id, ['is_active' => $newStatus])) {
            $this->activityModel->log($this->session['user_id'], 'update', 'Tax', ($newStatus ? 'Activated' : 'Deactivated') . ' tax type: ' . $taxType['name']);
            
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => true, 'is_active' => $newStatus]);
                exit;
            }
            
            $this->setFlashMessage('success', 'Tax type status updated.');
        } else {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
                exit;
            }
            $this->setFlashMessage('danger', 'Failed to update tax type status.');
        }
        
        redirect('tax/config');
    }
}

