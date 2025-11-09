<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Locations extends Base_Controller {
    private $locationModel;
    private $stockLevelModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('inventory', 'read');
        $this->locationModel = $this->loadModel('Location_model');
        $this->stockLevelModel = $this->loadModel('Stock_level_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $locations = $this->locationModel->getActive();
            
            // Get stock counts for each location
            foreach ($locations as &$location) {
                try {
                    $stockLevels = $this->stockLevelModel->getByLocation($location['id']);
                    $location['item_count'] = count($stockLevels);
                    $location['total_qty'] = array_sum(array_column($stockLevels, 'quantity'));
                } catch (Exception $e) {
                    $location['item_count'] = 0;
                    $location['total_qty'] = 0;
                }
            }
            unset($location);
            
        } catch (Exception $e) {
            $locations = [];
        }
        
        $data = [
            'page_title' => 'Locations',
            'locations' => $locations,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/locations/index', $data);
    }
    
    public function create() {
        $this->requirePermission('inventory', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'location_code' => sanitize_input($_POST['location_code'] ?? ''),
                'location_name' => sanitize_input($_POST['location_name'] ?? ''),
                'location_type' => sanitize_input($_POST['location_type'] ?? 'warehouse'),
                'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
                'address' => sanitize_input($_POST['address'] ?? ''),
                'capacity' => !empty($_POST['capacity']) ? floatval($_POST['capacity']) : null,
                'barcode' => sanitize_input($_POST['barcode'] ?? ''),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Auto-generate location code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['location_code'])) {
                $data['location_code'] = $this->locationModel->getNextLocationCode();
            }
            
            $locationId = $this->locationModel->create($data);
            
            if ($locationId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Locations', 'Created location: ' . $data['location_name']);
                $this->setFlashMessage('success', 'Location created successfully.');
                redirect('inventory/locations');
            } else {
                $this->setFlashMessage('danger', 'Failed to create location.');
            }
        }
        
        try {
            $parentLocations = $this->locationModel->getActive();
        } catch (Exception $e) {
            $parentLocations = [];
        }
        
        $data = [
            'page_title' => 'Create Location',
            'parent_locations' => $parentLocations,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('inventory/locations/create', $data);
    }
}

