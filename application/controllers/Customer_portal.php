<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_portal extends Base_Controller {
    private $customerPortalUserModel;
    private $bookingModel;
    private $facilityModel;
    private $bookingPaymentModel;
    private $spaceModel;
    private $bookingAddonModel;
    private $bookingRentalModel;
    private $gatewayModel;
    private $paymentTransactionModel;
    
    public function __construct() {
        parent::__construct();
        $this->customerPortalUserModel = $this->loadModel('Customer_portal_user_model');
        $this->bookingModel = $this->loadModel('Booking_model');
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->bookingPaymentModel = $this->loadModel('Booking_payment_model');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->bookingAddonModel = $this->loadModel('Booking_addon_model');
        $this->bookingRentalModel = $this->loadModel('Booking_rental_model');
        $this->gatewayModel = $this->loadModel('Payment_gateway_model');
        $this->paymentTransactionModel = $this->loadModel('Payment_transaction_model');
        $this->loadLibrary('Email_sender');
        require_once BASEPATH . 'helpers/payment_initiation_helper.php';
    }
    
    protected function checkAuth() {
        // Public controller for registration/login
        return true;
    }
    
    public function index() {
        if (isset($this->session['customer_user_id'])) {
            redirect('customer-portal/dashboard');
        } else {
            redirect('customer-portal/login');
        }
    }
    
    protected function loadView($view, $data = []) {
        $data['config'] = $this->config;
        $data['session'] = $this->session;
        
        // Check if user is logged in to customer portal
        $isCustomerLoggedIn = isset($this->session['customer_user_id']);
        
        if ($isCustomerLoggedIn) {
            // Use customer portal header/footer with dashboard navigation
            $this->loader->view('layouts/header_customer', $data);
            $this->loader->view($view, $data);
            $this->loader->view('layouts/footer_customer', $data);
        } else {
            // Use public header/footer
            $this->loader->view('layouts/header_public', $data);
            $this->loader->view($view, $data);
            $this->loader->view('layouts/footer_public', $data);
        }
    }
    
    /**
     * Registration page
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // Validate CSRF token
            
            $data = [
                'email' => sanitize_input($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'first_name' => sanitize_input($_POST['first_name'] ?? ''),
                'last_name' => sanitize_input($_POST['last_name'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'company_name' => sanitize_input($_POST['company_name'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'zip_code' => sanitize_input($_POST['zip_code'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? '')
            ];
            
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                $this->setFlashMessage('danger', 'Email and password are required.');
                redirect('customer-portal/register');
            }
            
            // Validate email format (requires dotted domain, e.g. .com — rejects gmailcom typos)
            if (!validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address. Use a full address like name@example.com and include your domain extension (for example .com).');
                redirect('customer-portal/register');
            }
            
            // Validate phone if provided
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
                redirect('customer-portal/register');
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Validate names if provided
            if (!empty($data['first_name']) && !validate_name($data['first_name'])) {
                $this->setFlashMessage('danger', 'Invalid first name.');
                redirect('customer-portal/register');
            }
            
            if (!empty($data['last_name']) && !validate_name($data['last_name'])) {
                $this->setFlashMessage('danger', 'Invalid last name.');
                redirect('customer-portal/register');
            }
            
            // Validate password strength
            $passwordValidation = validate_password($data['password']);
            if (!$passwordValidation['valid']) {
                $this->setFlashMessage('danger', implode(' ', $passwordValidation['errors']));
                redirect('customer-portal/register');
            }
            
            $result = $this->customerPortalUserModel->register($data);
            
            if ($result['success']) {
                // Send verification email
                $verificationLink = site_url('customer-portal/verify/' . $result['verification_token']);
                
                // Load email template
                $emailData = [
                    'name' => $data['first_name'],
                    'verification_link' => $verificationLink,
                    'company_name' => $this->config['company_name'] ?? 'ERP System'
                ];
                
                // Get email content
                // Note: Since we can't easily capture view output to string in this framework without a helper,
                // we'll use a simple approach or assume a helper exists. 
                // For now, let's use output buffering to capture the view.
                ob_start();
                $this->loader->view('emails/user_registration', $emailData);
                $emailBody = ob_get_clean();
                
                // Send email
                $emailSender = new Email_sender();
                $emailSender->sendEmail(
                    $data['email'],
                    'Verify Your Account',
                    $emailBody
                );
                
                $this->setFlashMessage('success', 'Registration successful! Please check your email to verify your account.');
                redirect('customer-portal/login');
            } else {
                $this->setFlashMessage('danger', $result['message']);
                redirect('customer-portal/register');
            }
        }
        
        $data = [
            'page_title' => 'Customer Registration',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/register', $data);
    }
    
    /**
     * Verify email address
     */
    public function verify($token) {
        if (empty($token)) {
            $this->setFlashMessage('danger', 'Invalid verification token.');
            redirect('customer-portal/login');
        }
        
        if ($this->customerPortalUserModel->verifyEmail($token)) {
            $this->setFlashMessage('success', 'Email verified successfully! You can now login.');
        } else {
            $this->setFlashMessage('danger', 'Invalid or expired verification token.');
        }
        
        redirect('customer-portal/login');
    }
    
    /**
     * Login page
     */
    public function login() {
        if (isset($this->session['customer_user_id'])) {
            redirect('customer-portal/dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // Validate CSRF token
            
            $email = sanitize_input($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rememberMe = !empty($_POST['remember_me']);
            
            $result = $this->customerPortalUserModel->authenticate($email, $password, $rememberMe);
            
            if ($result['success']) {
                // Clear any existing staff session to prevent concurrent logins
                unset($this->session['user_id']);
                unset($this->session['username']);
                unset($this->session['email']);
                unset($this->session['role']);
                unset($this->session['first_name']);
                unset($this->session['last_name']);
                unset($this->session['last_activity']);
                
                // Set session
                $this->session['customer_user_id'] = $result['user']['id'];
                $this->session['customer_email'] = $result['user']['email'];
                $this->session['customer_name'] = trim(($result['user']['first_name'] ?? '') . ' ' . ($result['user']['last_name'] ?? ''));
                
                // Link existing bookings to account
                $this->customerPortalUserModel->linkBookingsByEmail($email);
                
                // Set remember me cookie
                if ($rememberMe && isset($result['user']['remember_token'])) {
                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                    setcookie('customer_remember_token', $result['user']['remember_token'], time() + (86400 * 30), '/', '', $isHttps, true); // 30 days, httponly, secure
                }
                
                $this->setFlashMessage('success', 'Welcome back!');
                
                // Redirect to previous page or dashboard
                $redirectUrl = $this->session['customer_redirect_url'] ?? 'customer-portal/dashboard';
                unset($this->session['customer_redirect_url']);
                redirect($redirectUrl);
            } else {
                $this->setFlashMessage('danger', $result['message']);
                redirect('customer-portal/login');
            }
        }
        
        $data = [
            'page_title' => 'Customer Login',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/login', $data);
    }
    
    /**
     * Logout
     */
    public function logout() {
        unset($this->session['customer_user_id']);
        unset($this->session['customer_email']);
        unset($this->session['customer_name']);
        setcookie('customer_remember_token', '', time() - 3600, '/');
        
        $this->setFlashMessage('success', 'Logged out successfully.');
        redirect('customer-portal/login');
    }
    
    /**
     * Customer Dashboard
     */
    public function dashboard() {
        $this->requireCustomerAuth();
        
        $userId = $this->session['customer_user_id'];
        
        try {
            // Get bookings
            $allBookings = $this->customerPortalUserModel->getBookings($userId);
            $upcomingBookings = array_filter($allBookings, function($b) {
                return strtotime($b['booking_date'] . ' ' . $b['start_time']) >= time() && 
                       !in_array($b['status'], ['cancelled', 'completed']);
            });
            $pastBookings = array_filter($allBookings, function($b) {
                return strtotime($b['booking_date'] . ' ' . $b['start_time']) < time() || 
                       in_array($b['status'], ['completed', 'cancelled']);
            });
            
            // Calculate stats
            $totalBookings = count($allBookings);
            $pendingBookings = count(array_filter($allBookings, function($b) {
                return $b['status'] === 'pending';
            }));
            $totalSpent = array_sum(array_column($allBookings, 'paid_amount'));
            $outstandingBalance = array_sum(array_column($allBookings, 'balance_amount'));
        } catch (Exception $e) {
            $allBookings = [];
            $upcomingBookings = [];
            $pastBookings = [];
            $totalBookings = 0;
            $pendingBookings = 0;
            $totalSpent = 0;
            $outstandingBalance = 0;
        }
        
        $data = [
            'page_title' => 'My Dashboard',
            'bookings' => $allBookings,
            'upcoming_bookings' => $upcomingBookings,
            'past_bookings' => $pastBookings,
            'stats' => [
                'total_bookings' => $totalBookings,
                'pending_bookings' => $pendingBookings,
                'total_spent' => $totalSpent,
                'outstanding_balance' => $outstandingBalance
            ],
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/dashboard', $data);
    }
    
    /**
     * My Bookings
     */
    public function bookings($status = null) {
        $this->requireCustomerAuth();
        
        $userId = $this->session['customer_user_id'];
        
        try {
            $bookings = $this->customerPortalUserModel->getBookings($userId, $status);
        } catch (Exception $e) {
            $bookings = [];
        }
        
        $data = [
            'page_title' => 'My Bookings',
            'bookings' => $bookings,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/bookings', $data);
    }
    
    /**
     * View Booking Details (Public - No login required)
     * This is used from the booking confirmation page
     */
    public function booking($id) {
        $this->requireCustomerAuth();
        try {
            if (empty($id)) {
                $this->setFlashMessage('danger', 'Booking ID is required.');
                redirect('booking-wizard');
                return;
            }
            
            $booking = $this->bookingModel->getWithFacility($id);
            
            if (!$booking) {
                $this->setFlashMessage('danger', 'Booking not found. Please check your booking number.');
                redirect('booking-wizard');
                return;
            }
            
            // Get payments and addons
            $payments = [];
            $addons = [];
            try {
                $payments = $this->bookingPaymentModel->getByBooking($id);
            } catch (Exception $e) {}
            
            try {
                $addons = $this->bookingModel->getAddons($id);
            } catch (Exception $e) {}
            
        } catch (Exception $e) {
            error_log("Customer portal booking view error: " . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading booking.');
            redirect('booking-wizard');
            return;
        }
        
        $data = [
            'page_title' => 'Booking Details - ' . ($booking['booking_number'] ?? ''),
            'booking' => $booking,
            'payments' => $payments,
            'addons' => $addons,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/view_booking', $data);
    }

    /**
     * Pay outstanding balance (gateway selection + default fallback).
     */
    public function payBooking($id) {
        $this->requireCustomerAuth();

        $bookingId = (int) $id;
        $booking = $this->getCustomerBookingOrRedirect($bookingId);
        if (!$booking) {
            return;
        }

        $amountDue = floatval($booking['balance_amount'] ?? 0);
        if ($amountDue <= 0) {
            $this->setFlashMessage('info', 'This booking has no outstanding balance.');
            redirect('customer-portal/booking/' . $bookingId);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();

            $requestedCode = sanitize_input($_POST['gateway_code'] ?? '');
            $result = initiate_booking_gateway_payment(
                $bookingId,
                $requestedCode,
                $this->bookingModel,
                $this->gatewayModel,
                $this->paymentTransactionModel
            );

            if (!empty($result['success']) && !empty($result['authorization_url'])) {
                redirect($result['authorization_url']);
                return;
            }

            $message = $result['message'] ?? 'Could not start payment.';
            if (!empty($result['already_paid'])) {
                $this->setFlashMessage('info', $message);
                redirect('customer-portal/booking/' . $bookingId);
                return;
            }

            $this->setFlashMessage('danger', $message);
            redirect('customer-portal/pay-booking/' . $bookingId);
            return;
        }

        $data = [
            'page_title' => 'Pay Booking',
            'booking' => $booking,
            'amount_due' => $amountDue,
            'gateways' => get_usable_payment_gateways($this->gatewayModel),
            'flash' => $this->getFlashMessage(),
        ];

        $this->loadView('customer_portal/pay_booking', $data);
    }

    /**
     * Verify booking belongs to logged-in customer; redirect if not.
     */
    private function getCustomerBookingOrRedirect($bookingId) {
        $userId = $this->session['customer_user_id'];
        $user = $this->customerPortalUserModel->getById($userId);

        try {
            $booking = $this->bookingModel->getWithFacility($bookingId);
            if (!$booking || ($booking['customer_email'] ?? '') !== ($user['email'] ?? '')) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('customer-portal/bookings');
                return null;
            }
            if (in_array($booking['status'] ?? '', ['cancelled', 'refunded'], true)) {
                $this->setFlashMessage('danger', 'Cannot pay for a cancelled booking.');
                redirect('customer-portal/booking/' . $bookingId);
                return null;
            }
            return $booking;
        } catch (Exception $e) {
            error_log('Customer_portal getCustomerBookingOrRedirect: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading booking.');
            redirect('customer-portal/bookings');
            return null;
        }
    }
    
    /**
     * Customer reschedule booking
     */
    public function rescheduleBooking($id) {
        $this->requireCustomerAuth();

        $userId = $this->session['customer_user_id'];
        $user   = $this->customerPortalUserModel->getById($userId);

        try {
            $booking = $this->bookingModel->getWithFacility($id);
            if (!$booking || $booking['customer_email'] !== $user['email']) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('customer-portal/bookings');
                return;
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading booking.');
            redirect('customer-portal/bookings');
            return;
        }

        // Only allow reschedule on confirmed/pending bookings that haven't passed
        $allowedStatuses = ['confirmed', 'pending'];
        if (!in_array($booking['status'], $allowedStatuses)) {
            $this->setFlashMessage('danger', 'This booking cannot be rescheduled.');
            redirect('customer-portal/booking/' . $id);
            return;
        }
        if ($booking['booking_date'] < date('Y-m-d')) {
            $this->setFlashMessage('danger', 'Past bookings cannot be rescheduled.');
            redirect('customer-portal/booking/' . $id);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $newDate      = sanitize_input($_POST['booking_date'] ?? '');
            $newStartTime = sanitize_input($_POST['start_time'] ?? '');
            $newEndTime   = sanitize_input($_POST['end_time'] ?? '');
            $selectedSpaceId = intval($_POST['space_id'] ?? 0);
            $reason       = sanitize_input($_POST['reason'] ?? '');

            if (!$newDate || !$newStartTime || !$newEndTime) {
                $this->setFlashMessage('danger', 'Please select a date and time slot.');
                redirect('customer-portal/reschedule-booking/' . $id);
                return;
            }

            try {
                $selectedSpace = $this->resolveBookingSpace($selectedSpaceId, $booking);
                if (!$selectedSpace) {
                    $this->setFlashMessage('danger', 'Please select a valid venue.');
                    redirect('customer-portal/reschedule-booking/' . $id);
                    return;
                }
                $resourceId = intval($selectedSpace['facility_id']);
                if (!$this->facilityModel->checkAdvancedAvailability($resourceId, $newDate . ' ' . $newStartTime, $newDate . ' ' . $newEndTime, $id)) {
                    $this->setFlashMessage('danger', 'The selected time slot is no longer available. Please choose another.');
                    redirect('customer-portal/reschedule-booking/' . $id);
                    return;
                }

                $duration = abs(strtotime($newEndTime) - strtotime($newStartTime)) / 3600;
                $pricing = $this->recalculateBookingPricing(
                    $id,
                    $booking,
                    $resourceId,
                    $newDate,
                    $newStartTime,
                    $newEndTime
                );
                $this->bookingModel->update($id, [
                    'booking_date'   => $newDate,
                    'start_time'     => $newStartTime,
                    'end_time'       => $newEndTime,
                    'duration_hours' => $duration,
                    'space_id'       => intval($selectedSpace['space_id']),
                    'facility_id'    => $resourceId,
                    'location_id'    => intval($selectedSpace['property_id'] ?? 0),
                    'base_amount'    => $pricing['base_amount'],
                    'subtotal'       => $pricing['subtotal'],
                    'tax_amount'     => $pricing['tax_amount'],
                    'total_amount'   => $pricing['total_amount'],
                    'balance_amount' => $pricing['balance_amount']
                ]);
                $this->bookingModel->createSlots($id, $resourceId, $newDate, $newStartTime, $newEndTime);

                $this->setFlashMessage('success', 'Your booking has been rescheduled successfully.');
                redirect('customer-portal/booking/' . $id);
            } catch (Exception $e) {
                error_log('Customer portal reschedule error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to reschedule. Please try again.');
                redirect('customer-portal/reschedule-booking/' . $id);
            }
            return;
        }

        $data = [
            'page_title' => 'Reschedule Booking',
            'booking'    => $booking,
            'venue_options' => $this->getVenueOptionsForBooking($booking),
            'flash'      => $this->getFlashMessage()
        ];
        $this->loadView('customer_portal/reschedule_booking', $data);
    }

    public function getRescheduleQuote($id) {
        $this->requireCustomerAuth();
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json');

        try {
            $userId = $this->session['customer_user_id'];
            $user = $this->customerPortalUserModel->getById($userId);
            $booking = $this->bookingModel->getById(intval($id));
            if (!$booking || ($booking['customer_email'] ?? '') !== ($user['email'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Booking not found.']);
                exit;
            }

            $spaceId = intval($_GET['space_id'] ?? ($booking['space_id'] ?? 0));
            $date = sanitize_input($_GET['booking_date'] ?? ($booking['booking_date'] ?? date('Y-m-d')));
            $start = sanitize_input($_GET['start_time'] ?? ($booking['start_time'] ?? '00:00:00'));
            $end = sanitize_input($_GET['end_time'] ?? ($booking['end_time'] ?? '00:00:00'));

            $space = $this->resolveBookingSpace($spaceId, $booking);
            if (!$space) {
                echo json_encode(['success' => false, 'message' => 'Invalid venue selected.']);
                exit;
            }

            $pricing = $this->recalculateBookingPricing($id, $booking, intval($space['facility_id']), $date, $start, $end);
            echo json_encode(['success' => true, 'quote' => $pricing]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function getVenueOptionsForBooking($booking) {
        try {
            $propertyId = 0;
            if (!empty($booking['space_id'])) {
                $space = $this->spaceModel->getWithProperty(intval($booking['space_id']));
                $propertyId = intval($space['property_id'] ?? 0);
            }

            $spaces = $this->spaceModel->getBookableSpaces($propertyId ?: null);
            $options = [];
            foreach ($spaces as $space) {
                $facilityId = intval($space['facility_id'] ?? 0);
                if ($facilityId <= 0 && !empty($space['id'])) {
                    $facilityId = intval($this->spaceModel->syncToBookingModule(intval($space['id'])) ?: 0);
                }
                if ($facilityId <= 0) continue;
                $options[] = [
                    'space_id' => intval($space['id']),
                    'facility_id' => $facilityId,
                    'property_id' => intval($space['property_id'] ?? 0),
                    'space_name' => $space['space_name'] ?? ('Space #' . intval($space['id'])),
                    'property_name' => $space['property_name'] ?? ''
                ];
            }
            if (empty($options) && !empty($booking['space_id'])) {
                $currentSpace = $this->spaceModel->getWithProperty(intval($booking['space_id']));
                if ($currentSpace) {
                    $currentFacilityId = intval($currentSpace['facility_id'] ?? intval($booking['facility_id'] ?? 0));
                    if ($currentFacilityId > 0) {
                        $options[] = [
                            'space_id' => intval($currentSpace['id']),
                            'facility_id' => $currentFacilityId,
                            'property_id' => intval($currentSpace['property_id'] ?? 0),
                            'space_name' => $currentSpace['space_name'] ?? ('Space #' . intval($currentSpace['id'])),
                            'property_name' => $currentSpace['property_name'] ?? ''
                        ];
                    }
                }
            }
            return $options;
        } catch (Exception $e) {
            error_log('Customer portal getVenueOptionsForBooking error: ' . $e->getMessage());
            return [];
        }
    }

    private function resolveBookingSpace($selectedSpaceId, $booking) {
        $spaceId = intval($selectedSpaceId ?: ($booking['space_id'] ?? 0));
        if ($spaceId <= 0) return null;
        $space = $this->spaceModel->getWithProperty($spaceId);
        if (!$space) return null;
        $facilityId = intval($space['facility_id'] ?? 0);
        if ($facilityId <= 0) {
            $facilityId = intval($this->spaceModel->syncToBookingModule($spaceId) ?: 0);
        }
        if ($facilityId <= 0) return null;
        return [
            'space_id' => $spaceId,
            'facility_id' => $facilityId,
            'property_id' => intval($space['property_id'] ?? 0)
        ];
    }

    private function recalculateBookingPricing($bookingId, $booking, $facilityId, $date, $startTime, $endTime) {
        $bookingType = $booking['booking_type'] ?? 'hourly';
        $quantity = in_array($bookingType, ['picnic', 'photoshoot', 'videoshoot', 'workspace'])
            ? max(1, intval($booking['number_of_guests'] ?? 1))
            : max(1, intval($booking['quantity'] ?? 1));

        $baseAmount = floatval($this->facilityModel->calculatePrice(
            $facilityId,
            $date,
            $startTime,
            $endTime,
            $bookingType,
            $quantity,
            false,
            $booking['end_date'] ?? null,
            $booking['equipment_tier'] ?? null
        ));

        $addonsTotal = $this->bookingAddonModel ? floatval($this->bookingAddonModel->getTotalByBooking($bookingId)) : 0;
        $rentalsTotal = $this->bookingRentalModel ? floatval($this->bookingRentalModel->getTotalByBooking($bookingId)) : 0;
        $subtotal = $baseAmount + $addonsTotal + $rentalsTotal;
        $taxRate = floatval($booking['tax_rate'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $discount = floatval($booking['discount_amount'] ?? 0);
        $totalAmount = max(0, $subtotal + $taxAmount - $discount);
        $paidAmount = floatval($booking['paid_amount'] ?? 0);
        $balanceAmount = $totalAmount - $paidAmount;

        return [
            'base_amount' => $baseAmount,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'balance_amount' => $balanceAmount
        ];
    }

    /**
     * View Booking Details (Requires Login)
     */
    public function viewBooking($id) {
        $this->requireCustomerAuth();
        
        $userId = $this->session['customer_user_id'];
        $user = $this->customerPortalUserModel->getById($userId);
        
        try {
            $booking = $this->bookingModel->getWithFacility($id);
            
            // Verify booking belongs to customer
            if (!$booking || $booking['customer_email'] !== $user['email']) {
                $this->setFlashMessage('danger', 'Booking not found.');
                redirect('customer-portal/bookings');
            }
            
            $payments = $this->bookingPaymentModel->getByBooking($id);
        } catch (Exception $e) {
            $booking = null;
            $payments = [];
        }
        
        $data = [
            'page_title' => 'Booking Details',
            'booking' => $booking,
            'payments' => $payments,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/view_booking', $data);
    }
    
    /**
     * Profile
     */
    public function profile() {
        $this->requireCustomerAuth();
        
        $userId = $this->session['customer_user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // Validate CSRF token
            
            $data = [
                'first_name' => sanitize_input($_POST['first_name'] ?? ''),
                'last_name' => sanitize_input($_POST['last_name'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'company_name' => sanitize_input($_POST['company_name'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'zip_code' => sanitize_input($_POST['zip_code'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? '')
            ];
            
            if ($this->customerPortalUserModel->update($userId, $data)) {
                $this->setFlashMessage('success', 'Profile updated successfully.');
                redirect('customer-portal/profile');
            } else {
                $this->setFlashMessage('danger', 'Failed to update profile.');
            }
        }
        
        try {
            $user = $this->customerPortalUserModel->getById($userId);
        } catch (Exception $e) {
            $user = null;
        }
        
        $data = [
            'page_title' => 'My Profile',
            'user' => $user,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/profile', $data);
    }
    
    /**
     * Forgot Password page
     */
    public function forgotPassword() {
        if (isset($this->session['customer_user_id'])) {
            redirect('customer-portal/dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $email = sanitize_input($_POST['email'] ?? '');
            
            if (empty($email) || !validate_email($email)) {
                $this->setFlashMessage('danger', 'Please enter a valid email address.');
                redirect('customer-portal/forgot-password');
            }
            
            $result = $this->customerPortalUserModel->requestPasswordReset($email);
            
            if ($result['success']) {
                // Send reset email with customer portal link
                try {
                    if (file_exists(BASEPATH . '../application/helpers/email_helper.php')) {
                        require_once BASEPATH . '../application/helpers/email_helper.php';
                        
                        if (function_exists('send_password_reset_email')) {
                            $user = $this->customerPortalUserModel->getByEmail($email);
                            $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                            send_password_reset_email($email, $result['token'], $userName ?: null, 'customer');
                        }
                    }
                } catch (Exception $e) {
                    error_log("Customer portal password reset email failed: " . $e->getMessage());
                }
            }
            
            // Always show same message to prevent email enumeration
            $this->setFlashMessage('success', 'If that email is registered, a password reset link has been sent. Please check your inbox.');
            redirect('customer-portal/login');
        }
        
        $data = [
            'page_title' => 'Forgot Password',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/forgot_password', $data);
    }
    
    /**
     * Reset Password page (also used for guest account activation)
     */
    public function resetPassword() {
        if (isset($this->session['customer_user_id'])) {
            redirect('customer-portal/dashboard');
        }
        
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        
        if (empty($token)) {
            $this->setFlashMessage('danger', 'Invalid or missing reset token.');
            redirect('customer-portal/login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate passwords match
            if ($password !== $confirmPassword) {
                $this->setFlashMessage('danger', 'Passwords do not match.');
                redirect('customer-portal/reset-password?token=' . urlencode($token));
                return;
            }
            
            // Validate password strength
            $passwordValidation = validate_password($password);
            if (!$passwordValidation['valid']) {
                $this->setFlashMessage('danger', implode(' ', $passwordValidation['errors']));
                redirect('customer-portal/reset-password?token=' . urlencode($token));
                return;
            }
            
            // Reset password
            if ($this->customerPortalUserModel->resetPassword($token, $password)) {
                $this->setFlashMessage('success', 'Password set successfully! You can now login.');
                redirect('customer-portal/login');
            } else {
                $this->setFlashMessage('danger', 'Invalid or expired reset link. Please request a new one.');
                redirect('customer-portal/forgot-password');
            }
            return;
        }
        
        $data = [
            'page_title' => 'Set Password',
            'token' => $token,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('customer_portal/reset_password', $data);
    }
    
    /**
     * Require customer authentication
     */
    private function requireCustomerAuth() {
        if (!isset($this->session['customer_user_id'])) {
            // Store current URL for redirect after login
            $this->session['customer_redirect_url'] = current_url();
            
            $this->setFlashMessage('warning', 'Please login to access this page.');
            redirect('customer-portal/login');
        }
    }
}

