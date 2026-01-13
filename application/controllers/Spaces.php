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
        require_once dirname(__DIR__) . '/models/Bookable_config_model.php';
        $this->bookableConfigModel = new Bookable_config_model();
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
                    // Auto-sync to booking module
                    try {
                        $this->spaceModel->syncToBookingModule($spaceId);
                    } catch (Exception $e) {
                        error_log('Spaces create auto-sync error: ' . $e->getMessage());
                    }
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
                // Check if rates/config fields were submitted (even if is_bookable checkbox wasn't checked)
                $hasRateUpdates = isset($_POST['hourly_rate']) || isset($_POST['daily_rate']) || 
                                  isset($_POST['half_day_rate']) || isset($_POST['weekly_rate']) ||
                                  isset($_POST['minimum_duration']) || isset($_POST['maximum_duration']);
                
                // Get current space state after update
                $currentSpace = $this->spaceModel->getById($id);
                $isCurrentlyBookable = !empty($currentSpace['is_bookable']);
                $hasBookableConfig = !empty($this->spaceModel->getBookableConfig($id));
                
                // Update bookable config if checkbox is checked OR if rates were updated
                error_log("Spaces Edit: POST is_bookable=" . ($_POST['is_bookable']??'null') . ", hasRateUpdates=" . ($hasRateUpdates?'yes':'no') . ", hasConfig=" . ($hasBookableConfig?'yes':'no'));
                
                if (!empty($_POST['is_bookable']) || ($hasRateUpdates && $hasBookableConfig)) {
                    error_log("Spaces Edit: Calling updateBookableConfig");
                    $this->updateBookableConfig($id, $_POST);
                } else {
                    error_log("Spaces Edit: NOT calling updateBookableConfig");
                }
                
                // Always sync if space is bookable or has bookable config
                if ($isCurrentlyBookable || $hasBookableConfig) {
                    try {
                        $this->spaceModel->syncToBookingModule($id);
                    } catch (Exception $e) {
                        error_log('Spaces edit auto-sync error: ' . $e->getMessage());
                        $this->setFlashMessage('warning', 'Space updated but sync to booking module failed: ' . $e->getMessage());
                    }
                }
                
                // If unmarked as bookable, remove from booking module
                if (empty($_POST['is_bookable']) && $wasBookable) {
                    try {
                        $currentSpace = $this->spaceModel->getById($id);
                        if ($currentSpace && !empty($currentSpace['facility_id'])) {
                            $this->facilityModel->update($currentSpace['facility_id'], [
                                'status' => 'inactive',
                                'is_bookable' => 0
                            ]);
                        }
                    } catch (Exception $e) {
                        error_log('Spaces edit remove-sync error: ' . $e->getMessage());
                    }
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
     * Sync space to booking module (alias for syncToBooking)
     * This method is called from the view via /spaces/sync/{id}
     */
    public function sync($id) {
        return $this->syncToBooking($id);
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
            error_log("Spaces createBookableConfig ENTRY for $spaceId");
            
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
            
            error_log('Spaces createBookableConfig: Pricing rules = ' . json_encode($pricingRules));
            
            $availabilityRules = [
                'operating_hours' => [
                    'start' => $postData['operating_start'] ?? '08:00',
                    'end' => $postData['operating_end'] ?? '22:00'
                ],
                'days_available' => !empty($postData['days_available']) ? array_map('intval', $postData['days_available']) : [0,1,2,3,4,5,6],
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
            
            error_log('Spaces createBookableConfig: Config data = ' . json_encode($configData));
            
            $createResult = $this->bookableConfigModel->create($configData);
            error_log('Spaces createBookableConfig: Create result = ' . var_export($createResult, true));
            
            // Sync to booking module
            $syncResult = $this->spaceModel->syncToBookingModule($spaceId);
            error_log('Spaces createBookableConfig: Sync result = ' . var_export($syncResult, true));
            
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
            error_log("Spaces updateBookableConfig ENTRY for $spaceId");
            
            $config = $this->spaceModel->getBookableConfig($spaceId);
            
            if (!$config) {
                error_log("Spaces updateBookableConfig: No config, calling create");
                return $this->createBookableConfig($spaceId, $postData);
            }
            
            error_log('Spaces updateBookableConfig: Found existing config ID ' . $config['id']);
            
            $bookingTypes = [];
            if (!empty($postData['booking_types']) && is_array($postData['booking_types'])) {
                $bookingTypes = $postData['booking_types'];
            }
            
            // Get existing pricing rules to preserve values not being updated
            $existingPricingRules = json_decode($config['pricing_rules'] ?? '{}', true) ?: [];
            
            // Start with existing rules and only update fields that are provided and not empty
            $pricingRules = $existingPricingRules;
            
            if (isset($postData['hourly_rate']) && $postData['hourly_rate'] !== '' && $postData['hourly_rate'] !== null) {
                $pricingRules['base_hourly'] = floatval($postData['hourly_rate']);
            }
            if (isset($postData['daily_rate']) && $postData['daily_rate'] !== '' && $postData['daily_rate'] !== null) {
                $pricingRules['base_daily'] = floatval($postData['daily_rate']);
            }
            if (isset($postData['half_day_rate']) && $postData['half_day_rate'] !== '' && $postData['half_day_rate'] !== null) {
                $pricingRules['half_day'] = floatval($postData['half_day_rate']);
            }
            if (isset($postData['weekly_rate']) && $postData['weekly_rate'] !== '' && $postData['weekly_rate'] !== null) {
                $pricingRules['weekly'] = floatval($postData['weekly_rate']);
            }
            if (isset($postData['security_deposit']) && $postData['security_deposit'] !== '' && $postData['security_deposit'] !== null) {
                $pricingRules['deposit'] = floatval($postData['security_deposit']);
            }
            // Preserve peak_rate_multiplier if it exists
            if (!isset($pricingRules['peak_rate_multiplier']) && isset($existingPricingRules['peak_rate_multiplier'])) {
                $pricingRules['peak_rate_multiplier'] = $existingPricingRules['peak_rate_multiplier'];
            }
            
            error_log('Spaces updateBookableConfig: Pricing rules = ' . json_encode($pricingRules));
            
            // Get existing availability rules to preserve values
            $existingAvailabilityRules = json_decode($config['availability_rules'] ?? '{}', true) ?: [];
            
            $availabilityRules = $existingAvailabilityRules; // Start with existing rules
            
            if (isset($postData['operating_start']) || isset($postData['operating_end'])) {
                $availabilityRules['operating_hours'] = [
                    'start' => $postData['operating_start'] ?? ($existingAvailabilityRules['operating_hours']['start'] ?? '08:00'),
                    'end' => $postData['operating_end'] ?? ($existingAvailabilityRules['operating_hours']['end'] ?? '22:00')
                ];
            }
            
            if (!empty($postData['days_available']) && is_array($postData['days_available'])) {
                $availabilityRules['days_available'] = array_map('intval', $postData['days_available']);
            }
            
            // Preserve blackout_dates if they exist
            if (!isset($availabilityRules['blackout_dates']) && isset($existingAvailabilityRules['blackout_dates'])) {
                $availabilityRules['blackout_dates'] = $existingAvailabilityRules['blackout_dates'];
            }
            
            $updateData = [
                'booking_types' => json_encode($bookingTypes),
                'minimum_duration' => isset($postData['minimum_duration']) ? intval($postData['minimum_duration']) : ($config['minimum_duration'] ?? 1),
                'maximum_duration' => isset($postData['maximum_duration']) && $postData['maximum_duration'] !== '' ? intval($postData['maximum_duration']) : ($config['maximum_duration'] ?? null),
                'pricing_rules' => json_encode($pricingRules),
                'availability_rules' => json_encode($availabilityRules),
                'setup_time_buffer' => isset($postData['setup_time_buffer']) ? intval($postData['setup_time_buffer']) : ($config['setup_time_buffer'] ?? 0),
                'cleanup_time_buffer' => isset($postData['cleanup_time_buffer']) ? intval($postData['cleanup_time_buffer']) : ($config['cleanup_time_buffer'] ?? 0)
            ];
            
            // Only update advance_booking_days if provided
            if (isset($postData['advance_booking_days'])) {
                $updateData['advance_booking_days'] = intval($postData['advance_booking_days']);
            }
            
            // Only update simultaneous_limit if provided
            if (isset($postData['simultaneous_limit'])) {
                $updateData['simultaneous_limit'] = intval($postData['simultaneous_limit']);
            }
            
            error_log('Spaces updateBookableConfig: Update data = ' . json_encode($updateData));
            
            $updateResult = $this->bookableConfigModel->update($config['id'], $updateData);
            
            error_log('Spaces updateBookableConfig: Update result = ' . var_export($updateResult, true));
            
            // Sync to booking module - ALWAYS sync when config is updated
            $syncResult = $this->spaceModel->syncToBookingModule($spaceId);
            
            error_log('Spaces updateBookableConfig: Sync result = ' . var_export($syncResult, true));
            
            if (!$syncResult) {
                error_log('Spaces updateBookableConfig: WARNING - Sync failed for space ' . $spaceId);
            }
            
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
    
    /**
     * Delete a space
     * Validates no active bookings or leases exist before deletion
     */
    public function delete($id) {
        $this->requirePermission('locations', 'delete');
        
        try {
            $space = $this->spaceModel->getById($id);
            if (!$space) {
                $this->setFlashMessage('danger', 'Space not found.');
                redirect('spaces');
            }
            
            $propertyId = $space['property_id'] ?? null;
            
            // Check for active bookings
            // Check for active bookings via Facility link (bookings are linked to facilities, not directly to spaces ID if synced)
            // But we should also check if 'space_id' exists in bookings table just in case? 
            // Based on Booking_model, it uses facility_id.
            
            if (!empty($space['facility_id'])) {
                $activeBookings = $this->db->fetchAll(
                    "SELECT id FROM `" . $this->db->getPrefix() . "bookings` 
                     WHERE facility_id = ? AND status IN ('pending', 'confirmed', 'checked_in')",
                    [$space['facility_id']]
                );
                
                if (!empty($activeBookings)) {
                    $this->setFlashMessage('danger', 'Cannot delete space. It has ' . count($activeBookings) . ' active booking(s). Please cancel or complete them first.');
                    redirect('spaces/view/' . $id);
                }
            }
            
            // Check for active leases
            // Check for active leases
            // Use direct DB check for robustness and consistency
            $activeLeases = $this->db->fetchAll(
                "SELECT id FROM `" . $this->db->getPrefix() . "leases` 
                 WHERE space_id = ? AND status = 'active'",
                [$id]
            );
            
            if (!empty($activeLeases)) {
                $this->setFlashMessage('danger', 'Cannot delete space with active leases. Please terminate leases first.');
                redirect('spaces/view/' . $id);
            }
            
            // Deactivate in booking module first
            if (!empty($space['facility_id'])) {
                try {
                    $this->facilityModel->update($space['facility_id'], [
                        'status' => 'inactive',
                        'is_bookable' => 0
                    ]);
                } catch (Exception $e) {
                    error_log('Spaces delete deactivate facility error: ' . $e->getMessage());
                }
            }
            
            // Delete bookable config if exists
            try {
                $this->bookableConfigModel->deleteBy(['space_id' => $id]);
            } catch (Exception $e) {
                // Continue if fails
            }
            
            // Delete the space
            if ($this->spaceModel->delete($id)) {
                $this->activityModel->log(
                    $this->session['user_id'], 
                    'delete', 
                    'Spaces', 
                    'Deleted space: ' . ($space['space_name'] ?? $space['space_number'] ?? '')
                );
                $this->setFlashMessage('success', 'Space deleted successfully.');
                
                // Redirect to property spaces if we have property ID
                if ($propertyId) {
                    redirect('spaces?property_id=' . $propertyId);
                }
            } else {
                $this->setFlashMessage('danger', 'Failed to delete space.');
                redirect('spaces/view/' . $id);
            }
            
        } catch (Exception $e) {
            error_log('Spaces delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting space: ' . $e->getMessage());
        }
        
        redirect('spaces');
    }
}
