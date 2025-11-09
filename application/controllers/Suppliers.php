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
            
            // Auto-generate supplier code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['supplier_code'])) {
                $data['supplier_code'] = $this->supplierModel->getNextSupplierCode();
            }
            
            $supplierId = $this->supplierModel->create($data);
            
            if ($supplierId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Suppliers', 'Created supplier: ' . $data['supplier_name']);
                $this->setFlashMessage('success', 'Supplier created successfully.');
                redirect('inventory/suppliers');
            } else {
                $this->setFlashMessage('danger', 'Failed to create supplier.');
            }
        }
        
        $data = [
            'page_title' => 'Create Supplier',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/suppliers/create', $data);
    }
}

