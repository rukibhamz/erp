<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Booking_wizard extends Base_Controller {
    private $facilityModel;
    private $bookingModel;
    private $addonModel;
    private $promoCodeModel;
    private $cancellationPolicyModel;
    private $paymentScheduleModel;
    private $bookingResourceModel;
    private $bookingAddonModel;
    private $paymentModel;
    private $transactionModel;
    private $accountModel;
    private $cashAccountModel;
    private $activityModel;
    private $gatewayModel;
    private $locationModel;
    private $spaceModel;

    public function __construct() {
        parent::__construct();
        // Public booking wizard - allow guest access
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->bookingModel = $this->loadModel('Booking_model');
        $this->addonModel = $this->loadModel('Addon_model');
        $this->promoCodeModel = $this->loadModel('Promo_code_model');
        $this->cancellationPolicyModel = $this->loadModel('Cancellation_policy_model');
        $this->paymentScheduleModel = $this->loadModel('Payment_schedule_model');
        $this->bookingResourceModel = $this->loadModel('Booking_resource_model');
        $this->bookingAddonModel = $this->loadModel('Booking_addon_model');
        $this->paymentModel = $this->loadModel('Booking_payment_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->gatewayModel = $this->loadModel('Payment_gateway_model');
        $this->locationModel = $this->loadModel('Location_model');
        $this->spaceModel = $this->loadModel('Space_model');
    }
    
    public function index() {
        redirect('booking_wizard/step1');
    }
    
    protected function checkAuth() {
        // Public controller - allow guest booking
        return true;
    }
    
    protected function loadView($view, $data = []) {
        $data['config'] = $this->config;
        $data['session'] = $this->session;
        
        $this->loader->view('layouts/header_public', $data);
        $this->loader->view($view, $data);
        $this->loader->view('layouts/footer_public', $data);
    }

    /**
     * Step 1: Select Location and Space
     */
    public function step1() {
        $locationId = $_GET['location_id'] ?? null;
        $date = $_GET['date'] ?? date('Y-m-d');
        
        try {
            // Load all locations with bookable spaces
            $locations = $this->locationModel->getActive();
            
            // Get spaces grouped by location
            $spacesByLocation = [];
            foreach ($locations as $location) {
                $spaces = $this->spaceModel->getBookableSpaces($location['id']);
                if (!empty($spaces)) {
                    // Get booking types and pricing for each space
                    foreach ($spaces as &$space) {
                        $config = $this->spaceModel->getBookableConfig($space['id']);
                        if ($config && !empty($config['booking_types'])) {
                            $space['booking_types'] = json_decode($config['booking_types'], true) ?: ['hourly', 'daily'];
                        } else {
                            $space['booking_types'] = ['hourly', 'daily']; // Default
                        }
                        
                        // Get pricing info
                        if ($config && !empty($config['pricing_rules'])) {
                            $pricingRules = json_decode($config['pricing_rules'], true) ?: [];
                            $space['hourly_rate'] = $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 0;
                            $space['daily_rate'] = $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 0;
                            $space['half_day_rate'] = $pricingRules['half_day'] ?? 0;
                            $space['weekly_rate'] = $pricingRules['weekly'] ?? 0;
                        }
                        
                        // Get facility_id if synced
                        if (!empty($space['facility_id'])) {
                            $facility = $this->facilityModel->getById($space['facility_id']);
                            if ($facility) {
                                $space['facility_id'] = $facility['id'];
                                $space['hourly_rate'] = $space['hourly_rate'] ?? $facility['hourly_rate'] ?? 0;
                                $space['daily_rate'] = $space['daily_rate'] ?? $facility['daily_rate'] ?? 0;
                            }
                        }
                        
                        // Get photos
                        try {
                            $space['photos'] = $this->spaceModel->getPhotos($space['id']);
                        } catch (Exception $e) {
                            $space['photos'] = [];
                        }
                    }
                    unset($space);
                    $spacesByLocation[$location['id']] = $spaces;
                }
            }
        } catch (Exception $e) {
            error_log('Booking_wizard step1 error: ' . $e->getMessage());
            $locations = [];
            $spacesByLocation = [];
        }

        $data = [
            'page_title' => 'Select Location & Space',
            'locations' => $locations,
            'spaces_by_location' => $spacesByLocation,
            'selected_location_id' => $locationId,
            'selected_date' => $date,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/step1_select_resource', $data);
    }
    
    /**
     * AJAX endpoint to get spaces for a location
     */
    public function getSpacesForLocation() {
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
                $bookingTypes = ['hourly', 'daily']; // Default
                
                if ($config && !empty($config['booking_types'])) {
                    $bookingTypes = json_decode($config['booking_types'], true) ?: $bookingTypes;
                }
                
                // Get pricing
                $pricingRules = [];
                if ($config && !empty($config['pricing_rules'])) {
                    $pricingRules = json_decode($config['pricing_rules'], true) ?: [];
                }
                
                // Get facility if synced
                $facilityId = null;
                $hourlyRate = $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 0;
                $dailyRate = $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 0;
                
                if (!empty($space['facility_id'])) {
                    $facilityId = $space['facility_id'];
                    $facility = $this->facilityModel->getById($facilityId);
                    if ($facility) {
                        $hourlyRate = $hourlyRate ?: ($facility['hourly_rate'] ?? 0);
                        $dailyRate = $dailyRate ?: ($facility['daily_rate'] ?? 0);
                    }
                }
                
                // Get photos
                $photos = [];
                try {
                    $photos = $this->spaceModel->getPhotos($space['id']);
                } catch (Exception $e) {
                    // Ignore
                }
                
                $spacesData[] = [
                    'id' => $space['id'],
                    'space_name' => $space['space_name'],
                    'space_number' => $space['space_number'] ?? '',
                    'capacity' => $space['capacity'] ?? 0,
                    'description' => $space['description'] ?? '',
                    'facility_id' => $facilityId,
                    'booking_types' => $bookingTypes,
                    'hourly_rate' => floatval($hourlyRate),
                    'daily_rate' => floatval($dailyRate),
                    'half_day_rate' => floatval($pricingRules['half_day'] ?? 0),
                    'weekly_rate' => floatval($pricingRules['weekly'] ?? 0),
                    'security_deposit' => floatval($pricingRules['deposit'] ?? 0),
                    'minimum_duration' => intval($config['minimum_duration'] ?? 1),
                    'maximum_duration' => !empty($config['maximum_duration']) ? intval($config['maximum_duration']) : null,
                    'photos' => $photos
                ];
            }
            
            echo json_encode(['success' => true, 'spaces' => $spacesData]);
        } catch (Exception $e) {
            error_log('getSpacesForLocation error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Step 2: Choose Date, Time, and Booking Type
     */
    public function step2($spaceId) {
        try {
            // Get space with location info
            $space = $this->spaceModel->getWithProperty($spaceId);
            if (!$space || !$space['is_bookable']) {
                $this->setFlashMessage('danger', 'Space not available for booking.');
                redirect('booking-wizard/step1');
            }
            
            // Get booking config
            $config = $this->spaceModel->getBookableConfig($spaceId);
            $bookingTypes = ['hourly', 'daily', 'multi_day']; // Default - always include common types
            if ($config && !empty($config['booking_types'])) {
                $configTypes = json_decode($config['booking_types'], true);
                if (is_array($configTypes) && !empty($configTypes)) {
                    $bookingTypes = $configTypes;
                }
            }
            
            // Get pricing
            $pricingRules = [];
            if ($config && !empty($config['pricing_rules'])) {
                $pricingRules = json_decode($config['pricing_rules'], true) ?: [];
            }
            
            // Get facility if synced
            $facility = null;
            if (!empty($space['facility_id'])) {
                $facility = $this->facilityModel->getById($space['facility_id']);
            }
            
            // Merge pricing from facility if available
            if ($facility) {
                $space['hourly_rate'] = $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? ($facility['hourly_rate'] ?? 0);
                $space['daily_rate'] = $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? ($facility['daily_rate'] ?? 0);
                $space['security_deposit'] = $pricingRules['deposit'] ?? ($facility['security_deposit'] ?? 0);
            } else {
                $space['hourly_rate'] = $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 0;
                $space['daily_rate'] = $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 0;
                $space['security_deposit'] = $pricingRules['deposit'] ?? 0;
            }
            
            $photos = $this->spaceModel->getPhotos($spaceId);
            $amenities = json_decode($space['amenities'] ?? '[]', true) ?: [];
            
            // Map location fields for view
            $location = $this->locationModel->mapFieldsForView([
                'id' => $space['property_id'],
                'property_name' => $space['property_name'] ?? '',
                'property_code' => $space['property_code'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log('Booking_wizard step2 error: ' . $e->getMessage());
            $space = null;
            $photos = [];
            $amenities = [];
            $bookingTypes = [];
            $location = null;
        }

        if (!$space) {
            redirect('booking-wizard/step1');
        }

        $data = [
            'page_title' => 'Select Date, Time & Booking Type',
            'space' => $space,
            'location' => $location,
            'facility' => $facility,
            'photos' => $photos,
            'amenities' => $amenities,
            'booking_types' => $bookingTypes,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/step2_select_datetime', $data);
    }

    /**
     * Get available and occupied time slots for a date (with 1-hour buffer)
     */
    /**
     * Get available and occupied time slots for a date (with 1-hour buffer)
     */
    public function getTimeSlots() {
        // Prevent partial output or warnings from breaking JSON
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
        
        $spaceId = intval($_GET['space_id'] ?? 0);
        $spaceId = intval($_GET['space_id'] ?? 0);
        $rawDate = $_GET['date'] ?? '';
        $rawEndDate = $_GET['end_date'] ?? $rawDate;
        
        error_log("DEBUG: Booking_wizard input - space_id: $spaceId, rawDate: $rawDate, rawEndDate: $rawEndDate");

        // Helper to normalize date to Y-m-d
        $normalizeDate = function($d) {
            if (empty($d)) return '';
            // Try d/m/Y first (common frontend format)
            $dt = DateTime::createFromFormat('d/m/Y', $d);
            if ($dt && $dt->format('d/m/Y') === $d) {
                return $dt->format('Y-m-d');
            }
            // Try Y-m-d
            $dt = DateTime::createFromFormat('Y-m-d', $d);
            if ($dt && $dt->format('Y-m-d') === $d) {
                return $dt->format('Y-m-d');
            }
            // Fallback to strtotime (risky with slashed dates, but last resort)
            return date('Y-m-d', strtotime($d));
        };

        $date = $normalizeDate(sanitize_input($rawDate));
        $endDate = $normalizeDate(sanitize_input($rawEndDate));
        
        if (empty($endDate)) {
            $endDate = $date;
        }
        
        error_log("DEBUG: Booking_wizard normalized - date: $date, endDate: $endDate");
        
        if (!$spaceId || !$date) {
            error_log("DEBUG: Invalid parameters in Booking_wizard.");
            echo json_encode(['success' => false, 'message' => 'Invalid parameters: Space ID or Date missing']);
            exit;
        }

        try {
            // Get space to find facility_id
            $space = $this->spaceModel->getWithProperty($spaceId);
            if (!$space || !$space['is_bookable']) {
                echo json_encode(['success' => false, 'message' => 'Space not found or not available']);
                exit;
            }
            
            // Get facility_id
            $facilityId = $space['facility_id'] ?? null;
            if (!$facilityId) {
                // Try sync if not found
                $facilityId = $this->spaceModel->syncToBookingModule($spaceId);
            }
            
            if (!$facilityId) {
                 echo json_encode(['success' => false, 'message' => 'Facility configuration error']);
                 exit;
            }

            // Use centralized logic in Facility_model
            // This ensures default 8am-10pm slots and consistent buffer logic
            $result = $this->facilityModel->getAvailableTimeSlots($facilityId, $date, $endDate);
            
            echo json_encode($result);

        } catch (Exception $e) {
            error_log('Booking_wizard getTimeSlots error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading time slots: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Step 3: Add Extras/Add-ons
     */
    public function step3($resourceId) {
        try {
            $resource = $this->facilityModel->getById($resourceId);
            if (!$resource) {
                redirect('booking-wizard/step1');
            }
            
            // Get addons available for this resource
            $addons = $this->addonModel->getActive($resourceId);
            
            // Get selected date/time from session
            $bookingData = $_SESSION['booking_data'] ?? [];
        } catch (Exception $e) {
            $resource = null;
            $addons = [];
            $bookingData = [];
        }

        if (!$resource) {
            redirect('booking-wizard/step1');
        }

        $data = [
            'page_title' => 'Add Extras',
            'resource' => $resource,
            'addons' => $addons,
            'booking_data' => $bookingData,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/step3_addons', $data);
    }

    /**
     * Step 4: Customer Information
     */
    public function step4() {
        $bookingData = $_SESSION['booking_data'] ?? [];
        
        if (empty($bookingData['resource_id']) || empty($bookingData['date']) || empty($bookingData['start_time'])) {
            $this->setFlashMessage('danger', 'Please complete previous steps.');
            redirect('booking-wizard/step1');
        }

        $data = [
            'page_title' => 'Your Information',
            'booking_data' => $bookingData,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/step4_customer_info', $data);
    }

    /**
     * Step 5: Review and Payment
     */
    public function step5() {
        $bookingData = $_SESSION['booking_data'] ?? [];
        
        if (empty($bookingData['resource_id']) || empty($bookingData['customer_email'])) {
            $this->setFlashMessage('danger', 'Please complete previous steps.');
            redirect('booking-wizard/step1');
        }

        try {
            $resource = $this->facilityModel->getById($bookingData['resource_id']);
            
            // Calculate pricing
            $baseAmount = $this->facilityModel->calculatePrice(
                $bookingData['resource_id'],
                $bookingData['date'],
                $bookingData['start_time'],
                $bookingData['end_time'],
                $bookingData['booking_type'] ?? 'hourly',
                $bookingData['quantity'] ?? 1,
                false // isMember - would check customer membership status
            );
            
            // Calculate addons total
            $addonsTotal = 0;
            if (!empty($bookingData['addons'])) {
                foreach ($bookingData['addons'] as $addonId => $qty) {
                    $addon = $this->addonModel->getById($addonId);
                    if ($addon) {
                        $addonsTotal += floatval($addon['price']) * intval($qty);
                    }
                }
            }
            
            // Apply promo code if provided
            $discountAmount = 0;
            if (!empty($bookingData['promo_code'])) {
                $promoValidation = $this->promoCodeModel->validateCode(
                    $bookingData['promo_code'],
                    $baseAmount + $addonsTotal,
                    [$bookingData['resource_id']],
                    array_keys($bookingData['addons'] ?? [])
                );
                
                if ($promoValidation['valid']) {
                    $discountAmount = $promoValidation['discount_amount'];
                }
            }
            
            $subtotal = $baseAmount + $addonsTotal;
            $total = $subtotal - $discountAmount + floatval($resource['security_deposit'] ?? 0);
            
            // Get cancellation policy
            $cancellationPolicy = $this->cancellationPolicyModel->getDefault();
            
            // Get available payment gateways
            $gateways = $this->gatewayModel->getActive();
            
            // Store calculated amounts in session
            $bookingData['base_amount'] = $baseAmount;
            $bookingData['addons_total'] = $addonsTotal;
            $bookingData['discount_amount'] = $discountAmount;
            $bookingData['subtotal'] = $subtotal;
            $bookingData['total_amount'] = $total;
            $bookingData['security_deposit'] = floatval($resource['security_deposit'] ?? 0);
            $_SESSION['booking_data'] = $bookingData;
            
        } catch (Exception $e) {
            error_log('Booking_wizard step5 error: ' . $e->getMessage());
            $resource = null;
            $gateways = [];
            $cancellationPolicy = null;
        }

        $data = [
            'page_title' => 'Review & Payment',
            'resource' => $resource,
            'booking_data' => $bookingData,
            'gateways' => $gateways,
            'cancellation_policy' => $cancellationPolicy,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/step5_review_payment', $data);
    }

    /**
     * Save booking data to session (AJAX)
     */
    public function saveStep() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $step = intval($_POST['step'] ?? 0);
        $rawData = $_POST['data'] ?? '';
        
        // Decode JSON data if it's a string
        if (is_string($rawData)) {
            $data = json_decode($rawData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Booking wizard saveStep: JSON decode error - ' . json_last_error_msg());
                echo json_encode(['success' => false, 'message' => 'Invalid data format']);
                exit;
            }
        } else {
            $data = $rawData;
        }
        
        // Validate that data is an array
        if (!is_array($data)) {
            log_message('error', 'Booking wizard saveStep: Data is not an array after decoding');
            echo json_encode(['success' => false, 'message' => 'Invalid data structure']);
            exit;
        }
        
        if (!isset($_SESSION['booking_data'])) {
            $_SESSION['booking_data'] = [];
        }
        
        // Merge step data
        $_SESSION['booking_data'] = array_merge($_SESSION['booking_data'], $data);
        
        log_message('debug', 'Booking wizard saveStep: Step ' . $step . ' data saved successfully');
        
        echo json_encode(['success' => true, 'next_step' => $step + 1]);
        exit;
    }

    /**
     * Validate promo code (AJAX)
     */
    public function validatePromoCode() {
        header('Content-Type: application/json');
        
        $code = sanitize_input($_POST['code'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $resourceIds = !empty($_POST['resource_ids']) ? json_decode($_POST['resource_ids'], true) : [];
        $addonIds = !empty($_POST['addon_ids']) ? json_decode($_POST['addon_ids'], true) : [];
        
        if (!$code) {
            echo json_encode(['valid' => false, 'message' => 'Please enter a promo code']);
            exit;
        }
        
        try {
            $result = $this->promoCodeModel->validateCode($code, $amount, $resourceIds, $addonIds);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['valid' => false, 'message' => 'Error validating promo code']);
        }
        exit;
    }

    /**
     * Finalize booking
     */
    public function finalize() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request.');
            redirect('booking-wizard/step1');
        }
        
        $bookingData = $_SESSION['booking_data'] ?? [];
        
        if (empty($bookingData['resource_id']) || empty($bookingData['customer_email'])) {
            $this->setFlashMessage('danger', 'Please complete all steps.');
            redirect('booking-wizard/step1');
        }
        
        try {
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();
            
            // SECURITY: Recalculate all prices to prevent TOCTOU (Time-of-Check to Time-of-Use) attacks
            // Never trust calculated values from session - recalculate from raw inputs
            $resource = $this->facilityModel->getById($bookingData['resource_id']);
            if (!$resource) {
                throw new Exception('Resource not found');
            }
            
            // Recalculate base amount
            $baseAmount = $this->facilityModel->calculatePrice(
                $bookingData['resource_id'],
                $bookingData['date'],
                $bookingData['start_time'],
                $bookingData['end_time'],
                $bookingData['booking_type'] ?? 'hourly',
                $bookingData['quantity'] ?? 1,
                false // isMember - would check customer membership status
            );
            
            // Recalculate addons total
            $addonsTotal = 0;
            if (!empty($bookingData['addons'])) {
                foreach ($bookingData['addons'] as $addonId => $qty) {
                    $addon = $this->addonModel->getById($addonId);
                    if ($addon) {
                        $addonsTotal += floatval($addon['price']) * intval($qty);
                    }
                }
            }
            
            // Recalculate discount amount (re-validate promo code)
            $discountAmount = 0;
            $promoCode = $bookingData['promo_code'] ?? null;
            if (!empty($promoCode)) {
                $promoValidation = $this->promoCodeModel->validateCode(
                    $promoCode,
                    $baseAmount + $addonsTotal,
                    [$bookingData['resource_id']],
                    array_keys($bookingData['addons'] ?? [])
                );
                
                if ($promoValidation['valid']) {
                    $discountAmount = $promoValidation['discount_amount'];
                } else {
                    // Invalid promo code - clear it
                    $promoCode = null;
                    $discountAmount = 0;
                }
            }
            
            // Calculate final totals
            $subtotal = $baseAmount + $addonsTotal;
            $securityDeposit = floatval($resource['security_deposit'] ?? 0);
            $finalTotal = $subtotal - $discountAmount + $securityDeposit;
            
            // Create booking
            $bookingNumber = $this->bookingModel->getNextBookingNumber();
            $paymentPlan = sanitize_input($_POST['payment_plan'] ?? 'full');
            
            // Handle multi-day bookings
            $endDate = $bookingData['end_date'] ?? $bookingData['date'];
            $isRecurring = !empty($bookingData['is_recurring']) && intval($bookingData['is_recurring']) == 1;
            $recurringPattern = $isRecurring ? sanitize_input($bookingData['recurring_pattern'] ?? '') : null;
            $recurringEndDate = $isRecurring && !empty($bookingData['recurring_end_date']) ? sanitize_input($bookingData['recurring_end_date']) : null;
            
            // Calculate duration for multi-day bookings
            $startDateTime = new DateTime($bookingData['date'] . ' ' . $bookingData['start_time']);
            $endDateTime = new DateTime($endDate . ' ' . $bookingData['end_time']);
            $duration = $endDateTime->diff($startDateTime);
            $durationHours = ($duration->days * 24) + $duration->h + ($duration->i / 60);
            
            $bookingRecord = [
                'booking_number' => $bookingNumber,
                'facility_id' => $bookingData['resource_id'], // Legacy field
                'customer_name' => sanitize_input($bookingData['customer_name'] ?? ''),
                'customer_email' => sanitize_input($bookingData['customer_email'] ?? ''),
                'customer_phone' => sanitize_input($bookingData['customer_phone'] ?? ''),
                'customer_address' => sanitize_input($bookingData['customer_address'] ?? ''),
                'booking_date' => $bookingData['date'],
                'start_time' => $bookingData['start_time'],
                'end_time' => $bookingData['end_time'],
                'duration_hours' => $durationHours,
                'number_of_guests' => intval($bookingData['guests'] ?? 0),
                'booking_type' => $bookingData['booking_type'] ?? 'hourly',
                'base_amount' => $baseAmount, // Use recalculated value
                'subtotal' => $subtotal, // Use recalculated value
                'discount_amount' => $discountAmount, // Use recalculated value
                'security_deposit' => $securityDeposit, // Use recalculated value
                'total_amount' => $finalTotal, // Use recalculated value
                'paid_amount' => 0,
                'balance_amount' => $finalTotal, // Use recalculated total
                'currency' => 'NGN',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_plan' => $paymentPlan,
                'promo_code' => $promoCode, // Use validated promo code
                'booking_notes' => sanitize_input($bookingData['notes'] ?? ''),
                'special_requests' => sanitize_input($bookingData['special_requests'] ?? ''),
                'booking_source' => 'online',
                'is_recurring' => $isRecurring ? 1 : 0,
                'recurring_pattern' => $recurringPattern,
                'recurring_end_date' => $recurringEndDate,
                'created_by' => null
            ];
            
            $bookingId = $this->bookingModel->create($bookingRecord);
            if (!$bookingId) {
                throw new Exception('Failed to create booking');
            }
            
            // Create booking resources
            $this->bookingResourceModel->addResource(
                $bookingId,
                $bookingData['resource_id'],
                $bookingData['date'] . ' ' . $bookingData['start_time'],
                $bookingData['date'] . ' ' . $bookingData['end_time'],
                $bookingData['quantity'] ?? 1,
                $this->facilityModel->getById($bookingData['resource_id'])['hourly_rate'],
                $bookingData['booking_type'] ?? 'hourly'
            );
            
            // Create booking addons
            if (!empty($bookingData['addons'])) {
                foreach ($bookingData['addons'] as $addonId => $quantity) {
                    $addon = $this->addonModel->getById($addonId);
                    if ($addon) {
                        $this->bookingAddonModel->addAddon(
                            $bookingId,
                            $addonId,
                            intval($quantity),
                            floatval($addon['price'])
                        );
                    }
                }
            }
            
            // Create payment schedule
            $this->paymentScheduleModel->createSchedule(
                $bookingId,
                $paymentPlan,
                $finalTotal, // Use recalculated total
                !empty($_POST['deposit_percentage']) ? floatval($_POST['deposit_percentage']) : null
            );
            
            // Create booking slots (handles multi-day bookings)
            $this->bookingModel->createSlots(
                $bookingId,
                $bookingData['resource_id'],
                $bookingData['date'],
                $bookingData['start_time'],
                $endDate, // Use endDate for multi-day bookings
                $bookingData['end_time']
            );
            
            // Process initial payment if provided
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? '');
            $initialPayment = floatval($_POST['initial_payment'] ?? 0);
            
            if ($paymentMethod && $initialPayment > 0) {
                if ($paymentMethod === 'gateway') {
                    $gatewayCode = sanitize_input($_POST['gateway_code'] ?? '');
                    
                    // Initialize payment gateway
                    require_once BASEPATH . 'libraries/Payment_gateway.php';
                    $gateway = $this->gatewayModel->getByCode($gatewayCode);
                    
                    if ($gateway && $gateway['is_active']) {
                        $gatewayConfig = [
                            'public_key' => $gateway['public_key'],
                            'private_key' => $gateway['private_key'],
                            'secret_key' => $gateway['secret_key'] ?? '',
                            'test_mode' => $gateway['test_mode'],
                            'callback_url' => base_url('payment/callback'),
                            'additional_config' => json_decode($gateway['additional_config'] ?? '{}', true)
                        ];
                        
                        $paymentGateway = new Payment_gateway($gatewayCode, $gatewayConfig);
                        
                        $customer = [
                            'email' => $bookingData['customer_email'],
                            'name' => $bookingData['customer_name'] ?? '',
                            'phone' => $bookingData['customer_phone'] ?? ''
                        ];
                        
                        $metadata = [
                            'transaction_ref' => 'BKG-' . $bookingNumber,
                            'payment_type' => 'booking_payment',
                            'reference_id' => $bookingId,
                            'description' => 'Booking payment for ' . $bookingNumber
                        ];
                        
                        $paymentResult = $paymentGateway->initialize(
                            $initialPayment,
                            'NGN',
                            $customer,
                            $metadata
                        );
                        
                        if ($paymentResult['success']) {
                            // Redirect to payment gateway
                            $pdo->commit();
                            unset($_SESSION['booking_data']);
                            echo json_encode([
                                'success' => true,
                                'redirect' => $paymentResult['authorization_url'],
                                'booking_number' => $bookingNumber
                            ]);
                            exit;
                        }
                    }
                } elseif ($paymentMethod === 'cash' || $paymentMethod === 'bank') {
                    // Record offline payment
                    $paymentData = [
                        'booking_id' => $bookingId,
                        'payment_number' => $this->paymentModel->getNextPaymentNumber(),
                        'payment_date' => date('Y-m-d'),
                        'payment_type' => $paymentPlan === 'deposit' ? 'deposit' : 'partial',
                        'payment_method' => $paymentMethod,
                        'amount' => $initialPayment,
                        'currency' => 'NGN',
                        'status' => 'completed',
                        'created_by' => $this->session['user_id'] ?? null
                    ];
                    
                    $this->paymentModel->create($paymentData);
                    $this->bookingModel->addPayment($bookingId, $initialPayment);
                }
            }
            
            $pdo->commit();
            
            // Clear booking data from session
            unset($_SESSION['booking_data']);
            
            $this->setFlashMessage('success', 'Booking created successfully! Booking Number: ' . $bookingNumber);
            redirect('booking-wizard/confirmation/' . $bookingId);
            
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Booking_wizard finalize error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to create booking: ' . $e->getMessage());
            redirect('booking-wizard/step5');
        }
    }

    /**
     * Booking confirmation page
     */
    public function confirmation($bookingId) {
        try {
            $booking = $this->bookingModel->getWithFacility($bookingId);
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('booking-wizard/step1');
            }
            
            $resources = $this->bookingModel->getResources($bookingId);
            $addons = $this->bookingModel->getAddons($bookingId);
            $paymentSchedule = $this->bookingModel->getPaymentSchedule($bookingId);
        } catch (Exception $e) {
            $booking = null;
            $resources = [];
            $addons = [];
            $paymentSchedule = [];
        }

        $data = [
            'page_title' => 'Booking Confirmation',
            'booking' => $booking,
            'resources' => $resources,
            'addons' => $addons,
            'payment_schedule' => $paymentSchedule,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/confirmation', $data);
    }
    
    private function calculateDuration($date, $startTime, $endTime) {
        try {
            $start = new DateTime($date . ' ' . $startTime);
            $end = new DateTime($date . ' ' . $endTime);
            $duration = $end->diff($start);
            return $duration->h + ($duration->i / 60);
        } catch (Exception $e) {
            return 0;
        }
    }
}

