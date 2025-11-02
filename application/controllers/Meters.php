<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meters extends Base_Controller {
    private $meterModel;
    private $utilityTypeModel;
    private $propertyModel;
    private $spaceModel;
    private $tenantModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->meterModel = $this->loadModel('Meter_model');
        $this->utilityTypeModel = $this->loadModel('Utility_type_model');
        $this->propertyModel = $this->loadModel('Property_model');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->tenantModel = $this->loadModel('Tenant_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $utilityTypeId = $_GET['utility_type_id'] ?? null;
        $propertyId = $_GET['property_id'] ?? null;
        
        try {
            if ($utilityTypeId) {
                $meters = $this->meterModel->getByUtilityType($utilityTypeId);
            } elseif ($propertyId) {
                $meters = $this->meterModel->getByProperty($propertyId);
            } else {
                $meters = $this->meterModel->getActive();
            }
            
            $utilityTypes = $this->utilityTypeModel->getActive();
            $properties = $this->propertyModel->getAll();
        } catch (Exception $e) {
            $meters = [];
            $utilityTypes = [];
            $properties = [];
        }
        
        $data = [
            'page_title' => 'Meters',
            'meters' => $meters,
            'utility_types' => $utilityTypes,
            'properties' => $properties,
            'selected_utility_type_id' => $utilityTypeId,
            'selected_property_id' => $propertyId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/meters/index', $data);
    }
    
    public function create() {
        $this->requirePermission('utilities', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'meter_number' => sanitize_input($_POST['meter_number'] ?? ''),
                'utility_type_id' => intval($_POST['utility_type_id'] ?? 0),
                'meter_type' => sanitize_input($_POST['meter_type'] ?? 'master'),
                'parent_meter_id' => !empty($_POST['parent_meter_id']) ? intval($_POST['parent_meter_id']) : null,
                'property_id' => !empty($_POST['property_id']) ? intval($_POST['property_id']) : null,
                'space_id' => !empty($_POST['space_id']) ? intval($_POST['space_id']) : null,
                'tenant_id' => !empty($_POST['tenant_id']) ? intval($_POST['tenant_id']) : null,
                'meter_location' => sanitize_input($_POST['meter_location'] ?? ''),
                'installation_date' => sanitize_input($_POST['installation_date'] ?? null),
                'meter_make' => sanitize_input($_POST['meter_make'] ?? ''),
                'meter_model' => sanitize_input($_POST['meter_model'] ?? ''),
                'meter_capacity' => sanitize_input($_POST['meter_capacity'] ?? ''),
                'meter_rating' => sanitize_input($_POST['meter_rating'] ?? ''),
                'last_calibration_date' => !empty($_POST['last_calibration_date']) ? sanitize_input($_POST['last_calibration_date']) : null,
                'next_calibration_due' => !empty($_POST['next_calibration_due']) ? sanitize_input($_POST['next_calibration_due']) : null,
                'initial_reading' => floatval($_POST['initial_reading'] ?? 0),
                'reading_frequency' => sanitize_input($_POST['reading_frequency'] ?? 'monthly'),
                'barcode' => sanitize_input($_POST['barcode'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'active'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (empty($data['meter_number'])) {
                $data['meter_number'] = $this->meterModel->getNextMeterNumber();
            }
            
            $data['last_reading'] = $data['initial_reading'];
            
            $meterId = $this->meterModel->create($data);
            
            if ($meterId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Meters', 'Created meter: ' . $data['meter_number']);
                $this->setFlashMessage('success', 'Meter created successfully.');
                redirect('utilities/meters/view/' . $meterId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create meter.');
            }
        }
        
        try {
            $utilityTypes = $this->utilityTypeModel->getActive();
            $properties = $this->propertyModel->getAll();
            $spaces = [];
            $tenants = $this->tenantModel->getActive();
            try {
                $meters = $this->meterModel->getAll(); // For parent meter selection
            } catch (Exception $e) {
                $meters = [];
            }
        } catch (Exception $e) {
            $utilityTypes = [];
            $properties = [];
            $spaces = [];
            $tenants = [];
            $meters = [];
        }
        
        $data = [
            'page_title' => 'Create Meter',
            'utility_types' => $utilityTypes,
            'properties' => $properties,
            'spaces' => $spaces,
            'tenants' => $tenants,
            'meters' => $meters,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/meters/create', $data);
    }
    
    public function view($id) {
        try {
            $meter = $this->meterModel->getWithDetails($id);
            if (!$meter) {
                $this->setFlashMessage('danger', 'Meter not found.');
                redirect('utilities/meters');
            }
            
            // Get last reading
            $lastReading = $this->meterModel->getLastReading($id);
            
            // Get recent readings
            $readings = $this->readingModel->getByMeter($id, date('Y-m-d', strtotime('-6 months')));
            
            // Get bills
            $bills = $this->billModel->getByMeter($id);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading meter.');
            redirect('utilities/meters');
        }
        
        $data = [
            'page_title' => 'Meter: ' . $meter['meter_number'],
            'meter' => $meter,
            'last_reading' => $lastReading,
            'readings' => array_slice($readings, 0, 10), // Recent 10 readings
            'bills' => array_slice($bills, 0, 10), // Recent 10 bills
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/meters/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('utilities', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'utility_type_id' => intval($_POST['utility_type_id'] ?? 0),
                'meter_type' => sanitize_input($_POST['meter_type'] ?? 'master'),
                'parent_meter_id' => !empty($_POST['parent_meter_id']) ? intval($_POST['parent_meter_id']) : null,
                'property_id' => !empty($_POST['property_id']) ? intval($_POST['property_id']) : null,
                'space_id' => !empty($_POST['space_id']) ? intval($_POST['space_id']) : null,
                'tenant_id' => !empty($_POST['tenant_id']) ? intval($_POST['tenant_id']) : null,
                'meter_location' => sanitize_input($_POST['meter_location'] ?? ''),
                'installation_date' => sanitize_input($_POST['installation_date'] ?? null),
                'meter_make' => sanitize_input($_POST['meter_make'] ?? ''),
                'meter_model' => sanitize_input($_POST['meter_model'] ?? ''),
                'meter_capacity' => sanitize_input($_POST['meter_capacity'] ?? ''),
                'meter_rating' => sanitize_input($_POST['meter_rating'] ?? ''),
                'last_calibration_date' => !empty($_POST['last_calibration_date']) ? sanitize_input($_POST['last_calibration_date']) : null,
                'next_calibration_due' => !empty($_POST['next_calibration_due']) ? sanitize_input($_POST['next_calibration_due']) : null,
                'reading_frequency' => sanitize_input($_POST['reading_frequency'] ?? 'monthly'),
                'barcode' => sanitize_input($_POST['barcode'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'active'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->meterModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Meters', 'Updated meter: ' . $id);
                $this->setFlashMessage('success', 'Meter updated successfully.');
                redirect('utilities/meters/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update meter.');
            }
        }
        
        try {
            $meter = $this->meterModel->getById($id);
            if (!$meter) {
                $this->setFlashMessage('danger', 'Meter not found.');
                redirect('utilities/meters');
            }
            
            $utilityTypes = $this->utilityTypeModel->getActive();
            $properties = $this->propertyModel->getAll();
            try {
                $spaces = $meter['property_id'] ? $this->spaceModel->getByProperty($meter['property_id']) : [];
            } catch (Exception $e) {
                $spaces = [];
            }
            $tenants = $this->tenantModel->getActive();
            try {
                $meters = $this->meterModel->getAll();
            } catch (Exception $e) {
                $meters = [];
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading meter.');
            redirect('utilities/meters');
        }
        
        $data = [
            'page_title' => 'Edit Meter',
            'meter' => $meter,
            'utility_types' => $utilityTypes,
            'properties' => $properties,
            'spaces' => $spaces,
            'tenants' => $tenants,
            'meters' => $meters,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/meters/edit', $data);
    }
}

