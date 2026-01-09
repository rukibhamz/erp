<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Locations extends Base_Controller {
    private $locationModel;
    private $spaceModel;
    private $activityModel;
    private $bookingModel;
    private $facilityModel;
    private $spaceBookingModel;
    private $meterModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('locations', 'read');
        $this->locationModel = $this->loadModel('Location_model');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->bookingModel = $this->loadModel('Booking_model');
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->spaceBookingModel = $this->loadModel('Space_booking_model');
        $this->meterModel = $this->loadModel('Meter_model');
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
                'is_bookable' => isset($_POST['is_bookable']) ? 1 : 0,
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
                'is_bookable' => isset($_POST['is_bookable']) ? 1 : 0,
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
            
            // Check if location has active leases (via spaces)
            // Note: Since we block deletion if spaces exist, this is implicitly covered.
            
            // Check if location has meters
            $meters = $this->meterModel->getByProperty($id);
            if (!empty($meters)) {
                $this->setFlashMessage('danger', 'Cannot delete location with associated meters. Please remove or reassign meters first.');
                redirect('locations/view/' . $id);
            }
            
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
    
    /**
     * Bookings - Consolidated into Locations module
     */
    public function bookings($locationId = null) {
        $this->requirePermission('locations', 'read');
        
        $status = $_GET['status'] ?? 'all';
        $date = $_GET['date'] ?? date('Y-m-d');
        $selectedLocationId = $locationId ?: (isset($_GET['location_id']) ? intval($_GET['location_id']) : null);
        
        try {
            if ($selectedLocationId) {
                // Get bookings for specific location's spaces
                $spaces = $this->spaceModel->getByProperty($selectedLocationId);
                $spaceIds = array_column($spaces, 'id');
                $bookings = [];
                if (!empty($spaceIds)) {
                    $bookings = $this->spaceBookingModel->getBySpaces($spaceIds);
                }
            } else {
                // Get all bookings
                $bookings = $this->spaceBookingModel->getAllWithDetails();
            }
            
            // Filter by status if needed
            if ($status !== 'all') {
                $bookings = array_filter($bookings, function($b) use ($status) {
                    return ($b['status'] ?? 'pending') === $status;
                });
            }
        } catch (Exception $e) {
            $bookings = [];
        }
        
        $locations = $this->locationModel->getAll();
        
        $data = [
            'page_title' => 'Bookings',
            'bookings' => $bookings,
            'locations' => $locations,
            'selected_location_id' => $selectedLocationId,
            'selected_status' => $status,
            'selected_date' => $date,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('locations/bookings/index', $data);
    }
    
    public function createBooking($locationId = null, $spaceId = null) {
        $this->requirePermission('locations', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $spaceId = intval($_POST['space_id'] ?? 0);
            $bookingDate = sanitize_input($_POST['booking_date'] ?? '');
            $startTime = sanitize_input($_POST['start_time'] ?? '');
            $endTime = sanitize_input($_POST['end_time'] ?? '');
            $bookingType = sanitize_input($_POST['booking_type'] ?? 'hourly');
            $numberOfGuests = intval($_POST['number_of_guests'] ?? 0);
            $customerName = sanitize_input($_POST['customer_name'] ?? '');
            $customerEmail = sanitize_input($_POST['customer_email'] ?? '');
            $customerPhone = sanitize_input($_POST['customer_phone'] ?? '');
            $bookingNotes = sanitize_input($_POST['booking_notes'] ?? '');
            $specialRequests = sanitize_input($_POST['special_requests'] ?? '');
            
            if (!$spaceId || !$bookingDate || !$startTime || !$endTime || !$customerName || !$customerPhone) {
                $this->setFlashMessage('danger', 'Please fill in all required fields.');
                redirect('locations/create-booking' . ($locationId ? '/' . $locationId : '') . ($spaceId ? '/' . $spaceId : ''));
            }
            
            // Get space
            $space = $this->spaceModel->getWithProperty($spaceId);
            if (!$space || !$space['is_bookable']) {
                $this->setFlashMessage('danger', 'Selected space is not available for booking.');
                redirect('locations/create-booking' . ($locationId ? '/' . $locationId : ''));
            }
            
            // Check availability
            if (!$this->spaceBookingModel->checkAvailability($spaceId, $bookingDate, $startTime, $endTime)) {
                $this->setFlashMessage('danger', 'The selected time slot is not available. Please choose another time.');
                redirect('locations/create-booking/' . ($space['property_id'] ?? '') . '/' . $spaceId);
            }
            
            // Calculate duration and price
            $start = new DateTime($bookingDate . ' ' . $startTime);
            $end = new DateTime($bookingDate . ' ' . $endTime);
            $duration = $end->diff($start);
            $durationHours = $duration->h + ($duration->i / 60);
            
            // Get pricing from config
            $config = $this->spaceModel->getBookableConfig($spaceId);
            $pricingRules = [];
            if ($config && !empty($config['pricing_rules'])) {
                $pricingRules = json_decode($config['pricing_rules'], true) ?: [];
            }
            
            $hourlyRate = floatval($pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 5000);
            $baseAmount = $hourlyRate * $durationHours;
            
            $data = [
                'booking_number' => $this->spaceBookingModel->getNextBookingNumber(),
                'space_id' => $spaceId,
                'tenant_id' => null, // Can be linked to tenant later if needed
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_hours' => $durationHours,
                'number_of_guests' => $numberOfGuests,
                'booking_type' => $bookingType,
                'base_amount' => $baseAmount,
                'total_amount' => $baseAmount,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'booking_notes' => $bookingNotes,
                'special_requests' => $specialRequests,
                'created_by' => $this->session['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->spaceBookingModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Created booking: ' . $data['booking_number']);
                $this->setFlashMessage('success', 'Booking created successfully.');
                redirect('locations/bookings');
            } else {
                $this->setFlashMessage('danger', 'Failed to create booking.');
            }
        }
        
        try {
            $locations = $this->locationModel->getAll();
            $selectedLocation = $locationId ? $this->locationModel->getById($locationId) : null;
            $spaces = [];
            $selectedSpace = null;
            $spaceConfig = null;
            
            if ($locationId) {
                $spaces = $this->spaceModel->getBookableSpaces($locationId);
            }
            
            if ($spaceId) {
                $selectedSpace = $this->spaceModel->getById($spaceId);
                if ($selectedSpace) {
                    $spaceConfig = $this->spaceModel->getBookableConfig($spaceId);
                }
            }
            
            // Check if coming from spaces module (via referrer or query param)
            $fromSpacesModule = !empty($_GET['from']) && $_GET['from'] === 'spaces';
            if (!$fromSpacesModule && !empty($_SERVER['HTTP_REFERER'])) {
                $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
                $fromSpacesModule = strpos($referer, '/spaces/') !== false;
            }
        } catch (Exception $e) {
            $locations = [];
            $selectedLocation = null;
            $spaces = [];
            $selectedSpace = null;
            $spaceConfig = null;
            $fromSpacesModule = false;
        }
        
        $data = [
            'page_title' => 'Create Booking',
            'locations' => $locations,
            'selected_location' => $selectedLocation,
            'spaces' => $spaces,
            'selected_space' => $selectedSpace,
            'space_config' => $spaceConfig,
            'from_spaces_module' => $fromSpacesModule ?? false,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('locations/bookings/create', $data);
    }
    
    public function viewBooking($id) {
        $this->requirePermission('locations', 'read');
        
        try {
            $booking = $this->spaceBookingModel->getById($id);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('locations/bookings');
            }
            
            $space = $this->spaceModel->getWithProperty($booking['space_id']);
            $location = $space ? $this->locationModel->getById($space['property_id']) : null;
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading booking.');
            redirect('locations/bookings');
        }
        
        $data = [
            'page_title' => 'Booking: ' . $booking['booking_number'],
            'booking' => $booking,
            'space' => $space,
            'location' => $location,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('locations/bookings/view', $data);
    }
    
    public function bookingCalendar($locationId = null, $spaceId = null) {
        $this->requirePermission('locations', 'read');
        
        // Get location_id and space_id from query string if not in URL
        if (!$locationId && !empty($_GET['location_id'])) {
            $locationId = intval($_GET['location_id']);
        }
        if (!$spaceId && !empty($_GET['space_id'])) {
            $spaceId = intval($_GET['space_id']);
        }
        
        // Convert empty string to null
        if ($locationId === '' || $locationId === '0') {
            $locationId = null;
        }
        if ($spaceId === '' || $spaceId === '0') {
            $spaceId = null;
        }
        
        try {
            $locations = $this->locationModel->getAll();
            $selectedLocation = ($locationId && $locationId > 0) ? $this->locationModel->getById($locationId) : null;
            $spaces = [];
            $selectedSpace = null;
            $bookings = [];
            
            if ($locationId && $locationId > 0) {
                $spaces = $this->spaceModel->getBookableSpaces($locationId);
            }
            
            if ($spaceId && $spaceId > 0) {
                $selectedSpace = $this->spaceModel->getById($spaceId);
                if ($selectedSpace) {
                    $startDate = date('Y-m-d');
                    $endDate = date('Y-m-d', strtotime('+30 days'));
                    $bookingsData = $this->spaceBookingModel->getAvailabilityCalendar($spaceId, $startDate, $endDate);
                    
                    foreach ($bookingsData as $booking) {
                        $bookings[] = [
                            'booking_date' => $booking['booking_date'],
                            'start_time' => $booking['start_time'],
                            'end_time' => $booking['end_time'],
                            'status' => $booking['status'],
                            'customer_name' => $booking['customer_name'] ?? ($booking['business_name'] ?? $booking['contact_person'] ?? 'N/A'),
                            'id' => $booking['id'] ?? null
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Locations bookingCalendar error: ' . $e->getMessage());
            $locations = [];
            $selectedLocation = null;
            $spaces = [];
            $selectedSpace = null;
            $bookings = [];
        }
        
        $data = [
            'page_title' => 'Booking Calendar',
            'locations' => $locations,
            'selected_location' => $selectedLocation,
            'spaces' => $spaces,
            'selected_space' => $selectedSpace,
            'bookings' => $bookings,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('locations/bookings/calendar', $data);
    }
    
    public function getSpacesForBooking() {
        $this->requirePermission('locations', 'read');
        
        header('Content-Type: application/json');
        
        $locationId = intval($_GET['location_id'] ?? 0);
        if (!$locationId) {
            echo json_encode(['success' => false, 'error' => 'Location ID required']);
            exit;
        }
        
        try {
            $spaces = $this->spaceModel->getBookableSpaces($locationId);
            $spacesData = [];
            
            foreach ($spaces as $space) {
                $config = $this->spaceModel->getBookableConfig($space['id']);
                $bookingTypes = ['hourly', 'daily', 'half_day', 'weekly', 'multi_day'];
                
                if ($config && !empty($config['booking_types'])) {
                    $bookingTypes = json_decode($config['booking_types'], true) ?: $bookingTypes;
                }
                
                $pricingRules = [];
                if ($config && !empty($config['pricing_rules'])) {
                    $pricingRules = json_decode($config['pricing_rules'], true) ?: [];
                }
                
                $availabilityRules = [];
                if ($config && !empty($config['availability_rules'])) {
                    $availabilityRules = json_decode($config['availability_rules'], true) ?: [];
                }
                
                $operatingHours = $availabilityRules['operating_hours'] ?? ['start' => '08:00', 'end' => '22:00'];
                $daysAvailable = $availabilityRules['days_available'] ?? [0,1,2,3,4,5,6];
                
                $spacesData[] = [
                    'id' => $space['id'],
                    'space_name' => $space['space_name'],
                    'space_number' => $space['space_number'] ?? '',
                    'capacity' => $space['capacity'] ?? 0,
                    'facility_id' => $space['facility_id'] ?? '',
                    'booking_types' => $bookingTypes,
                    'hourly_rate' => floatval($pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 0),
                    'daily_rate' => floatval($pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 0),
                    'half_day_rate' => floatval($pricingRules['half_day'] ?? 0),
                    'weekly_rate' => floatval($pricingRules['weekly'] ?? 0),
                    'security_deposit' => floatval($pricingRules['deposit'] ?? 0),
                    'minimum_duration' => intval($config['minimum_duration'] ?? 1),
                    'maximum_duration' => !empty($config['maximum_duration']) ? intval($config['maximum_duration']) : null,
                    'operating_hours' => $operatingHours,
                    'days_available' => $daysAvailable
                ];
            }
            
            echo json_encode(['success' => true, 'spaces' => $spacesData]);
        } catch (Exception $e) {
            error_log('getSpacesForBooking error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    public function checkBookingAvailability() {
        $this->requirePermission('locations', 'read');
        
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['available' => false, 'error' => 'Method not allowed']);
            return;
        }
        
        $spaceId = intval($_POST['space_id'] ?? 0);
        $bookingDate = sanitize_input($_POST['booking_date'] ?? '');
        $startTime = sanitize_input($_POST['start_time'] ?? '');
        $endTime = sanitize_input($_POST['end_time'] ?? '');
        $excludeBookingId = !empty($_POST['exclude_booking_id']) ? intval($_POST['exclude_booking_id']) : null;
        
        if (!$spaceId || !$bookingDate || !$startTime || !$endTime) {
            echo json_encode(['available' => false, 'error' => 'Missing required parameters']);
            return;
        }
        
        $available = $this->spaceBookingModel->checkAvailability($spaceId, $bookingDate, $startTime, $endTime, $excludeBookingId);
        
        echo json_encode(['available' => $available]);
    }
}
