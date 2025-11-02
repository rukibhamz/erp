<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_providers extends Base_Controller {
    private $providerModel;
    private $utilityTypeModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->providerModel = $this->loadModel('Utility_provider_model');
        $this->utilityTypeModel = $this->loadModel('Utility_type_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $utilityTypeId = $_GET['utility_type_id'] ?? null;
        
        try {
            if ($utilityTypeId) {
                $providers = $this->providerModel->getByUtilityType($utilityTypeId);
            } else {
                $providers = $this->providerModel->getActive();
            }
            
            $utilityTypes = $this->utilityTypeModel->getActive();
        } catch (Exception $e) {
            $providers = [];
            $utilityTypes = [];
        }
        
        $data = [
            'page_title' => 'Utility Providers',
            'providers' => $providers,
            'utility_types' => $utilityTypes,
            'selected_utility_type_id' => $utilityTypeId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/providers/index', $data);
    }
    
    public function create() {
        $this->requirePermission('utilities', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'provider_name' => sanitize_input($_POST['provider_name'] ?? ''),
                'utility_type_id' => intval($_POST['utility_type_id'] ?? 0),
                'account_number' => sanitize_input($_POST['account_number'] ?? ''),
                'contact_person' => sanitize_input($_POST['contact_person'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'service_areas' => sanitize_input($_POST['service_areas'] ?? ''),
                'payment_terms' => intval($_POST['payment_terms'] ?? 30),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $providerId = $this->providerModel->create($data);
            
            if ($providerId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Utility Providers', 'Created provider: ' . $data['provider_name']);
                $this->setFlashMessage('success', 'Provider created successfully.');
                redirect('utilities/providers/view/' . $providerId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create provider.');
            }
        }
        
        try {
            $utilityTypes = $this->utilityTypeModel->getActive();
        } catch (Exception $e) {
            $utilityTypes = [];
        }
        
        $data = [
            'page_title' => 'Create Utility Provider',
            'utility_types' => $utilityTypes,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/providers/create', $data);
    }
    
    public function view($id) {
        try {
            $provider = $this->providerModel->getWithUtilityType($id);
            if (!$provider) {
                $this->setFlashMessage('danger', 'Provider not found.');
                redirect('utilities/providers');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading provider.');
            redirect('utilities/providers');
        }
        
        $data = [
            'page_title' => 'Provider: ' . $provider['provider_name'],
            'provider' => $provider,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/providers/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('utilities', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'provider_name' => sanitize_input($_POST['provider_name'] ?? ''),
                'utility_type_id' => intval($_POST['utility_type_id'] ?? 0),
                'account_number' => sanitize_input($_POST['account_number'] ?? ''),
                'contact_person' => sanitize_input($_POST['contact_person'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'service_areas' => sanitize_input($_POST['service_areas'] ?? ''),
                'payment_terms' => intval($_POST['payment_terms'] ?? 30),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->providerModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Utility Providers', 'Updated provider: ' . $id);
                $this->setFlashMessage('success', 'Provider updated successfully.');
                redirect('utilities/providers/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update provider.');
            }
        }
        
        try {
            $provider = $this->providerModel->getById($id);
            if (!$provider) {
                $this->setFlashMessage('danger', 'Provider not found.');
                redirect('utilities/providers');
            }
            
            $utilityTypes = $this->utilityTypeModel->getActive();
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading provider.');
            redirect('utilities/providers');
        }
        
        $data = [
            'page_title' => 'Edit Utility Provider',
            'provider' => $provider,
            'utility_types' => $utilityTypes,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/providers/edit', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('utilities', 'delete');
        
        try {
            if ($this->providerModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Utility Providers', 'Deleted provider: ' . $id);
                $this->setFlashMessage('success', 'Provider deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete provider.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error deleting provider: ' . $e->getMessage());
        }
        
        redirect('utilities/providers');
    }
}

