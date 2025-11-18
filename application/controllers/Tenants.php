<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tenants extends Base_Controller {
    private $tenantModel;
    private $leaseModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('locations', 'read');
        $this->tenantModel = $this->loadModel('Tenant_model');
        $this->leaseModel = $this->loadModel('Lease_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $tenants = $this->tenantModel->getAll();
        } catch (Exception $e) {
            $tenants = [];
        }
        
        $data = [
            'page_title' => 'Tenants',
            'tenants' => $tenants,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tenants/index', $data);
    }
    
    public function create() {
        $this->requirePermission('locations', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $data = [
                'tenant_code' => sanitize_input($_POST['tenant_code'] ?? ''),
                'tenant_type' => sanitize_input($_POST['tenant_type'] ?? 'commercial'),
                'business_name' => sanitize_input($_POST['business_name'] ?? ''),
                'business_registration' => sanitize_input($_POST['business_registration'] ?? ''),
                'business_type' => sanitize_input($_POST['business_type'] ?? ''),
                'contact_person' => sanitize_input($_POST['contact_person'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'alternate_phone' => sanitize_input($_POST['alternate_phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? ''),
                'documents' => !empty($_POST['documents']) ? json_encode($_POST['documents']) : null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Validate email
            if (!empty($data['email']) && !validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
                redirect('tenants/create');
            }
            
            // Validate phone
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
                redirect('tenants/create');
            }
            
            // Validate alternate phone
            if (!empty($data['alternate_phone']) && !validate_phone($data['alternate_phone'])) {
                $this->setFlashMessage('danger', 'Invalid alternate phone number.');
                redirect('tenants/create');
            }
            
            // Sanitize phone numbers
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            if (!empty($data['alternate_phone'])) {
                $data['alternate_phone'] = sanitize_phone($data['alternate_phone']);
            }
            
            // Validate contact person name
            if (!empty($data['contact_person']) && !validate_name($data['contact_person'])) {
                $this->setFlashMessage('danger', 'Invalid contact person name.');
                redirect('tenants/create');
            }
            
            // Auto-generate tenant code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['tenant_code'])) {
                $data['tenant_code'] = $this->tenantModel->getNextTenantCode();
            }
            
            $tenantId = $this->tenantModel->create($data);
            
            if ($tenantId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Tenants', 'Created tenant: ' . ($data['business_name'] ?: $data['contact_person']));
                
                // Check if this is an AJAX request (from modal)
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    $tenant = $this->tenantModel->getById($tenantId);
                    echo json_encode([
                        'success' => true,
                        'tenant_id' => $tenantId,
                        'business_name' => $tenant['business_name'] ?? '',
                        'contact_person' => $tenant['contact_person'] ?? '',
                        'message' => 'Tenant created successfully.'
                    ]);
                    exit;
                }
                
                $this->setFlashMessage('success', 'Tenant created successfully.');
                redirect('tenants/view/' . $tenantId);
            } else {
                // Check if this is an AJAX request
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => 'Failed to create tenant.'
                    ]);
                    exit;
                }
                
                $this->setFlashMessage('danger', 'Failed to create tenant.');
            }
        }
        
        $data = [
            'page_title' => 'Create Tenant',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tenants/create', $data);
    }
    
    public function view($id) {
        try {
            $tenant = $this->tenantModel->getWithLeases($id);
            if (!$tenant) {
                $this->setFlashMessage('danger', 'Tenant not found.');
                redirect('tenants');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading tenant.');
            redirect('tenants');
        }
        
        $data = [
            'page_title' => 'Tenant: ' . ($tenant['business_name'] ?: $tenant['contact_person']),
            'tenant' => $tenant,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tenants/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('locations', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $data = [
                'tenant_type' => sanitize_input($_POST['tenant_type'] ?? 'commercial'),
                'business_name' => sanitize_input($_POST['business_name'] ?? ''),
                'business_registration' => sanitize_input($_POST['business_registration'] ?? ''),
                'business_type' => sanitize_input($_POST['business_type'] ?? ''),
                'contact_person' => sanitize_input($_POST['contact_person'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'alternate_phone' => sanitize_input($_POST['alternate_phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? ''),
                'documents' => !empty($_POST['documents']) ? json_encode($_POST['documents']) : null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->tenantModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Tenants', 'Updated tenant: ' . ($data['business_name'] ?: $data['contact_person']));
                $this->setFlashMessage('success', 'Tenant updated successfully.');
                redirect('tenants/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update tenant.');
            }
        }
        
        try {
            $tenant = $this->tenantModel->getById($id);
            if (!$tenant) {
                $this->setFlashMessage('danger', 'Tenant not found.');
                redirect('tenants');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading tenant.');
            redirect('tenants');
        }
        
        $data = [
            'page_title' => 'Edit Tenant',
            'tenant' => $tenant,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tenants/edit', $data);
    }
}

