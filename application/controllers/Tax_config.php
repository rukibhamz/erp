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
        $this->requirePermission('tax', 'read');
        
        try {
            $taxTypes = $this->taxTypeModel->getAll();
            
            // Group by authority
            $grouped = [];
            foreach ($taxTypes as $tax) {
                $authority = $tax['authority'] ?? 'Other';
                if (!isset($grouped[$authority])) {
                    $grouped[$authority] = [];
                }
                $grouped[$authority][] = $tax;
            }
        } catch (Exception $e) {
            error_log('Tax_config index error: ' . $e->getMessage());
            $taxTypes = [];
            $grouped = [];
        }
        
        $data = [
            'page_title' => 'Tax Configuration',
            'tax_types' => $taxTypes,
            'grouped_taxes' => $grouped,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/config/index', $data);
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
                    if ($this->taxTypeModel->update($data, "id = ?", [$id])) {
                        $this->activityModel->log($this->session['user_id'], 'update', 'Tax', 'Updated tax type: ' . $data['name']);
                        $this->setFlashMessage('success', 'Tax type updated successfully.');
                        redirect('tax/config');
                    } else {
                        $this->setFlashMessage('danger', 'Failed to update tax type.');
                    }
                }
            } else {
                if ($this->taxTypeModel->update($data, "id = ?", [$id])) {
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
            if ($this->taxTypeModel->update(['is_active' => 0], "id = ?", [$id])) {
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
        
        if ($this->taxTypeModel->update(['is_active' => $newStatus], "id = ?", [$id])) {
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

