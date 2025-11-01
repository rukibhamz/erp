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
     * Step 1: Select Resource
     */
    public function step1() {
        $resourceType = $_GET['type'] ?? 'all';
        $category = $_GET['category'] ?? 'all';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        try {
            if ($resourceType !== 'all') {
                $resources = $this->facilityModel->getByType($resourceType);
            } elseif ($category !== 'all') {
                $resources = $this->facilityModel->getByCategory($category);
            } else {
                $resources = $this->facilityModel->getActive();
            }
            
            // Get photos for each resource
            foreach ($resources as &$resource) {
                try {
                    $resource['photos'] = $this->facilityModel->getPhotos($resource['id']);
                } catch (Exception $e) {
                    $resource['photos'] = [];
                }
            }
            unset($resource); // Break reference
            
            // Get categories and types for filters
            $allResources = $this->facilityModel->getAll();
            $categories = array_unique(array_filter(array_column($allResources, 'category')));
            $types = ['hall', 'meeting_room', 'equipment', 'vehicle', 'staff', 'other'];
        } catch (Exception $e) {
            $resources = [];
            $categories = [];
            $types = [];
        }

        $data = [
            'page_title' => 'Select Resource',
            'resources' => $resources,
            'categories' => $categories,
            'types' => $types,
            'selected_type' => $resourceType,
            'selected_category' => $category,
            'selected_date' => $date,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/step1_select_resource', $data);
    }

    /**
     * Step 2: Choose Date and Time
     */
    public function step2($resourceId) {
        try {
            $resource = $this->facilityModel->getById($resourceId);
            if (!$resource || $resource['status'] !== 'available') {
                $this->setFlashMessage('danger', 'Resource not available.');
                redirect('booking-wizard/step1');
            }
            
            $photos = $this->facilityModel->getPhotos($resourceId);
            $amenities = $this->facilityModel->getAmenities($resourceId);
        } catch (Exception $e) {
            $resource = null;
            $photos = [];
            $amenities = [];
        }

        if (!$resource) {
            redirect('booking-wizard/step1');
        }

        $data = [
            'page_title' => 'Select Date & Time',
            'resource' => $resource,
            'photos' => $photos,
            'amenities' => $amenities,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('booking_wizard/step2_select_datetime', $data);
    }

    /**
     * Get available time slots for a date
     */
    public function getTimeSlots() {
        header('Content-Type: application/json');
        
        $resourceId = intval($_GET['resource_id'] ?? 0);
        $date = sanitize_input($_GET['date'] ?? '');
        
        if (!$resourceId || !$date) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        try {
            $resource = $this->facilityModel->getById($resourceId);
            if (!$resource) {
                echo json_encode(['success' => false, 'message' => 'Resource not found']);
                exit;
            }
            
            $slotDuration = intval($resource['slot_duration'] ?? 60); // minutes
            $minDuration = intval($resource['minimum_duration'] ?? 1); // hours
            $maxDuration = intval($resource['max_duration'] ?? 24); // hours
            
            // Get day-of-week availability
            $dayOfWeek = date('w', strtotime($date));
            $dayAvailability = $this->loadModel('Resource_availability_model')->getByResource($resourceId);
            $availableDay = null;
            foreach ($dayAvailability as $avail) {
                if ($avail['day_of_week'] == $dayOfWeek) {
                    $availableDay = $avail;
                    break;
                }
            }
            
            // Default availability: 8 AM to 10 PM
            $startHour = 8;
            $endHour = 22;
            
            if ($availableDay && $availableDay['is_available']) {
                if ($availableDay['start_time']) {
                    $startHour = intval(substr($availableDay['start_time'], 0, 2));
                }
                if ($availableDay['end_time']) {
                    $endHour = intval(substr($availableDay['end_time'], 0, 2));
                }
            } elseif ($availableDay && !$availableDay['is_available']) {
                echo json_encode(['success' => true, 'slots' => []]);
                exit;
            }
            
            // Check blockouts
            $blockouts = $this->loadModel('Resource_blockout_model')->getByResource($resourceId, $date, $date);
            
            // Get existing bookings for this date
            $bookings = $this->bookingModel->getByDateRange($date, $date, $resourceId);
            $bookedSlots = [];
            foreach ($bookings as $booking) {
                if (!in_array($booking['status'], ['cancelled', 'no_show'])) {
                    $bookedSlots[] = [
                        'start' => $booking['start_time'],
                        'end' => $booking['end_time']
                    ];
                }
            }
            
            // Generate available slots
            $availableSlots = [];
            $currentHour = $startHour;
            
            while ($currentHour < $endHour) {
                $slotStart = str_pad($currentHour, 2, '0', STR_PAD_LEFT) . ':00';
                $slotEndMinutes = ($currentHour * 60) + ($slotDuration * $minDuration);
                $slotEndHour = floor($slotEndMinutes / 60);
                $slotEndMin = $slotEndMinutes % 60;
                
                if ($slotEndHour >= $endHour) {
                    break;
                }
                
                $slotEnd = str_pad($slotEndHour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($slotEndMin, 2, '0', STR_PAD_LEFT);
                
                // Check if slot conflicts with bookings
                $isAvailable = true;
                foreach ($bookedSlots as $booked) {
                    if (!($slotEnd <= $booked['start'] || $slotStart >= $booked['end'])) {
                        $isAvailable = false;
                        break;
                    }
                }
                
                // Check blockouts
                foreach ($blockouts as $blockout) {
                    if ($blockout['start_date'] <= $date && $blockout['end_date'] >= $date) {
                        if ((!$blockout['start_time'] || $slotEnd <= $blockout['start_time']) ||
                            (!$blockout['end_time'] || $slotStart >= $blockout['end_time'])) {
                            // Not blocked
                        } else {
                            $isAvailable = false;
                            break;
                        }
                    }
                }
                
                if ($isAvailable) {
                    $availableSlots[] = [
                        'start' => $slotStart,
                        'end' => $slotEnd,
                        'duration' => $slotDuration * $minDuration,
                        'display' => date('g:i A', strtotime($slotStart)) . ' - ' . date('g:i A', strtotime($slotEnd))
                    ];
                }
                
                $currentHour += ($slotDuration / 60);
            }
            
            echo json_encode([
                'success' => true,
                'slots' => $availableSlots,
                'min_duration' => $minDuration,
                'max_duration' => $maxDuration
            ]);
        } catch (Exception $e) {
            error_log('Booking_wizard getTimeSlots error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading time slots']);
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
        $data = $_POST['data'] ?? [];
        
        if (!isset($_SESSION['booking_data'])) {
            $_SESSION['booking_data'] = [];
        }
        
        // Merge step data
        $_SESSION['booking_data'] = array_merge($_SESSION['booking_data'], $data);
        
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
            
            // Create booking
            $bookingNumber = $this->bookingModel->getNextBookingNumber();
            $paymentPlan = sanitize_input($_POST['payment_plan'] ?? 'full');
            
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
                'duration_hours' => $this->calculateDuration($bookingData['date'], $bookingData['start_time'], $bookingData['end_time']),
                'number_of_guests' => intval($bookingData['guests'] ?? 0),
                'booking_type' => $bookingData['booking_type'] ?? 'hourly',
                'base_amount' => $bookingData['base_amount'] ?? 0,
                'subtotal' => $bookingData['subtotal'] ?? 0,
                'discount_amount' => $bookingData['discount_amount'] ?? 0,
                'security_deposit' => $bookingData['security_deposit'] ?? 0,
                'total_amount' => $bookingData['total_amount'] ?? 0,
                'paid_amount' => 0,
                'balance_amount' => $bookingData['total_amount'] ?? 0,
                'currency' => 'NGN',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_plan' => $paymentPlan,
                'promo_code' => $bookingData['promo_code'] ?? null,
                'booking_notes' => sanitize_input($bookingData['notes'] ?? ''),
                'special_requests' => sanitize_input($bookingData['special_requests'] ?? ''),
                'booking_source' => 'online',
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
                $bookingData['total_amount'] ?? 0,
                !empty($_POST['deposit_percentage']) ? floatval($_POST['deposit_percentage']) : null
            );
            
            // Create booking slots
            $this->bookingModel->createSlots(
                $bookingId,
                $bookingData['resource_id'],
                $bookingData['date'],
                $bookingData['start_time'],
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

