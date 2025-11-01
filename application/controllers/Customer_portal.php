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
    }
    
    protected function checkAuth() {
        // Public controller for registration/login
        // Individual methods will check authentication
        return true;
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
            
            // Validate
            if (empty($data['email']) || empty($data['password'])) {
                $this->setFlashMessage('danger', 'Email and password are required.');
                redirect('customer-portal/register');
            }
            
            if (strlen($data['password']) < 8) {
                $this->setFlashMessage('danger', 'Password must be at least 8 characters.');
                redirect('customer-portal/register');
            }
            
            $result = $this->customerPortalUserModel->register($data);
            
            if ($result['success']) {
                // TODO: Send verification email
                // For now, auto-verify
                $this->customerPortalUserModel->verifyEmail($result['verification_token']);
                
                $this->setFlashMessage('success', 'Registration successful! Please login.');
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
     * Login page
     */
    public function login() {
        if (isset($this->session['customer_user_id'])) {
            redirect('customer-portal/dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize_input($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rememberMe = !empty($_POST['remember_me']);
            
            $result = $this->customerPortalUserModel->authenticate($email, $password, $rememberMe);
            
            if ($result['success']) {
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
                redirect('customer-portal/dashboard');
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
     * View Booking Details
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
     * Require customer authentication
     */
    private function requireCustomerAuth() {
        if (!isset($this->session['customer_user_id'])) {
            $this->setFlashMessage('warning', 'Please login to access this page.');
            redirect('customer-portal/login');
        }
    }
}

