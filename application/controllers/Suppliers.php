<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Suppliers extends Base_Controller {
    private $supplierModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->supplierModel = $this->loadModel('Supplier_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $suppliers = $this->supplierModel->getActive();
        } catch (Exception $e) {
            $suppliers = [];
        }
        
        $data = [
            'page_title' => 'Suppliers',
            'suppliers' => $suppliers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/suppliers/index', $data);
    }
    
    public function create() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $data = [
                'supplier_code' => sanitize_input($_POST['supplier_code'] ?? ''),
                'supplier_name' => sanitize_input($_POST['supplier_name'] ?? ''),
                'contact_person' => sanitize_input($_POST['contact_person'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'payment_terms' => intval($_POST['payment_terms'] ?? 30),
                'lead_time_days' => intval($_POST['lead_time_days'] ?? 0),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Validate required fields
            $errors = [];
            if (empty($data['supplier_name'])) {
                $errors[] = 'Supplier name is required.';
            }
            
            // Validate email if provided
            if (!empty($data['email']) && !validate_email($data['email'])) {
                $errors[] = 'Invalid email address.';
            }
            
            // Validate phone if provided
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $errors[] = 'Invalid phone number. Please enter a valid phone number.';
            }
            
            // Validate contact person name if provided
            if (!empty($data['contact_person']) && !validate_name($data['contact_person'])) {
                $errors[] = 'Invalid contact person name.';
            }
            
            if (!empty($errors)) {
                $this->setFlashMessage('danger', implode('<br>', $errors));
                redirect('inventory/suppliers/create');
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Auto-generate supplier code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['supplier_code'])) {
                try {
                    $data['supplier_code'] = $this->supplierModel->getNextSupplierCode();
                } catch (Exception $e) {
                    $this->setFlashMessage('danger', 'Error generating supplier code: ' . $e->getMessage());
                    redirect('inventory/suppliers/create');
                }
            }
            
            try {
                $supplierId = $this->supplierModel->create($data);
                
                if ($supplierId) {
                    $this->activityModel->log($this->session['user_id'], 'create', 'Suppliers', 'Created supplier: ' . $data['supplier_name']);
                    $this->setFlashMessage('success', 'Supplier created successfully.');
                    redirect('inventory/suppliers/view/' . $supplierId);
                } else {
                    $this->setFlashMessage('danger', 'Failed to create supplier.');
                }
            } catch (Exception $e) {
                error_log('Suppliers create error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Error creating supplier: ' . $e->getMessage());
            }
        }
        
        $data = [
            'page_title' => 'Create Supplier',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/suppliers/create', $data);
    }
    
    public function view($id) {
        try {
            $supplier = $this->supplierModel->getById($id);
            if (!$supplier) {
                $this->setFlashMessage('danger', 'Supplier not found.');
                redirect('inventory/suppliers');
            }
        } catch (Exception $e) {
            error_log('Suppliers view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading supplier.');
            redirect('inventory/suppliers');
        }
        
        $data = [
            'page_title' => 'Supplier: ' . $supplier['supplier_name'],
            'supplier' => $supplier,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/suppliers/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('inventory', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $data = [
                'supplier_name' => sanitize_input($_POST['supplier_name'] ?? ''),
                'contact_person' => sanitize_input($_POST['contact_person'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'payment_terms' => intval($_POST['payment_terms'] ?? 30),
                'lead_time_days' => intval($_POST['lead_time_days'] ?? 0),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Validate required fields
            $errors = [];
            if (empty($data['supplier_name'])) {
                $errors[] = 'Supplier name is required.';
            }
            
            // Validate email if provided
            if (!empty($data['email']) && !validate_email($data['email'])) {
                $errors[] = 'Invalid email address.';
            }
            
            // Validate phone if provided
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $errors[] = 'Invalid phone number. Please enter a valid phone number.';
            }
            
            // Validate contact person name if provided
            if (!empty($data['contact_person']) && !validate_name($data['contact_person'])) {
                $errors[] = 'Invalid contact person name.';
            }
            
            if (!empty($errors)) {
                $this->setFlashMessage('danger', implode('<br>', $errors));
                redirect('inventory/suppliers/edit/' . $id);
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            try {
                if ($this->supplierModel->update($id, $data)) {
                    $supplier = $this->supplierModel->getById($id);
                    $this->activityModel->log($this->session['user_id'], 'update', 'Suppliers', 'Updated supplier: ' . $data['supplier_name']);
                    $this->setFlashMessage('success', 'Supplier updated successfully.');
                    redirect('inventory/suppliers/view/' . $id);
                } else {
                    $this->setFlashMessage('danger', 'Failed to update supplier.');
                }
            } catch (Exception $e) {
                error_log('Suppliers edit error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Error updating supplier: ' . $e->getMessage());
            }
        }
        
        try {
            $supplier = $this->supplierModel->getById($id);
            if (!$supplier) {
                $this->setFlashMessage('danger', 'Supplier not found.');
                redirect('inventory/suppliers');
            }
        } catch (Exception $e) {
            error_log('Suppliers edit load error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading supplier.');
            redirect('inventory/suppliers');
        }
        
        $data = [
            'page_title' => 'Edit Supplier',
            'supplier' => $supplier,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/suppliers/edit', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('inventory', 'delete');
        
        try {
            $supplier = $this->supplierModel->getById($id);
            if (!$supplier) {
                $this->setFlashMessage('danger', 'Supplier not found.');
                redirect('inventory/suppliers');
            }
            
            // Check if supplier has associated purchase orders or goods receipts
            // This would require checking related models - placeholder for now
            // TODO: Add validation to prevent deletion if supplier has active orders
            
            if ($this->supplierModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Suppliers', 'Deleted supplier: ' . $supplier['supplier_name']);
                $this->setFlashMessage('success', 'Supplier deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete supplier.');
            }
        } catch (Exception $e) {
            error_log('Suppliers delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting supplier: ' . $e->getMessage());
        }
        
        redirect('inventory/suppliers');
    }
}

