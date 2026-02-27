<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_portal extends Base_Controller {
    private $customerPortalUserModel;
    private $bookingModel;
    private $bookingPaymentModel;
    
    public function __construct() {
        parent::__construct();
        $this->customerPortalUserModel = $this->loadModel('Customer_portal_user_model');
        $this->bookingModel = $this->loadModel('Booking_model');
        $this->bookingPaymentModel = $this->loadModel('Booking_payment_model');
        $this->loadLibrary('Email_sender');
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
            
            // Validate email format
            if (!validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
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
                    setcookie('customer_remember_token', $result['user']['remember_token'], time() + (86400 * 30), '/'); // 30 days
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
            }
            
            // Validate password strength
            $passwordValidation = validate_password($password);
            if (!$passwordValidation['valid']) {
                $this->setFlashMessage('danger', implode(' ', $passwordValidation['errors']));
                redirect('customer-portal/reset-password?token=' . urlencode($token));
            }
            
            // Reset password
            if ($this->customerPortalUserModel->resetPassword($token, $password)) {
                $this->setFlashMessage('success', 'Password set successfully! You can now login.');
                redirect('customer-portal/login');
            } else {
                $this->setFlashMessage('danger', 'Invalid or expired reset link. Please request a new one.');
                redirect('customer-portal/forgot-password');
            }
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

