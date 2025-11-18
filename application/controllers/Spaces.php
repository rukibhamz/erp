<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Spaces extends Base_Controller {
    private $spaceModel;
    private $locationModel; // Location_model (formerly Property_model)
    private $facilityModel;
    private $bookableConfigModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('locations', 'read');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->activityModel = $this->loadModel('Activity_model');
        
        // Load Bookable_config_model dynamically
        require_once BASEPATH . 'models/Bookable_config_model.php';
        $this->bookableConfigModel = new Bookable_config_model($this->db);
    }
    
    public function index($propertyId = null) {
        try {
            // Check if property_id is in GET params
            $propertyId = $propertyId ?: (isset($_GET['property_id']) ? intval($_GET['property_id']) : null);
            
            if ($propertyId) {
                $spaces = $this->spaceModel->getByProperty($propertyId);
                $location = $this->locationModel->getById($propertyId);
            } else {
                // Show all spaces when no filter is selected
                $spaces = $this->spaceModel->getAll();
                $location = null;
            }
            
            $locations = $this->locationModel->getAll();
        } catch (Exception $e) {
            $spaces = [];
            $location = null;
            $locations = [];
        }
        
        $data = [
            'page_title' => 'Spaces',
            'spaces' => $spaces,
            'location' => $location,
            'property' => $location, // Legacy compatibility
            'locations' => $locations,
            'properties' => $locations, // Legacy compatibility
            'selected_property_id' => $propertyId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('spaces/index', $data);
    }
    
    public function create($propertyId = null) {
        $this->requirePermission('locations', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $propertyId = intval($_POST['property_id'] ?? 0);
            
            $amenities = [];
            if (!empty($_POST['amenities']) && is_array($_POST['amenities'])) {
                $amenities = $_POST['amenities'];
            }
            
            $data = [
                'property_id' => $propertyId,
                'space_number' => sanitize_input($_POST['space_number'] ?? ''),
                'space_name' => sanitize_input($_POST['space_name'] ?? ''),
                'category' => sanitize_input($_POST['category'] ?? 'other'),
                'space_type' => sanitize_input($_POST['space_type'] ?? ''),
                'floor' => sanitize_input($_POST['floor'] ?? ''),
                'area' => !empty($_POST['area']) ? floatval($_POST['area']) : null,
                'capacity' => !empty($_POST['capacity']) ? intval($_POST['capacity']) : null,
                'configuration' => sanitize_input($_POST['configuration'] ?? ''),
                'amenities' => json_encode($amenities),
                'accessibility_features' => !empty($_POST['accessibility_features']) ? json_encode($_POST['accessibility_features']) : null,
                'operational_status' => sanitize_input($_POST['operational_status'] ?? 'active'),
                'operational_mode' => sanitize_input($_POST['operational_mode'] ?? 'vacant'),
                'is_bookable' => !empty($_POST['is_bookable']) ? 1 : 0,
                'description' => sanitize_input($_POST['description'] ?? ''),
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Auto-generate space number if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['space_number'])) {
                $data['space_number'] = $this->spaceModel->getNextSpaceNumber($propertyId);
            }
            
            $spaceId = $this->spaceModel->create($data);
            
            if ($spaceId) {
                // If marked as bookable, create bookable config and sync to booking module
                if (!empty($_POST['is_bookable'])) {
                    $this->createBookableConfig($spaceId, $_POST);
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Spaces', 'Created space: ' . $data['space_name']);
                $this->setFlashMessage('success', 'Space created successfully.');
                redirect('spaces/view/' . $spaceId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create space.');
            }
        }
        
        try {
            $locations = $this->locationModel->getAll();
            $properties = $locations; // Legacy compatibility
        } catch (Exception $e) {
            $properties = [];
        }
        
        $data = [
            'page_title' => 'Create Space',
            'property_id' => $propertyId,
            'properties' => $properties,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('spaces/create', $data);
    }
    
    public function view($id) {
        try {
            $space = $this->spaceModel->getWithProperty($id);
            if (!$space) {
                $this->setFlashMessage('danger', 'Space not found.');
                redirect('spaces');
            }
            
            $space['photos'] = $this->spaceModel->getPhotos($id);
            $space['bookable_config'] = $this->spaceModel->getBookableConfig($id);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading space.');
            redirect('spaces');
        }
        
        $data = [
            'page_title' => 'Space: ' . $space['space_name'],
            'space' => $space,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('spaces/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('locations', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            $amenities = [];
            if (!empty($_POST['amenities']) && is_array($_POST['amenities'])) {
                $amenities = $_POST['amenities'];
            }
            
            $data = [
                'space_number' => sanitize_input($_POST['space_number'] ?? ''),
                'space_name' => sanitize_input($_POST['space_name'] ?? ''),
                'category' => sanitize_input($_POST['category'] ?? 'other'),
                'space_type' => sanitize_input($_POST['space_type'] ?? ''),
                'floor' => sanitize_input($_POST['floor'] ?? ''),
                'area' => !empty($_POST['area']) ? floatval($_POST['area']) : null,
                'capacity' => !empty($_POST['capacity']) ? intval($_POST['capacity']) : null,
                'configuration' => sanitize_input($_POST['configuration'] ?? ''),
                'amenities' => json_encode($amenities),
                'accessibility_features' => !empty($_POST['accessibility_features']) ? json_encode($_POST['accessibility_features']) : null,
                'operational_status' => sanitize_input($_POST['operational_status'] ?? 'active'),
                'operational_mode' => sanitize_input($_POST['operational_mode'] ?? 'vacant'),
                'is_bookable' => !empty($_POST['is_bookable']) ? 1 : 0,
                'description' => sanitize_input($_POST['description'] ?? ''),
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $wasBookable = false;
            try {
                $currentSpace = $this->spaceModel->getById($id);
                $wasBookable = !empty($currentSpace['is_bookable']);
            } catch (Exception $e) {
                // Ignore
            }
            
            if ($this->spaceModel->update($id, $data)) {
                // Update bookable config if needed
                if (!empty($_POST['is_bookable'])) {
                    $this->updateBookableConfig($id, $_POST);
                } elseif ($wasBookable) {
                    // If space was bookable but now isn't, deactivate in booking module
                    $this->deactivateInBookingModule($id);
                }
                
                $this->activityModel->log($this->session['user_id'], 'update', 'Spaces', 'Updated space: ' . $data['space_name']);
                $this->setFlashMessage('success', 'Space updated successfully.');
                redirect('spaces/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update space.');
            }
        }
        
        try {
            $space = $this->spaceModel->getWithProperty($id);
            if (!$space) {
                $this->setFlashMessage('danger', 'Space not found.');
                redirect('spaces');
            }
            
            $space['photos'] = $this->spaceModel->getPhotos($id);
            $space['bookable_config'] = $this->spaceModel->getBookableConfig($id);
            $locations = $this->locationModel->getAll();
            $properties = $locations; // Legacy compatibility
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading space.');
            redirect('spaces');
        }
        
        $data = [
            'page_title' => 'Edit Space',
            'space' => $space,
            'properties' => $properties,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('spaces/edit', $data);
    }
    
    /**
     * Sync space to booking module
     */
    public function syncToBooking($id) {
        $this->requirePermission('locations', 'update');
        
        try {
            $facilityId = $this->spaceModel->syncToBookingModule($id);
            
            if ($facilityId) {
                $this->setFlashMessage('success', 'Space synchronized with booking module successfully.');
            } else {
                $this->setFlashMessage('warning', 'Failed to sync space. Ensure space is marked as bookable.');
            }
        } catch (Exception $e) {
            error_log('Spaces syncToBooking error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error syncing space: ' . $e->getMessage());
        }
        
        redirect('spaces/view/' . $id);
    }
    
    /**
     * Create bookable configuration
     */
    private function createBookableConfig($spaceId, $postData) {
        try {
            $bookingTypes = [];
            if (!empty($postData['booking_types']) && is_array($postData['booking_types'])) {
                $bookingTypes = $postData['booking_types'];
            }
            
            $pricingRules = [
                'base_hourly' => floatval($postData['hourly_rate'] ?? 0),
                'base_daily' => floatval($postData['daily_rate'] ?? 0),
                'half_day' => floatval($postData['half_day_rate'] ?? 0),
                'weekly' => floatval($postData['weekly_rate'] ?? 0),
                'deposit' => floatval($postData['security_deposit'] ?? 0),
                'peak_rate_multiplier' => floatval($postData['peak_rate_multiplier'] ?? 1.0)
            ];
            
            $availabilityRules = [
                'operating_hours' => [
                    'start' => $postData['operating_start'] ?? '08:00',
                    'end' => $postData['operating_end'] ?? '22:00'
                ],
                'days_available' => !empty($postData['days_available']) ? $postData['days_available'] : [0,1,2,3,4,5,6],
                'blackout_dates' => []
            ];
            
            $configData = [
                'space_id' => $spaceId,
                'is_bookable' => 1,
                'booking_types' => json_encode($bookingTypes),
                'minimum_duration' => intval($postData['minimum_duration'] ?? 1),
                'maximum_duration' => !empty($postData['maximum_duration']) ? intval($postData['maximum_duration']) : null,
                'advance_booking_days' => intval($postData['advance_booking_days'] ?? 365),
                'cancellation_policy_id' => !empty($postData['cancellation_policy_id']) ? intval($postData['cancellation_policy_id']) : null,
                'pricing_rules' => json_encode($pricingRules),
                'availability_rules' => json_encode($availabilityRules),
                'setup_time_buffer' => intval($postData['setup_time_buffer'] ?? 0),
                'cleanup_time_buffer' => intval($postData['cleanup_time_buffer'] ?? 0),
                'simultaneous_limit' => intval($postData['simultaneous_limit'] ?? 1)
            ];
            
            $this->bookableConfigModel->create($configData);
            
            // Sync to booking module
            $this->spaceModel->syncToBookingModule($spaceId);
            
            return true;
        } catch (Exception $e) {
            error_log('Spaces createBookableConfig error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update bookable configuration
     */
    private function updateBookableConfig($spaceId, $postData) {
        try {
            $config = $this->spaceModel->getBookableConfig($spaceId);
            
            if (!$config) {
                // Create new config
                return $this->createBookableConfig($spaceId, $postData);
            }
            
            $bookingTypes = [];
            if (!empty($postData['booking_types']) && is_array($postData['booking_types'])) {
                $bookingTypes = $postData['booking_types'];
            }
            
            $pricingRules = [
                'base_hourly' => floatval($postData['hourly_rate'] ?? 0),
                'base_daily' => floatval($postData['daily_rate'] ?? 0),
                'half_day' => floatval($postData['half_day_rate'] ?? 0),
                'weekly' => floatval($postData['weekly_rate'] ?? 0),
                'deposit' => floatval($postData['security_deposit'] ?? 0)
            ];
            
            $availabilityRules = [
                'operating_hours' => [
                    'start' => $postData['operating_start'] ?? '08:00',
                    'end' => $postData['operating_end'] ?? '22:00'
                ],
                'days_available' => !empty($postData['days_available']) ? $postData['days_available'] : [0,1,2,3,4,5,6]
            ];
            
            $updateData = [
                'booking_types' => json_encode($bookingTypes),
                'minimum_duration' => intval($postData['minimum_duration'] ?? 1),
                'maximum_duration' => !empty($postData['maximum_duration']) ? intval($postData['maximum_duration']) : null,
                'pricing_rules' => json_encode($pricingRules),
                'availability_rules' => json_encode($availabilityRules),
                'setup_time_buffer' => intval($postData['setup_time_buffer'] ?? 0),
                'cleanup_time_buffer' => intval($postData['cleanup_time_buffer'] ?? 0)
            ];
            
            $this->bookableConfigModel->update($config['id'], $updateData);
            
            // Sync to booking module
            $this->spaceModel->syncToBookingModule($spaceId);
            
            return true;
        } catch (Exception $e) {
            error_log('Spaces updateBookableConfig error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deactivate space in booking module
     */
    private function deactivateInBookingModule($spaceId) {
        try {
            $space = $this->spaceModel->getById($spaceId);
            if ($space && $space['facility_id']) {
                $this->facilityModel->update($space['facility_id'], [
                    'status' => 'under_maintenance',
                    'is_bookable' => 0
                ]);
            }
        } catch (Exception $e) {
            error_log('Spaces deactivateInBookingModule error: ' . $e->getMessage());
        }
    }
}

