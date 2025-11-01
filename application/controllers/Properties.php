<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Properties extends Base_Controller {
    private $propertyModel;
    private $spaceModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('properties', 'read');
        $this->propertyModel = $this->loadModel('Property_model');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $properties = $this->propertyModel->getAll();
        } catch (Exception $e) {
            $properties = [];
        }
        
        $data = [
            'page_title' => 'Properties',
            'properties' => $properties,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('properties/index', $data);
    }
    
    public function create() {
        $this->requirePermission('properties', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'property_code' => sanitize_input($_POST['property_code'] ?? ''),
                'property_name' => sanitize_input($_POST['property_name'] ?? ''),
                'property_type' => sanitize_input($_POST['property_type'] ?? 'multi_purpose'),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? ''),
                'postal_code' => sanitize_input($_POST['postal_code'] ?? ''),
                'gps_latitude' => !empty($_POST['gps_latitude']) ? floatval($_POST['gps_latitude']) : null,
                'gps_longitude' => !empty($_POST['gps_longitude']) ? floatval($_POST['gps_longitude']) : null,
                'land_area' => !empty($_POST['land_area']) ? floatval($_POST['land_area']) : null,
                'built_area' => !empty($_POST['built_area']) ? floatval($_POST['built_area']) : null,
                'year_built' => !empty($_POST['year_built']) ? intval($_POST['year_built']) : null,
                'year_acquired' => !empty($_POST['year_acquired']) ? intval($_POST['year_acquired']) : null,
                'property_value' => !empty($_POST['property_value']) ? floatval($_POST['property_value']) : null,
                'manager_id' => !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null,
                'status' => sanitize_input($_POST['status'] ?? 'operational'),
                'ownership_status' => sanitize_input($_POST['ownership_status'] ?? 'owned'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (empty($data['property_code'])) {
                $data['property_code'] = $this->propertyModel->getNextPropertyCode();
            }
            
            $propertyId = $this->propertyModel->create($data);
            
            if ($propertyId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Properties', 'Created property: ' . $data['property_name']);
                $this->setFlashMessage('success', 'Property created successfully.');
                redirect('properties/view/' . $propertyId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create property.');
            }
        }
        
        // Load users for manager selection
        $userModel = $this->loadModel('User_model');
        try {
            $managers = $userModel->getByRole(['manager', 'admin', 'super_admin']);
        } catch (Exception $e) {
            $managers = [];
        }
        
        $data = [
            'page_title' => 'Create Property',
            'managers' => $managers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('properties/create', $data);
    }
    
    public function view($id) {
        try {
            $property = $this->propertyModel->getWithSpaces($id);
            if (!$property) {
                $this->setFlashMessage('danger', 'Property not found.');
                redirect('properties');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading property.');
            redirect('properties');
        }
        
        $data = [
            'page_title' => 'Property: ' . $property['property_name'],
            'property' => $property,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('properties/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('properties', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'property_name' => sanitize_input($_POST['property_name'] ?? ''),
                'property_type' => sanitize_input($_POST['property_type'] ?? 'multi_purpose'),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? ''),
                'postal_code' => sanitize_input($_POST['postal_code'] ?? ''),
                'gps_latitude' => !empty($_POST['gps_latitude']) ? floatval($_POST['gps_latitude']) : null,
                'gps_longitude' => !empty($_POST['gps_longitude']) ? floatval($_POST['gps_longitude']) : null,
                'land_area' => !empty($_POST['land_area']) ? floatval($_POST['land_area']) : null,
                'built_area' => !empty($_POST['built_area']) ? floatval($_POST['built_area']) : null,
                'year_built' => !empty($_POST['year_built']) ? intval($_POST['year_built']) : null,
                'year_acquired' => !empty($_POST['year_acquired']) ? intval($_POST['year_acquired']) : null,
                'property_value' => !empty($_POST['property_value']) ? floatval($_POST['property_value']) : null,
                'manager_id' => !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null,
                'status' => sanitize_input($_POST['status'] ?? 'operational'),
                'ownership_status' => sanitize_input($_POST['ownership_status'] ?? 'owned'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->propertyModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Properties', 'Updated property: ' . $data['property_name']);
                $this->setFlashMessage('success', 'Property updated successfully.');
                redirect('properties/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update property.');
            }
        }
        
        try {
            $property = $this->propertyModel->getById($id);
            if (!$property) {
                $this->setFlashMessage('danger', 'Property not found.');
                redirect('properties');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading property.');
            redirect('properties');
        }
        
        $userModel = $this->loadModel('User_model');
        try {
            $managers = $userModel->getByRole(['manager', 'admin', 'super_admin']);
        } catch (Exception $e) {
            $managers = [];
        }
        
        $data = [
            'page_title' => 'Edit Property',
            'property' => $property,
            'managers' => $managers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('properties/edit', $data);
    }
}

