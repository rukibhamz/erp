<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Locations extends Base_Controller {
    private $locationModel;
    private $spaceModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('locations', 'read');
        $this->locationModel = $this->loadModel('Location_model');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $locations = $this->locationModel->getAll();
        } catch (Exception $e) {
            $locations = [];
        }
        
        $data = [
            'page_title' => 'Locations',
            'locations' => $locations,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('locations/index', $data);
    }
    
    public function create() {
        $this->requirePermission('locations', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            
            $data = [
                'property_code' => sanitize_input($_POST['Location_code'] ?? $_POST['property_code'] ?? ''),
                'property_name' => sanitize_input($_POST['Location_name'] ?? $_POST['property_name'] ?? ''),
                'property_type' => sanitize_input($_POST['Location_type'] ?? $_POST['property_type'] ?? 'multi_purpose'),
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
                'property_value' => !empty($_POST['Location_value']) ? floatval($_POST['Location_value']) : (!empty($_POST['property_value']) ? floatval($_POST['property_value']) : null),
                'manager_id' => !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null,
                'status' => sanitize_input($_POST['status'] ?? 'operational'),
                'ownership_status' => sanitize_input($_POST['ownership_status'] ?? 'owned'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Auto-generate property code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['property_code'])) {
                $data['property_code'] = $this->locationModel->getNextPropertyCode();
            }
            
            $locationId = $this->locationModel->create($data);
            
            if ($locationId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Locations', 'Created location: ' . $data['property_name']);
                $this->setFlashMessage('success', 'Location created successfully.');
                redirect('locations/view/' . $locationId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create location.');
            }
        }
        
        // Load users for manager selection
        $userModel = $this->loadModel('User_model');
        try {
            $allUsers = $userModel->getAll();
            $managers = array_filter($allUsers, function($user) {
                return in_array($user['role'], ['manager', 'admin', 'super_admin']);
            });
        } catch (Exception $e) {
            $managers = [];
        }
        
        $data = [
            'page_title' => 'Create Location',
            'managers' => $managers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('locations/create', $data);
    }
    
    public function view($id) {
        try {
            $location = $this->locationModel->getWithSpaces($id);
            if (!$location) {
                $this->setFlashMessage('danger', 'Location not found.');
                redirect('locations');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading location.');
            redirect('locations');
        }
        
        // Ensure fields are mapped for view
        $location = $this->locationModel->mapFieldsForView($location);
        
        // Load manager information if manager_id exists
        $manager = null;
        if (!empty($location['manager_id'])) {
            try {
                $userModel = $this->loadModel('User_model');
                $manager = $userModel->getById($location['manager_id']);
            } catch (Exception $e) {
                // Ignore errors
            }
        }
        
        $data = [
            'page_title' => 'Location: ' . ($location['Location_name'] ?? $location['property_name'] ?? ''),
            'Location' => $location, // Use Location for consistency with view
            'manager' => $manager,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('locations/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('locations', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            
            $data = [
                'property_name' => sanitize_input($_POST['Location_name'] ?? $_POST['property_name'] ?? ''),
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
                'property_value' => !empty($_POST['Location_value']) ? floatval($_POST['Location_value']) : (!empty($_POST['property_value']) ? floatval($_POST['property_value']) : null),
                'manager_id' => !empty($_POST['manager_id']) ? intval($_POST['manager_id']) : null,
                'status' => sanitize_input($_POST['status'] ?? 'operational'),
                'ownership_status' => sanitize_input($_POST['ownership_status'] ?? 'owned'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->locationModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Locations', 'Updated location: ' . $data['property_name']);
                $this->setFlashMessage('success', 'Location updated successfully.');
                redirect('locations/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update location.');
            }
        }
        
        try {
            $location = $this->locationModel->getById($id);
            if (!$location) {
                $this->setFlashMessage('danger', 'Location not found.');
                redirect('locations');
            }
            // Ensure fields are mapped for view
            $location = $this->locationModel->mapFieldsForView($location);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading location.');
            redirect('locations');
        }
        
        $userModel = $this->loadModel('User_model');
        try {
            $allUsers = $userModel->getAll();
            $managers = array_filter($allUsers, function($user) {
                return in_array($user['role'], ['manager', 'admin', 'super_admin']);
            });
        } catch (Exception $e) {
            $managers = [];
        }
        
        $data = [
            'page_title' => 'Edit Location',
            'Location' => $location, // Use Location for consistency with view
            'managers' => $managers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('locations/edit', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('locations', 'delete');
        
        try {
            $location = $this->locationModel->getById($id);
            if (!$location) {
                $this->setFlashMessage('danger', 'Location not found.');
                redirect('locations');
            }
            
            // Check if location has spaces
            $spaces = $this->spaceModel->getByProperty($id);
            if (!empty($spaces)) {
                $this->setFlashMessage('danger', 'Cannot delete location with associated spaces. Please remove or reassign spaces first.');
                redirect('locations/view/' . $id);
            }
            
            // Check if location has active leases
            // TODO: Add lease model check if needed
            
            if ($this->locationModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Locations', 'Deleted location: ' . ($location['property_name'] ?? $location['Location_name'] ?? ''));
                $this->setFlashMessage('success', 'Location deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete location.');
            }
        } catch (Exception $e) {
            error_log('Locations delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting location: ' . $e->getMessage());
        }
        
        redirect('locations');
    }
}
