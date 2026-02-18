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
    private $userModel;
    private $customerPortalUserModel;
    private $paymentTransactionModel;
    private $invoiceModel;
    private $entityModel;
    private $customerModel;

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
        $this->userModel = $this->loadModel('User_model');
        $this->customerPortalUserModel = $this->loadModel('Customer_portal_user_model');
        $this->paymentTransactionModel = $this->loadModel('Payment_transaction_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->entityModel = $this->loadModel('Entity_model');
        $this->customerModel = $this->loadModel('Customer_model');
    }
    
    public function index() {
        // Handle payment action for existing bookings from customer portal
        $bookingId = intval($_GET['booking_id'] ?? 0);
        $action = $_GET['action'] ?? '';
        
        if ($bookingId && $action === 'pay') {
            $this->makePayment($bookingId);
            return;
        }
        
        redirect('booking-wizard/step1');
    }

    /**
     * Make payment for an existing unpaid booking
     * Called from customer portal "Make Payment" button
     */
    public function makePayment($bookingId) {
        $bookingId = intval($bookingId);
        
        if (!$bookingId) {
            $this->setFlashMessage('danger', 'Invalid booking.');
            redirect('booking-wizard/step1');
            return;
        }
        
        try {
            // Load the booking
            $booking = $this->bookingModel->getById($bookingId);
            
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('booking-wizard/step1');
                return;
            }
            
            // Check if booking has a balance to pay
            $balance = floatval($booking['balance_amount'] ?? $booking['total_amount']);
            if ($balance <= 0) {
                $this->setFlashMessage('info', 'This booking is already fully paid.');
                redirect('customer-portal/bookings');
                return;
            }
            
            // Check booking isn't cancelled
            if (in_array($booking['status'], ['cancelled', 'refunded'])) {
                $this->setFlashMessage('danger', 'Cannot make payment for a cancelled booking.');
                redirect('customer-portal/bookings');
                return;
            }
            
            // Find active payment gateway (Paystack)
            $gatewayCode = 'paystack';
            $gatewayPath = BASEPATH . 'libraries/Payment_gateway.php';
            
            if (!file_exists($gatewayPath)) {
                $this->setFlashMessage('danger', 'Payment gateway not available. Please contact support.');
                redirect('customer-portal/view-booking/' . $bookingId);
                return;
            }
            
            require_once $gatewayPath;
            
            $gateway = $this->gatewayModel->getByCode($gatewayCode);
            
            if (!$gateway || !$gateway['is_active']) {
                $this->setFlashMessage('danger', 'Online payment is currently unavailable. Please contact support.');
                redirect('customer-portal/view-booking/' . $bookingId);
                return;
            }
            
            $gatewayConfig = [
                'public_key'  => $gateway['public_key'],
                'private_key' => $gateway['private_key'],
                'secret_key'  => $gateway['secret_key'] ?? '',
                'test_mode'   => $gateway['test_mode'],
                'callback_url' => base_url('payment/callback'),
                'additional_config' => json_decode($gateway['additional_config'] ?? '{}', true)
            ];
            
            $paymentGateway = new \Payment_gateway($gatewayCode, $gatewayConfig);
            
            $customer = [
                'email' => $booking['customer_email'],
                'name'  => $booking['customer_name'] ?? '',
                'phone' => $booking['customer_phone'] ?? ''
            ];
            
            // Generate unique transaction reference
            $bookingNumber = $booking['booking_number'] ?? ('BK-' . $bookingId);
            $transactionRef = 'BKG-' . $bookingNumber . '-' . time();
            
            $metadata = [
                'transaction_ref' => $transactionRef,
                'payment_type'    => 'booking_payment',
                'reference_id'    => $bookingId,
                'booking_id'      => $bookingId,
                'description'     => 'Payment for booking ' . $bookingNumber
            ];
            
            // Create payment transaction record
            $transactionData = [
                'transaction_ref'  => $transactionRef,
                'payment_type'     => 'booking_payment',
                'reference_id'     => $bookingId,
                'gateway_code'     => $gatewayCode,
                'amount'           => $balance,
                'currency'         => 'NGN',
                'status'           => 'pending',
                'customer_email'   => $booking['customer_email'],
                'customer_name'    => $booking['customer_name'] ?? '',
                'description'      => 'Payment for booking ' . $bookingNumber,
                'created_at'       => date('Y-m-d H:i:s')
            ];
            $this->paymentTransactionModel->create($transactionData);
            
            // Initialize Paystack
            $paymentResult = $paymentGateway->initialize(
                $balance,
                'NGN',
                $customer,
                $metadata
            );
            
            if ($paymentResult['success'] && !empty($paymentResult['authorization_url'])) {
                error_log("makePayment: Redirecting booking #$bookingId to Paystack");
                redirect($paymentResult['authorization_url']);
                exit;
            } else {
                error_log('makePayment: Gateway initialization failed: ' . json_encode($paymentResult));
                $this->setFlashMessage('danger', 'Could not initialize payment. Please try again later.');
                redirect('customer-portal/view-booking/' . $bookingId);
            }
            
        } catch (Exception $e) {
            error_log('makePayment error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Payment error: ' . $e->getMessage());
            redirect('customer-portal/view-booking/' . $bookingId);
        }
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
     * View more information about a space on the front end
     */
    public function space_details($spaceId) {
        $spaceId = intval($spaceId);
        
        try {
            $space = $this->spaceModel->getWithProperty($spaceId);
            if (!$space || empty($space['is_bookable'])) {
                $this->setFlashMessage('danger', 'Space not found or not available for public booking.');
                redirect('booking-wizard/step1');
            }
            
            // Get config for rates and booking types
            $config = $this->spaceModel->getBookableConfig($spaceId);
            if ($config) {
                $space['booking_types'] = json_decode($config['booking_types'], true) ?: ['hourly', 'daily'];
                $pricingRules = json_decode($config['pricing_rules'] ?? '{}', true) ?: [];
                $space['hourly_rate'] = $pricingRules['base_hourly'] ?? $pricingRules['hourly'] ?? 0;
                $space['daily_rate'] = $pricingRules['base_daily'] ?? $pricingRules['daily'] ?? 0;
            } else {
                $space['booking_types'] = ['hourly', 'daily'];
            }
            
            // Get photos
            $space['photos'] = $this->spaceModel->getPhotos($spaceId);
            
            // Get amenities
            $space['amenities'] = json_decode($space['amenities'] ?? '[]', true) ?: [];
            
        } catch (Exception $e) {
            error_log('Booking_wizard space_details error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'An error occurred while loading space details.');
            redirect('booking-wizard/step1');
        }

        $data = [
            'page_title' => $space['space_name'] . ' - Space Details',
            'space' => $space,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/space_details', $data);
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
        

        
        if (!$spaceId || !$date) {

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
            
            // Calculate resource cost based on booking type and duration
            $resourceCost = 0;
            if (!empty($bookingData['date']) && !empty($bookingData['start_time']) && !empty($bookingData['end_time'])) {
                $resourceCost = $this->facilityModel->calculatePrice(
                    $resourceId,
                    $bookingData['date'],
                    $bookingData['start_time'],
                    $bookingData['end_time'],
                    $bookingData['booking_type'] ?? 'hourly',
                    1, // quantity
                    false // isMember
                );
            }
            
            // Store the calculated resource cost in session
            $_SESSION['booking_data']['base_amount'] = $resourceCost;
            
        } catch (Exception $e) {
            $resource = null;
            $addons = [];
            $bookingData = [];
            $resourceCost = 0;
        }

        if (!$resource) {
            redirect('booking-wizard/step1');
        }

        $data = [
            'page_title' => 'Add Extras',
            'resource' => $resource,
            'addons' => $addons,
            'booking_data' => $bookingData,
            'resource_cost' => $resourceCost,
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
            $addonsData = $bookingData['addons'] ?? [];
            
            // Handle if addons is a JSON string
            if (is_string($addonsData)) {
                $addonsData = json_decode($addonsData, true) ?: [];
            }
            
            // Only iterate if it's an array
            if (is_array($addonsData) && !empty($addonsData)) {
                foreach ($addonsData as $addonId => $qty) {
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
        if (is_string($rawData) && !is_array($rawData)) {
            $decoded = json_decode($rawData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Not JSON, might already be an array from form data
                // Check if it looks like form data came through as array
                $data = $rawData;
            } else {
                $data = $decoded;
            }
        } else {
            $data = $rawData;
        }
        
        // Validate that data is an array
        if (!is_array($data)) {
            error_log('Booking wizard saveStep: Data is not an array - received: ' . gettype($data));
            echo json_encode(['success' => false, 'message' => 'Invalid data structure']);
            exit;
        }
        
        if (!isset($_SESSION['booking_data'])) {
            $_SESSION['booking_data'] = [];
        }
        
        // Merge step data
        $_SESSION['booking_data'] = array_merge($_SESSION['booking_data'], $data);
        
        // VALIDATION: Check availability for Step 2 (Date & Time)
        if ($step === 2) {
            $bookingData = $_SESSION['booking_data'];
            if (!empty($bookingData['date']) && !empty($bookingData['start_time']) && !empty($bookingData['end_time'])) {
                // Ensure Facility Model is loaded
                if (!$this->facilityModel) {
                     $this->facilityModel = $this->loadModel('Facility_model');
                }
                
                $resourceId = $bookingData['resource_id'] ?? 0;
                // Get facility_id (might be different from space_id)
                $space = $this->spaceModel->getById($resourceId);
                $facilityId = $space['facility_id'] ?? $resourceId;
                
                // Check availability
                // Note: end_date might be different for multi-day
                $checkEndDate = $bookingData['end_date'] ?? $bookingData['date'];
                
                $isAvailable = $this->facilityModel->checkAvailability(
                    $facilityId,
                    $bookingData['date'],
                    $bookingData['start_time'],
                    $bookingData['end_time'],
                    null, // No exclude ID (new booking)
                    $checkEndDate
                );
                
                if (!$isAvailable) {
                    echo json_encode(['success' => false, 'message' => 'Selected time slot is no longer available.']);
                    exit;
                }
            }
        }

        error_log('Booking wizard saveStep: Step ' . $step . ' data saved successfully');
        
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
        // Define log variables for debugging
        $logFile = ROOTPATH . 'logs/debug_wizard_log.txt';
        $timestamp = date('Y-m-d H:i:s');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request.');
            redirect('booking-wizard/step1');
        }
        
        $bookingData = $_SESSION['booking_data'] ?? [];
        
        if (empty($bookingData['resource_id']) || empty($bookingData['customer_email'])) {
            error_log("FINALIZE: Missing data - resource_id: " . ($bookingData['resource_id'] ?? 'null') . " email: " . ($bookingData['customer_email'] ?? 'null'));
            $this->setFlashMessage('danger', 'Please complete all steps.');
            redirect('booking-wizard/step1');
        }
        
        try {
            $pdo = $this->db->getConnection();
            $pdo->beginTransaction();
            error_log("FINALIZE: Transaction started");
            
            // Recalculate all prices to prevent TOCTOU (Time-of-Check to Time-of-Use) attacks
            // Never trust calculated values from session - recalculate from raw inputs
            $resource = $this->facilityModel->getById($bookingData['resource_id']);
            if (!$resource) {
                throw new Exception('Resource not found');
            }
            
            // CRITICAL: Final Availability Check
            // Prevents race conditions where a slot was taken between Step 2 and Finalize
            $facilityId = $resource['facility_id'] ?? $resource['id']; // Use facility ID if mapped, else resource ID
            $checkEndDate = $bookingData['end_date'] ?? $bookingData['date'];
            
            $isAvailable = $this->facilityModel->checkAvailability(
                $facilityId,
                $bookingData['date'],
                $bookingData['start_time'],
                $bookingData['end_time'],
                null,
                $checkEndDate
            );
            
            if (!$isAvailable) {
                // Rollback is automatic if exception thrown before commit? 
                // Actually we haven't done any SQL yet (except selects), so just throw
                throw new Exception('The selected time slot has just been booked by another user. Please select a different time.');
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
            $addonsData2 = $bookingData['addons'] ?? [];
            if (is_string($addonsData2)) {
                $addonsData2 = json_decode($addonsData2, true) ?: [];
            }
            if (is_array($addonsData2) && !empty($addonsData2)) {
                foreach ($addonsData2 as $addonId => $qty) {
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
            
            // Calculate tax (VAT) if applicable
            $taxAmount = 0;
            $taxRate = 0;
            try {
                $taxModel = $this->loadModel('Tax_model');
                if ($taxModel) {
                    // Try to get VAT tax or any active sales tax
                    $vatTax = $taxModel->getByCode('VAT');
                    if (!$vatTax) {
                        // Try to get any active tax
                        $activeTaxes = $taxModel->getActive();
                        if (!empty($activeTaxes)) {
                            $vatTax = $activeTaxes[0]; // Use first active tax
                        }
                    }
                    
                    if ($vatTax) {
                        $taxCalculation = $taxModel->calculateTax($subtotal - $discountAmount, $vatTax['id']);
                        $taxAmount = $taxCalculation['tax_amount'] ?? 0;
                        $taxRate = floatval($vatTax['rate'] ?? 0);
                        error_log("Booking tax calculation: Base=" . ($subtotal - $discountAmount) . ", Tax={$taxAmount}, Rate={$taxRate}%");
                    }
                }
            } catch (Exception $taxEx) {
                error_log("Booking_wizard: Tax calculation error - " . $taxEx->getMessage());
                $taxAmount = 0;
            }
            
            $finalTotal = $subtotal - $discountAmount + $taxAmount + $securityDeposit;
            
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
            
            // Handle guest user creation if needed (creates in customer_portal_users table)
            $createdById = null;
            if ($this->customerPortalUserModel && !empty($bookingData['customer_email'])) {
                $createdById = $this->createGuestUser(
                    $bookingData['customer_email'],
                    $bookingData['customer_name'] ?? '',
                    $bookingData['customer_phone'] ?? ''
                );
            }

            // Create booking with all fields (AutoMigration ensures columns exist)
            $bookingRecord = [
                'booking_number' => $bookingNumber,
                'space_id' => $bookingData['resource_id'],  // For backwards compatibility with older schema
                'facility_id' => $bookingData['resource_id'],
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
                'base_amount' => $baseAmount,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'security_deposit' => $securityDeposit,
                'total_amount' => $finalTotal,
                'paid_amount' => 0,
                'balance_amount' => $finalTotal,
                'currency' => 'NGN',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_plan' => $paymentPlan,
                'promo_code' => $promoCode,
                'booking_notes' => sanitize_input($bookingData['notes'] ?? ''),
                'special_requests' => sanitize_input($bookingData['special_requests'] ?? ''),
                'booking_source' => 'online',
                'is_recurring' => $isRecurring ? 1 : 0,
                'recurring_pattern' => $recurringPattern,
                'recurring_end_date' => $recurringEndDate,
                'created_by' => $createdById,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Log the record before insert
            file_put_contents($logFile, "[$timestamp] INSERTING BOOKING: " . print_r($bookingRecord, true) . "\n", FILE_APPEND);
            
            if (empty($bookingRecord['booking_number'])) {
                 file_put_contents($logFile, "[$timestamp] ERROR: Empty booking number\n", FILE_APPEND);
                 throw new Exception('System failed to generate booking number');
            }

            $bookingId = $this->bookingModel->create($bookingRecord);
            
            if (!$bookingId) {
                file_put_contents($logFile, "[$timestamp] ERROR: Model create returned false (Insert Failed)\n", FILE_APPEND);
                throw new Exception('Failed to create booking - Database Insert Failed');
            }
            
            file_put_contents($logFile, "[$timestamp] SUCCESS: Created Booking ID: $bookingId\n", FILE_APPEND);
            
            // CRITICAL FIX: Commit the booking IMMEDIATELY to prevent rollback
            // from optional operations (resources, addons, slots, invoice) that might fail
            if ($pdo->inTransaction()) {
                $pdo->commit();
                file_put_contents($logFile, "[$timestamp] BOOKING COMMITTED: ID $bookingId now persisted\n", FILE_APPEND);
            }
            
            // All subsequent operations are optional and run OUTSIDE the transaction
            // They use their own implicit autocommit
            
            // Create booking resources (optional - may fail if table doesn't exist)
            try {
                if ($this->bookingResourceModel) {
                    $this->bookingResourceModel->addResource(
                        $bookingId,
                        $bookingData['resource_id'],
                        $bookingData['date'] . ' ' . $bookingData['start_time'],
                        $bookingData['date'] . ' ' . $bookingData['end_time'],
                        $bookingData['quantity'] ?? 1,
                        $this->facilityModel->getById($bookingData['resource_id'])['hourly_rate'] ?? 0,
                        $bookingData['booking_type'] ?? 'hourly'
                    );
                }
            } catch (Exception $e) {
                error_log('Booking wizard: Failed to create booking resource - ' . $e->getMessage());
                // Continue anyway - this is optional
            }
            
            // Create booking addons (optional)
            try {
                $addonsData3 = $bookingData['addons'] ?? [];
                if (is_string($addonsData3)) {
                    $addonsData3 = json_decode($addonsData3, true) ?: [];
                }
                if (is_array($addonsData3) && !empty($addonsData3) && $this->bookingAddonModel) {
                    foreach ($addonsData3 as $addonId => $quantity) {
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
            } catch (Exception $e) {
                error_log('Booking wizard: Failed to create booking addons - ' . $e->getMessage());
                // Continue anyway
            }
            
            // Create payment schedule (optional)
            try {
                if ($this->paymentScheduleModel) {
                    $this->paymentScheduleModel->createSchedule(
                        $bookingId,
                        $paymentPlan,
                        $finalTotal,
                        !empty($_POST['deposit_percentage']) ? floatval($_POST['deposit_percentage']) : null
                    );
                }
            } catch (Exception $e) {
                error_log('Booking wizard: Failed to create payment schedule - ' . $e->getMessage());
                // Continue anyway
            }
            
            // Create booking slots (optional - handles multi-day bookings)
            try {
                $this->bookingModel->createSlots(
                    $bookingId,
                    $bookingData['resource_id'],
                    $bookingData['date'],
                    $bookingData['start_time'],
                    $endDate,
                    $bookingData['end_time']
                );
            } catch (Exception $e) {
                error_log('Booking wizard: Failed to create booking slots - ' . $e->getMessage());
                // Continue anyway
            }
            
            // Create invoice for the booking
            try {
                $bookingForInvoice = $bookingRecord;
                $bookingForInvoice['booking_number'] = $bookingNumber;
                $bookingForInvoice['facility_name'] = $resource['space_name'] ?? $resource['name'] ?? 'Space';
                $this->createBookingInvoice($bookingId, $bookingForInvoice);
            } catch (Exception $e) {
                error_log('Booking wizard: Failed to create invoice - ' . $e->getMessage());
                // Continue anyway - invoice creation is optional
            }
            
            // NOTE: Transaction was already committed immediately after booking creation
            // All operations above (resources, addons, slots, invoice) run with autocommit
            
            // Calculate initial payment based on payment plan
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? '');
            $paymentPlan = sanitize_input($_POST['payment_plan'] ?? 'full');
            
            // Determine initial payment amount based on plan
            $initialPayment = $finalTotal; // Default to full payment
            if ($paymentPlan === 'deposit') {
                $initialPayment = $finalTotal * 0.5; // 50% deposit
            } elseif ($paymentPlan === 'installment') {
                $initialPayment = $finalTotal / 3; // First of 3 installments
            } elseif ($paymentPlan === 'pay_later') {
                $initialPayment = 0; // Pay later
            }
            
            error_log("FINALIZE: Payment Method: $paymentMethod, Initial Payment: $initialPayment");
            
            // Process online payment via gateway
            if ($paymentMethod === 'gateway' && $initialPayment > 0) {
                $gatewayCode = sanitize_input($_POST['gateway_code'] ?? 'paystack');
                error_log("FINALIZE: Using gateway: $gatewayCode");
                
                // Initialize payment gateway
                $gatewayPath = BASEPATH . 'libraries/Payment_gateway.php';
                if (!file_exists($gatewayPath)) {
                    $gatewayPath = APPPATH . 'libraries/Payment_gateway.php';
                }
                error_log("FINALIZE: Gateway path: $gatewayPath, exists: " . (file_exists($gatewayPath) ? 'YES' : 'NO'));
                
                if (file_exists($gatewayPath)) {
                    require_once $gatewayPath;
                    
                    $gateway = $this->gatewayModel->getByCode($gatewayCode);
                    error_log("FINALIZE: Gateway from DB: " . ($gateway ? json_encode(['name' => $gateway['gateway_name'] ?? '', 'active' => $gateway['is_active'] ?? 0]) : 'NULL'));
                    
                    if ($gateway && $gateway['is_active']) {
                        $gatewayConfig = [
                            'public_key' => $gateway['public_key'],
                            'private_key' => $gateway['private_key'],
                            'secret_key' => $gateway['secret_key'] ?? '',
                            'test_mode' => $gateway['test_mode'],
                            'callback_url' => base_url('payment/callback'),
                            'additional_config' => json_decode($gateway['additional_config'] ?? '{}', true)
                        ];
                        error_log("FINALIZE: Callback URL: " . base_url('payment/callback'));
                        
                        $paymentGateway = new Payment_gateway($gatewayCode, $gatewayConfig);
                        
                        $customer = [
                            'email' => $bookingData['customer_email'],
                            'name' => $bookingData['customer_name'] ?? '',
                            'phone' => $bookingData['customer_phone'] ?? ''
                        ];
                        
                        // Generate unique transaction reference
                        $transactionRef = 'BKG-' . $bookingNumber . '-' . time();
                        
                        $metadata = [
                            'transaction_ref' => $transactionRef,
                            'payment_type' => 'booking_payment',
                            'reference_id' => $bookingId,
                            'booking_id' => $bookingId,
                            'description' => 'Booking payment for ' . $bookingNumber
                        ];
                        
                        // Create payment transaction record BEFORE calling gateway
                        // This ensures the callback can find and verify the transaction
                        $transactionData = [
                            'transaction_ref' => $transactionRef,
                            'payment_type' => 'booking_payment',
                            'reference_id' => $bookingId,
                            'gateway_code' => $gatewayCode,
                            'amount' => $initialPayment,
                            'currency' => 'NGN',
                            'status' => 'pending',
                            'customer_email' => $bookingData['customer_email'],
                            'customer_name' => $bookingData['customer_name'] ?? '',
                            'description' => 'Booking payment for ' . $bookingNumber,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        $this->paymentTransactionModel->create($transactionData);
                        
                        // Wrap gateway call to prevent transaction rollback on API failure
                        try {
                            $paymentResult = $paymentGateway->initialize(
                                $initialPayment,
                                'NGN',
                                $customer,
                                $metadata
                            );
                        } catch (Exception $gwEx) {
                            error_log("Booking_wizard: Gateway initialization exception: " . $gwEx->getMessage());
                            $paymentResult = ['success' => false, 'message' => $gwEx->getMessage()];
                        }
                        
                        if ($paymentResult['success'] && !empty($paymentResult['authorization_url'])) {
                            // Commit transaction and redirect to payment gateway
                            file_put_contents($logFile, "[$timestamp] GATEWAY SUCCESS - About to commit before redirect\n", FILE_APPEND);
                            if ($pdo->inTransaction()) {
                                $pdo->commit();
                                file_put_contents($logFile, "[$timestamp] TRANSACTION COMMITTED before Paystack redirect\n", FILE_APPEND);
                            } else {
                                file_put_contents($logFile, "[$timestamp] WARNING: No transaction active before redirect!\n", FILE_APPEND);
                            }
                            
                            // SEND PENDING EMAIL with payment link before redirect
                            try {
                                $notificationModel = $this->loadModel('Notification_model');
                                if ($notificationModel) {
                                    $emailBooking = $bookingRecord;
                                    $emailBooking['id'] = $bookingId;
                                    $emailBooking['facility_name'] = $resource['space_name'] ?? $resource['name'] ?? 'Reserved Space';
                                    $notificationModel->sendBookingPendingEmail($emailBooking, $paymentResult['authorization_url']);
                                    file_put_contents($logFile, "[$timestamp] PENDING EMAIL SENT to: " . $bookingData['customer_email'] . "\n", FILE_APPEND);
                                }
                            } catch (Exception $emailEx) {
                                error_log("Booking_wizard: Failed to send pending email - " . $emailEx->getMessage());
                                // Continue anyway - email is not critical for booking flow
                            }
                            
                            unset($_SESSION['booking_data']);
                            
                            // Redirect to Paystack
                            file_put_contents($logFile, "[$timestamp] Redirecting to: " . $paymentResult['authorization_url'] . "\n", FILE_APPEND);
                            redirect($paymentResult['authorization_url']);
                            exit;
                        } else {
                            // Gateway initialization failed, continue with offline booking
                            file_put_contents($logFile, "[$timestamp] GATEWAY FAILED - continuing with offline booking\n", FILE_APPEND);
                            error_log('Gateway initialization failed: ' . json_encode($paymentResult));
                        }
                    }
                }

            } elseif ($paymentMethod === 'cash' || $paymentMethod === 'bank') {
                // Record offline payment intention - no immediate payment
                // Booking is created with unpaid status
            }
            
            // Only commit if transaction is still active
            if ($pdo->inTransaction()) {
                $pdo->commit();
                file_put_contents($logFile, "[$timestamp] TRANSACTION COMMITTED\n", FILE_APPEND);
            }
            
            // Clear booking data from session
            unset($_SESSION['booking_data']);
            
            $this->setFlashMessage('success', 'Booking created successfully! Booking Number: ' . $bookingNumber);
            redirect('booking-wizard/confirmation/' . $bookingId);
            
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                    file_put_contents($logFile, "[$timestamp] TRANSACTION ROLLED BACK\n", FILE_APPEND);
                } catch (Exception $rollbackException) {
                    // Transaction might already be finished
                    error_log('Rollback note: ' . $rollbackException->getMessage());
                }
            }
            // Self-healing: Check for missing space_id column
            if (strpos($e->getMessage(), "Unknown column 'space_id'") !== false) {
                 try {
                     $this->db->query("ALTER TABLE " . $this->db->getPrefix() . "bookings ADD COLUMN space_id INT NULL AFTER booking_number");
                     $this->setFlashMessage('success', 'System update applied. Please click "Confirm Booking" again to proceed.');
                     redirect('booking-wizard/step5');
                     return;
                 } catch (Exception $fixEx) {
                     error_log("Failed to auto-fix missing column: " . $fixEx->getMessage());
                 }
            }
            
            error_log('Booking_wizard finalize error: ' . $e->getMessage());
            
            $logFile = ROOTPATH . 'logs/debug_wizard_log.txt';
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[$timestamp] EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            
            $this->setFlashMessage('danger', 'Failed to create booking: ' . $e->getMessage());
            redirect('booking-wizard/step5');
        }
    }

    /**
     * Booking confirmation page
     */
    public function confirmation($bookingId = null) {
        // Handle both path parameter and query parameter
        if (!$bookingId) {
            $bookingId = $_GET['id'] ?? null;
        }
        
        if (!$bookingId) {
            $this->setFlashMessage('danger', 'No booking ID provided.');
            redirect('booking-wizard/step1');
            return;
        }
        
        error_log("Booking confirmation: Looking for booking ID: $bookingId");
        
        try {
            // Try getWithFacility first, fallback to getById
            $booking = $this->bookingModel->getWithFacility($bookingId);
            if (!$booking) {
                error_log("Booking confirmation: getWithFacility returned null, trying getById");
                $booking = $this->bookingModel->getById($bookingId);
            }
            
            if (!$booking) {
                error_log("Booking confirmation: Booking not found with ID: $bookingId");
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('booking-wizard/step1');
                return;
            }
            
            error_log("Booking confirmation: Found booking #" . ($booking['booking_number'] ?? $bookingId));
            
            $resources = [];
            $addons = [];
            $paymentSchedule = [];
            
            try {
                $resources = $this->bookingModel->getResources($bookingId);
            } catch (Exception $e) {
                error_log("Booking confirmation: Error getting resources: " . $e->getMessage());
            }
            
            try {
                $addons = $this->bookingModel->getAddons($bookingId);
            } catch (Exception $e) {
                error_log("Booking confirmation: Error getting addons: " . $e->getMessage());
            }
            
            try {
                $paymentSchedule = $this->bookingModel->getPaymentSchedule($bookingId);
            } catch (Exception $e) {
                error_log("Booking confirmation: Error getting payment schedule: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log("Booking confirmation: Exception - " . $e->getMessage());
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


    /**
     * Helper to create or find a user for guest bookings
     * Creates account in customer_portal_users table for portal access
     * 
     * @param string $email
     * @param string $name
     * @param string $phone
     * @return int|null User ID
     */
    private function createGuestUser($email, $name, $phone) {
        try {
            // Check if customer portal user already exists
            $user = $this->customerPortalUserModel->getByEmail($email);
            if ($user) {
                error_log("Guest user already exists in customer portal: $email (ID: " . $user['id'] . ")");
                return $user['id'];
            }
            
            // Create new customer portal user
            $password = bin2hex(random_bytes(8)); // Random 16-char password
            
            // Handle name splitting
            $firstName = $name;
            $lastName = '';
            $parts = explode(' ', $name, 2);
            if (count($parts) > 1) {
                $firstName = $parts[0];
                $lastName = $parts[1];
            }
            
            $userData = [
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'status' => 'active',
                'email_verified' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = $this->customerPortalUserModel->create($userData);
            
            if ($userId) {
                try {
                    // Generate password reset token so user can set their own password
                    $resetResult = $this->customerPortalUserModel->requestPasswordReset($email);
                    
                    if ($resetResult['success']) {
                        $token = $resetResult['token'];
                        
                        // Send guest welcome email with customer portal reset link
                        if (file_exists(BASEPATH . '../application/helpers/email_helper.php')) {
                            require_once BASEPATH . '../application/helpers/email_helper.php';
                            
                            if (function_exists('send_guest_welcome_email')) {
                                send_guest_welcome_email($email, $token, $name);
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log("Guest user created but email failed: " . $e->getMessage());
                }
                
                // Link existing bookings to this new customer portal user
                $this->customerPortalUserModel->linkBookingsByEmail($email);
                
                error_log("Created guest user in customer portal: $email (ID: $userId)");
                return $userId;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error creating guest user: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get existing customer or create new one
     * Integrates with accounting module - prevents duplicate customers
     * @param array $booking Booking data
     * @return int|null Customer ID
     */
    private function getOrCreateCustomer($booking) {
        $logFile = 'logs/customer_creation.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " - START getOrCreateCustomer for email: " . ($booking['customer_email'] ?? 'N/A') . "\n", FILE_APPEND);
        
        if (!$this->customerModel) {
            @file_put_contents($logFile, "Customer model not loaded\n", FILE_APPEND);
            return null;
        }
        
        try {
            // ✅ STEP 1: Check if customer already exists by email
            // MODIFIED: Removed "AND status = 'active'" to find ANY existing customer with this email
            // This prevents duplicate entry errors if an inactive customer exists
            $existingCustomer = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "customers` 
                 WHERE email = ?",
                [$booking['customer_email']]
            );
            
            if ($existingCustomer) {
                // Customer exists - just return the ID
                @file_put_contents($logFile, "Found existing customer ID: " . $existingCustomer['id'] . "\n", FILE_APPEND);
                
                // Update customer info if it has changed
                $updateData = [];
                if (($existingCustomer['phone'] ?? '') != ($booking['customer_phone'] ?? '') && !empty($booking['customer_phone'])) {
                    $updateData['phone'] = $booking['customer_phone'];
                }
                if (($existingCustomer['address'] ?? '') != ($booking['customer_address'] ?? '') && !empty($booking['customer_address'])) {
                    $updateData['address'] = $booking['customer_address'];
                }
                
                if (!empty($updateData)) {
                    $this->customerModel->update($existingCustomer['id'], $updateData);
                    @file_put_contents($logFile, "Updated customer info\n", FILE_APPEND);
                }
                
                return $existingCustomer['id'];
            }
            
            // ✅ STEP 2: Customer doesn't exist - create new one
            @file_put_contents($logFile, "Creating new customer\n", FILE_APPEND);
            
            // Generate unique customer code
            $customerCode = $this->customerModel->getNextCustomerCode();
            
            // Get default customer type if available
            $customerTypeId = null;
            try {
                $defaultType = $this->db->fetchOne(
                    "SELECT id FROM `" . $this->db->getPrefix() . "customer_types` 
                     WHERE is_default = 1 OR name = 'Individual' 
                     LIMIT 1"
                );
                if ($defaultType) {
                    $customerTypeId = $defaultType['id'];
                }
            } catch (Exception $e) {
                // Customer types table might not exist
            }
            
            // Create customer record
            $customerData = [
                'customer_code' => $customerCode,
                'customer_type_id' => $customerTypeId,
                'company_name' => $booking['customer_name'] ?? 'Guest', // Use customer name so they appear in Receivables
                'contact_name' => $booking['customer_name'] ?? 'Guest',
                'email' => $booking['customer_email'],
                'phone' => $booking['customer_phone'] ?? '',
                'address' => $booking['customer_address'] ?? '',
                'payment_terms' => 'Immediate',
                'credit_limit' => 0,
                'current_balance' => 0,
                'status' => 'active',
                'notes' => 'Customer created from booking system',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => (($booking['booking_source'] ?? '') === 'online') ? null : ($booking['created_by'] ?? null)
            ];
            
            $customerId = $this->customerModel->create($customerData);
            
            if ($customerId) {
                @file_put_contents($logFile, "Created new customer ID: $customerId with code: $customerCode\n", FILE_APPEND);
                
                // ✅ STEP 3: Create customer ledger entry in accounting
                $this->createCustomerLedgerAccount($customerId, $booking['customer_name'] ?? 'Guest');
                
                return $customerId;
            }
            
            return null;
            
        } catch (Exception $e) {
            @file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            error_log("getOrCreateCustomer error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create ledger account for customer in accounting system
     */
    private function createCustomerLedgerAccount($customerId, $customerName) {
        try {
            if (!$this->accountModel) {
                return;
            }
            
            // Get the parent Accounts Receivable account
            $arParentAccount = $this->accountModel->getByCode('1200');
            
            if (!$arParentAccount) {
                // Try alternate names if 1200 is missing
                $arAccounts = $this->accountModel->getByType('Assets');
                if (is_array($arAccounts)) {
                    foreach ($arAccounts as $acc) {
                        $name = strtolower($acc['account_name'] ?? '');
                        if (strpos($name, 'receivable') !== false || strpos($name, 'ar') !== false) {
                            $arParentAccount = $acc;
                            error_log("Found fallback AR parent account: " . ($acc['account_code'] ?? 'ID: '.$acc['id']));
                            break;
                        }
                    }
                }
            }
            
            if ($arParentAccount) {
                // Create sub-ledger for this customer
                $accountCode = '1200-' . str_pad($customerId, 4, '0', STR_PAD_LEFT);
                
                // Check if account already exists
                $existingAccount = $this->accountModel->getByCode($accountCode);
                
                if (!$existingAccount) {
                    $this->accountModel->create([
                        'account_code' => $accountCode,
                        'account_name' => 'AR - ' . $customerName,
                        'account_type' => 'Assets',
                        'account_subtype' => 'Current Assets',
                        'parent_account_id' => $arParentAccount['id'],
                        'normal_balance' => 'debit',
                        'is_active' => 1,
                        'is_system_account' => 0,
                        'description' => 'Accounts Receivable for customer #' . $customerId,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    error_log("Created AR ledger account: $accountCode for customer: $customerName");
                }
            }
            
        } catch (Exception $e) {
            error_log("createCustomerLedgerAccount error: " . $e->getMessage());
        }
    }
    
    /**
     * Update customer's current balance in accounting
     */
    private function updateCustomerBalance($customerId) {
        try {
            if (!$this->customerModel) {
                return;
            }
            
            // Calculate total outstanding invoices
            $result = $this->db->fetchOne(
                "SELECT COALESCE(SUM(balance_amount), 0) as total_balance 
                 FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE customer_id = ? 
                 AND status IN ('sent', 'partially_paid', 'overdue', 'draft')",
                [$customerId]
            );
            
            $totalBalance = $result ? floatval($result['total_balance']) : 0;
            
            // Update customer record
            $this->customerModel->update($customerId, [
                'current_balance' => $totalBalance,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            error_log("Updated customer #$customerId balance to: $totalBalance");
            
        } catch (Exception $e) {
            error_log("updateCustomerBalance error: " . $e->getMessage());
        }
    }
    
    /**
     * Create invoice for a booking - Full accounting integration
     * @param int $bookingId
     * @param array $booking Booking data
     * @return int|null Invoice ID
     */
    private function createBookingInvoice($bookingId, $booking) {
        $logFile = 'logs/invoice_creation.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " - START createBookingInvoice for Booking $bookingId\n", FILE_APPEND);
        
        try {
            if (!$this->invoiceModel) {
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Invoice model not loaded\n", FILE_APPEND);
                error_log("createBookingInvoice: Invoice model not loaded");
                return null;
            }
            
            // ✅ STEP 1: Get or create customer (prevents duplicates)
            $customerId = $this->getOrCreateCustomer($booking);
            
            if (!$customerId) {
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Failed to obtain Customer ID\n", FILE_APPEND);
            }
            
            // ✅ STEP 2: Check if invoice already exists for this booking
            $existingInvoice = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "invoices` 
                 WHERE reference = ?",
                ['BKG-' . $bookingId]
            );
            
            if ($existingInvoice) {
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Invoice already exists ID: " . $existingInvoice['id'] . "\n", FILE_APPEND);
                return $existingInvoice['id'];
            }
            
            // Calculate amounts
            $subtotal = floatval($booking['subtotal'] ?? $booking['total_amount'] ?? 0);
            $taxRate = floatval($booking['tax_rate'] ?? 0);
            $taxAmount = floatval($booking['tax_amount'] ?? 0);
            $totalAmount = floatval($booking['total_amount'] ?? ($subtotal + $taxAmount));
            $discountAmount = floatval($booking['discount_amount'] ?? 0);
            $paidAmount = floatval($booking['paid_amount'] ?? 0);
            
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Creating invoice: subtotal=$subtotal, tax=$taxAmount, total=$totalAmount\n", FILE_APPEND);
            
            // ✅ STEP 3: Create invoice
            $invoiceData = [
                'invoice_number' => $this->invoiceModel->getNextInvoiceNumber(),
                'customer_id' => $customerId,
                'invoice_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+3 days')), // 3-day grace period for payment
                'reference' => 'BKG-' . $bookingId,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $totalAmount - $paidAmount,
                'currency' => $booking['currency'] ?? 'NGN',
                'status' => $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'sent'),
                'payment_date' => $paidAmount > 0 ? date('Y-m-d') : null,
                'payment_method' => 'Online Payment',
                'notes' => 'Booking #' . ($booking['booking_number'] ?? $bookingId) . ' - Space Booking',
                'terms' => 'Payment due immediately',
                'created_by' => (($booking['booking_source'] ?? '') === 'online') ? null : ($booking['created_by'] ?? null),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $invoiceId = $this->invoiceModel->create($invoiceData);
            
            if ($invoiceId) {
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Created invoice ID: $invoiceId\n", FILE_APPEND);
                
            // ✅ STEP 4: Add invoice line items
                try {
                    // Get Revenue account for the item
                    $revenueAccount = $this->accountModel->getByCode('4100'); // Service Revenue
                    if (!$revenueAccount) {
                        $revenueAccount = $this->accountModel->getByCode('4000'); // General Revenue
                    }
                    if (!$revenueAccount) {
                        // Fallback to any revenue account
                        $revenueAccounts = $this->accountModel->getByType('Revenue');
                        $revenueAccount = is_array($revenueAccounts) && !empty($revenueAccounts) ? $revenueAccounts[0] : null;
                    }

                    $this->invoiceModel->addItem($invoiceId, [
                        'item_description' => 'Space Booking - ' . ($booking['facility_name'] ?? 'Facility'),
                        'quantity' => 1,
                        'unit_price' => $subtotal,
                        'line_total' => $subtotal,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'account_id' => $revenueAccount['id'] ?? null
                    ]);
                } catch (Exception $e) {
                    error_log("createBookingInvoice: Error adding line item: " . $e->getMessage());
                }
                
                // ✅ STEP 5: Link invoice to booking
                $this->bookingModel->update($bookingId, [
                    'invoice_id' => $invoiceId
                ]);
                
                // ✅ STEP 6: Update customer balance
                if ($customerId) {
                    $this->updateCustomerBalance($customerId);
                }
                
                // ✅ STEP 7: Create accounting entries for the invoice
                $this->recordInvoiceInAccounting($invoiceId, $bookingId, $customerId, $booking);
                
                error_log("createBookingInvoice: Created invoice #$invoiceId for booking #$bookingId");
                
                return $invoiceId;
            }
            
            return null;
        } catch (Exception $e) {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            error_log("createBookingInvoice: Error - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Record invoice creation in accounting transactions
     * Creates: DR Accounts Receivable, CR Revenue, CR VAT (if applicable)
     */
    private function recordInvoiceInAccounting($invoiceId, $bookingId, $customerId, $booking) {
        try {
            if (!$this->accountModel || !$this->transactionModel) {
                error_log("recordInvoiceInAccounting: Account or Transaction model not loaded");
                return;
            }
            
            $subtotal = floatval($booking['subtotal'] ?? $booking['total_amount'] ?? 0);
            $taxAmount = floatval($booking['tax_amount'] ?? 0);
            $totalAmount = floatval($booking['total_amount'] ?? ($subtotal + $taxAmount));
            $discountAmount = floatval($booking['discount_amount'] ?? 0);
            $netRevenue = $subtotal - $discountAmount;
            
            // Get customer-specific AR account or parent AR account
            // FIX: Use 1200 prefix to match createCustomerLedgerAccount for consistency
            $arAccount = null;
            if ($customerId) {
                // Try 1200 first (Correct one)
                $arAccount = $this->accountModel->getByCode('1200-' . str_pad($customerId, 4, '0', STR_PAD_LEFT));
                
                // Fallback to 1100 if 1200 not found (Legacy support)
                if (!$arAccount) {
                    $arAccount = $this->accountModel->getByCode('1100-' . str_pad($customerId, 4, '0', STR_PAD_LEFT));
                }
            }
            
            // Fallback to parents
            if (!$arAccount) {
                $arAccount = $this->accountModel->getByCode('1200');
            }
            if (!$arAccount) {
                $arAccount = $this->accountModel->getByCode('1100');
            }
            if (!$arAccount) {
                $assetAccounts = $this->accountModel->getByType('Assets');
                if (is_array($assetAccounts)) {
                    foreach ($assetAccounts as $acc) {
                        if (stripos($acc['account_name'] ?? '', 'receivable') !== false) {
                            $arAccount = $acc;
                            break;
                        }
                    }
                }
            }
            
            // Get Revenue account
            $revenueAccount = $this->accountModel->getByCode('4100');
            if (!$revenueAccount) {
                $revenueAccount = $this->accountModel->getByCode('4000');
            }
            if (!$revenueAccount) {
                $revenueAccounts = $this->accountModel->getByType('Revenue');
                $revenueAccount = is_array($revenueAccounts) && !empty($revenueAccounts) ? $revenueAccounts[0] : null;
            }
            
            if (!$arAccount || !$revenueAccount) {
                error_log("recordInvoiceInAccounting: Missing AR or Revenue accounts");
                return;
            }
            
            $bookingRef = $booking['booking_number'] ?? 'Booking #' . $bookingId;
            
            $transactionCreatedBy = ($booking['booking_source'] ?? '') === 'online' ? null : ($booking['created_by'] ?? null);
            
            // ✅ Debit Accounts Receivable (customer owes us the TOTAL)
            $this->transactionModel->create([
                'transaction_number' => 'INV-' . date('Ymd') . '-' . str_pad($invoiceId, 6, '0', STR_PAD_LEFT) . '-AR',
                'account_id' => $arAccount['id'],
                'transaction_date' => date('Y-m-d'),
                'debit' => $totalAmount,
                'credit' => 0,
                'description' => 'Invoice - ' . $bookingRef,
                'reference_type' => 'invoice',
                'reference_id' => $invoiceId,
                'status' => 'posted',
                'created_by' => $transactionCreatedBy,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // ✅ Credit Revenue (net amount: subtotal - discount)
            $this->transactionModel->create([
                'transaction_number' => 'INV-' . date('Ymd') . '-' . str_pad($invoiceId, 6, '0', STR_PAD_LEFT) . '-REV',
                'account_id' => $revenueAccount['id'],
                'transaction_date' => date('Y-m-d'),
                'debit' => 0,
                'credit' => $netRevenue,
                'description' => 'Booking Revenue - ' . $bookingRef,
                'reference_type' => 'invoice',
                'reference_id' => $invoiceId,
                'status' => 'posted',
                'created_by' => $transactionCreatedBy,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // ✅ Credit VAT Liability if there is tax
            if ($taxAmount > 0) {
                $vatAccount = $this->accountModel->getByCode('2100');
                if (!$vatAccount) {
                    $liabilityAccounts = $this->accountModel->getByType('Liabilities');
                    if (is_array($liabilityAccounts)) {
                        foreach ($liabilityAccounts as $acc) {
                            if (stripos($acc['account_name'] ?? '', 'vat') !== false || 
                                stripos($acc['account_name'] ?? '', 'tax') !== false) {
                                $vatAccount = $acc;
                                break;
                            }
                        }
                    }
                }
                
                if ($vatAccount) {
                    $this->transactionModel->create([
                        'transaction_number' => 'INV-' . date('Ymd') . '-' . str_pad($invoiceId, 6, '0', STR_PAD_LEFT) . '-VAT',
                        'account_id' => $vatAccount['id'],
                        'transaction_date' => date('Y-m-d'),
                        'debit' => 0,
                        'credit' => $taxAmount,
                        'description' => 'VAT Liability - ' . $bookingRef,
                        'reference_type' => 'invoice',
                        'reference_id' => $invoiceId,
                        'status' => 'posted',
                        'created_by' => $transactionCreatedBy,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            error_log("recordInvoiceInAccounting: Created entries - AR DR: $totalAmount, Revenue CR: $netRevenue, VAT CR: $taxAmount");
            
        } catch (Exception $e) {
            error_log("recordInvoiceInAccounting error: " . $e->getMessage());
        }
    }
    
    /**
     * Record payment in accounting module
     * Creates: DR Cash, CR Accounts Receivable
     */
    public function recordPaymentInAccounting($bookingId, $booking, $amount, $reference) {
        $logFile = 'logs/payment_accounting.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Recording payment for booking $bookingId, amount: $amount\n", FILE_APPEND);
        
        if (!$this->accountModel || !$this->transactionModel) {
            @file_put_contents($logFile, "Accounting models not loaded\n", FILE_APPEND);
            return;
        }
        
        try {
            // Get customer ID from invoice
            $invoice = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . "invoices` WHERE reference = ?",
                ['BKG-' . $bookingId]
            );
            
            $customerId = $invoice ? $invoice['customer_id'] : null;
            
            // Get Cash account (Paystack/Online)
            $cashAccount = $this->accountModel->getByCode('1010');
            if (!$cashAccount) {
                $cashAccount = $this->accountModel->getByCode('1001');
            }
            if (!$cashAccount) {
                $cashAccount = $this->accountModel->getByCode('1000');
            }
            
            // Get customer's AR account or parent AR account
            $arAccount = null;
            if ($customerId) {
                $arAccount = $this->accountModel->getByCode('1100-' . str_pad($customerId, 4, '0', STR_PAD_LEFT));
            }
            if (!$arAccount) {
                $arAccount = $this->accountModel->getByCode('1100');
            }
            if (!$arAccount) {
                $arAccount = $this->accountModel->getByCode('1200');
            }
            
            if (!$cashAccount || !$arAccount) {
                @file_put_contents($logFile, "Missing cash or AR accounts\n", FILE_APPEND);
                error_log("recordPaymentInAccounting: Missing cash or AR accounts");
                return;
            }
            
            $bookingRef = $booking['booking_number'] ?? 'Booking #' . $bookingId;
            $transactionCreatedBy = ($booking['booking_source'] ?? '') === 'online' ? null : ($booking['created_by'] ?? null);
            
            // ✅ Debit Cash (money received)
            $this->transactionModel->create([
                'transaction_number' => 'PAY-' . date('Ymd') . '-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT) . '-CASH',
                'account_id' => $cashAccount['id'],
                'transaction_date' => date('Y-m-d'),
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Payment received - ' . $bookingRef . ' (' . $reference . ')',
                'reference_type' => 'booking_payment',
                'reference_id' => $bookingId,
                'status' => 'posted',
                'created_by' => $transactionCreatedBy,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // ✅ Credit Accounts Receivable (debt cleared)
            $this->transactionModel->create([
                'transaction_number' => 'PAY-' . date('Ymd') . '-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT) . '-AR',
                'account_id' => $arAccount['id'],
                'transaction_date' => date('Y-m-d'),
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Payment received - ' . $bookingRef,
                'reference_type' => 'booking_payment',
                'reference_id' => $bookingId,
                'status' => 'posted',
                'created_by' => $transactionCreatedBy,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // ✅ Update invoice status to paid
            if ($invoice) {
                $newPaidAmount = floatval($invoice['paid_amount']) + $amount;
                $newBalance = floatval($invoice['total_amount']) - $newPaidAmount;
                $newStatus = $newBalance <= 0 ? 'paid' : ($newPaidAmount > 0 ? 'partially_paid' : $invoice['status']);
                
                $this->invoiceModel->update($invoice['id'], [
                    'paid_amount' => $newPaidAmount,
                    'balance_amount' => max(0, $newBalance),
                    'status' => $newStatus,
                    'payment_date' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // ✅ Update cash account balance
            if ($this->cashAccountModel) {
                try {
                    $cashAccountRecord = $this->db->fetchOne(
                        "SELECT * FROM `" . $this->db->getPrefix() . "cash_accounts` 
                         WHERE account_name LIKE '%Paystack%' OR account_name LIKE '%Online%' OR account_code = '1010'
                         LIMIT 1"
                    );
                    
                    if ($cashAccountRecord) {
                        $newBalance = floatval($cashAccountRecord['current_balance'] ?? 0) + $amount;
                        $this->cashAccountModel->update($cashAccountRecord['id'], [
                            'current_balance' => $newBalance,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                } catch (Exception $e) {
                    // Cash account update is optional
                }
            }
            
            // ✅ Update customer balance
            if ($customerId) {
                $this->updateCustomerBalance($customerId);
            }
            
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Payment recording complete: Cash DR $amount, AR CR $amount\n", FILE_APPEND);
            error_log("recordPaymentInAccounting: Recorded payment - Cash DR: $amount, AR CR: $amount");
            
        } catch (Exception $e) {
            @file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            error_log("recordPaymentInAccounting error: " . $e->getMessage());
        }
    }

    /**
     * TEMPORARY: Fix database schema
     */
    public function fix_db() {
        echo "<h1>Database Fix Tool</h1>";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border: 1px solid #ccc;'>";
        
        try {
            $table = $this->db->getPrefix() . 'bookings';
            
            // Get existing columns
            $stmt = $this->db->query("SHOW COLUMNS FROM " . $table);
            $columns = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }
            
            echo "<strong>Checking table: $table</strong><br><br>";
            
            // Define required columns and their definitions
            $requiredColumns = [
                'space_id' => "INT NULL AFTER booking_number",
                'facility_id' => "INT NULL AFTER space_id",
                'tax_rate' => "DECIMAL(5,2) DEFAULT 0 AFTER subtotal",
                'tax_amount' => "DECIMAL(15,2) DEFAULT 0 AFTER tax_rate",
                'discount_amount' => "DECIMAL(15,2) DEFAULT 0 AFTER tax_amount",
                'security_deposit' => "DECIMAL(15,2) DEFAULT 0 AFTER discount_amount",
                'promo_code' => "VARCHAR(50) NULL AFTER payment_plan",
                'booking_source' => "VARCHAR(50) DEFAULT 'online' AFTER special_requests",
                'is_recurring' => "TINYINT(1) DEFAULT 0 AFTER booking_source",
                'recurring_pattern' => "VARCHAR(50) NULL AFTER is_recurring",
                'recurring_end_date' => "DATE NULL AFTER recurring_pattern",
                'customer_address' => "TEXT NULL AFTER customer_phone",
                'duration_hours' => "DECIMAL(5,2) DEFAULT 0 AFTER end_time",
                'number_of_guests' => "INT DEFAULT 1 AFTER duration_hours"
            ];
            
            $changesMade = false;
            
            foreach ($requiredColumns as $col => $def) {
                if (!in_array($col, $columns)) {
                    echo "Adding column '<strong>$col</strong>'...";
                    $sql = "ALTER TABLE $table ADD COLUMN $col $def";
                    try {
                        $this->db->query($sql);
                        echo " <span style='color:green'>Done</span><br>";
                        $changesMade = true;
                    } catch (Exception $e) {
                         echo " <span style='color:red'>Failed: " . $e->getMessage() . "</span><br>";
                    }
                } else {
                    echo "Column '<strong>$col</strong>' exists.<br>";
                }
            }
            
            if ($changesMade) {
                echo "<br><h3 style='color:green'>Database updated successfully!</h3>";
            } else {
                echo "<br><h3 style='color:blue'>Database is already up to date.</h3>";
            }
            
        } catch (Exception $e) {
            echo "<br><strong style='color:red'>Fatal Error: " . $e->getMessage() . "</strong>";
        }
        
        echo "</div>";
        echo "<p style='margin-top: 20px;'><a href='" . base_url('booking-wizard') . "' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Return to Booking Wizard</a></p>";
    }
}

