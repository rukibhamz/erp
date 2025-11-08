<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_Controller {
    protected $db;
    protected $loader;
    protected $config;
    protected $session;
    
    public function __construct() {
        $this->loader = new Loader();
        $this->config = require BASEPATH . 'config/config.php';
        $this->session = &$_SESSION;
        
        // Load common helper
        require_once BASEPATH . '../application/helpers/common_helper.php';
        
        // Load permission helper
        require_once BASEPATH . '../application/helpers/permission_helper.php';
        
        // Load module helper
        require_once BASEPATH . '../application/helpers/module_helper.php';
        
        // Initialize database only if installed and config is valid
        if (isset($this->config['installed']) && $this->config['installed'] === true) {
            if (!empty($this->config['db']['hostname']) && !empty($this->config['db']['database'])) {
                try {
                    // Run automatic migrations before initializing database
                    // This ensures all required tables exist before the application uses them
                    require_once __DIR__ . '/AutoMigration.php';
                    new AutoMigration();
                    
                    $this->db = Database::getInstance();
                } catch (Exception $e) {
                    // Database connection failed - don't die, allow public pages to work
                    error_log('Database connection failed: ' . $e->getMessage());
                    $this->db = null;
                }
            } else {
                error_log('Database configuration incomplete.');
                $this->db = null;
            }
        }
        
        // Check session timeout (30 minutes inactivity)
        if (isset($this->session['last_activity']) && 
            (time() - $this->session['last_activity'] > 1800)) {
            // Session expired
            session_destroy();
            redirect('login?timeout=1');
        }
        
        // Update last activity timestamp
        if (isset($this->session['user_id'])) {
            $this->session['last_activity'] = time();
        }
        
        // Check module activation (unless super admin)
        $this->checkModuleAccess();
        
        // Check authentication for protected pages
        $this->checkAuth();
    }
    
    protected function checkModuleAccess() {
        // Super admin can access all modules
        if (isset($this->session['role']) && $this->session['role'] === 'super_admin') {
            return;
        }
        
        // Map controllers to module keys
        $controllerModuleMap = [
            'Accounting' => 'accounting',
            'Accounts' => 'accounting',
            'Cash' => 'accounting',
            'Receivables' => 'accounting',
            'Payables' => 'accounting',
            'Ledger' => 'accounting',
            'Reports' => 'accounting',
            'Budgets' => 'accounting',
            'Financial_years' => 'accounting',
            'Recurring' => 'accounting',
            'Credit_notes' => 'accounting',
            'Estimates' => 'accounting',
            'Templates' => 'accounting',
            'Banking' => 'accounting',
            'Payroll' => 'accounting',
            'Bookings' => 'bookings',
            'Facilities' => 'bookings',
            'Resource_management' => 'bookings',
            'Booking_wizard' => 'bookings',
            'Booking_reports' => 'bookings',
            'Properties' => 'properties',
            'Spaces' => 'properties',
            'Leases' => 'properties',
            'Tenants' => 'properties',
            'Rent_invoices' => 'properties',
            'Utilities' => 'utilities',
            'Meters' => 'utilities',
            'Meter_readings' => 'utilities',
            'Utility_bills' => 'utilities',
            'Utility_providers' => 'utilities',
            'Utility_payments' => 'utilities',
            'Utility_reports' => 'utilities',
            'Utility_allocations' => 'utilities',
            'Utility_alerts' => 'utilities',
            'Tariffs' => 'utilities',
            'Vendor_utility_bills' => 'utilities',
            'Inventory' => 'inventory',
            'Items' => 'inventory',
            'Locations' => 'inventory',
            'Stock_movements' => 'inventory',
            'Suppliers' => 'inventory',
            'Purchase_orders' => 'inventory',
            'Goods_receipts' => 'inventory',
            'Stock_adjustments' => 'inventory',
            'Stock_takes' => 'inventory',
            'Fixed_assets' => 'inventory',
            'Inventory_reports' => 'inventory',
            'Tax' => 'tax',
            'Tax_compliance' => 'tax',
            'Tax_config' => 'tax',
            'Tax_reports' => 'tax',
            'Vat' => 'tax',
            'Paye' => 'tax',
            'Cit' => 'tax',
            'Wht' => 'tax',
            'Tax_payments' => 'tax',
            'Pos' => 'pos'
        ];
        
        $currentController = get_class($this);
        
        // Check if controller is mapped to a module
        if (isset($controllerModuleMap[$currentController])) {
            $moduleKey = $controllerModuleMap[$currentController];
            
            // Check if module is active
            if (!is_module_active($moduleKey)) {
                $this->setFlashMessage('danger', 'This module is currently inactive. Please contact your administrator.');
                redirect('dashboard');
            }
        }
    }
    
    protected function checkAuth() {
        $publicControllers = ['Auth', 'Error404', 'Payment', 'Booking_wizard', 'Customer_portal'];
        $currentController = get_class($this);
        
        // Always require authentication for non-public controllers
        if (!in_array($currentController, $publicControllers)) {
            // Check if user is authenticated
            if (empty($this->session['user_id'])) {
                // Check if we're trying to access login page (avoid redirect loop)
                if ($currentController !== 'Auth') {
                    redirect('login');
                }
            }
        }
    }
    
    protected function loadModel($model) {
        return $this->loader->model($model);
    }
    
    protected function loadView($view, $data = []) {
        $data['config'] = $this->config;
        $data['session'] = $this->session;
        $data['current_user'] = $this->getCurrentUser();
        
        // Load notifications for logged-in users
        if (isset($this->session['user_id'])) {
            try {
                $notificationModel = $this->loadModel('Notification_model');
                $data['notifications'] = $notificationModel->getUserNotifications($this->session['user_id'], true, 10);
                $data['unread_notification_count'] = $notificationModel->getUnreadCount($this->session['user_id']);
            } catch (Exception $e) {
                $data['notifications'] = [];
                $data['unread_notification_count'] = 0;
            }
        } elseif (isset($this->session['customer_user_id'])) {
            try {
                $notificationModel = $this->loadModel('Notification_model');
                $customerUserModel = $this->loadModel('Customer_portal_user_model');
                $customer = $customerUserModel->getById($this->session['customer_user_id']);
                if ($customer) {
                    $data['notifications'] = $notificationModel->getCustomerNotifications($customer['email'], true, 10);
                    $data['unread_notification_count'] = $notificationModel->getUnreadCount(null, $customer['email']);
                } else {
                    $data['notifications'] = [];
                    $data['unread_notification_count'] = 0;
                }
            } catch (Exception $e) {
                $data['notifications'] = [];
                $data['unread_notification_count'] = 0;
            }
        } else {
            $data['notifications'] = [];
            $data['unread_notification_count'] = 0;
        }
        
        $this->loader->view('layouts/header', $data);
        $this->loader->view($view, $data);
        $this->loader->view('layouts/footer', $data);
    }
    
    protected function getCurrentUser() {
        if (isset($this->session['user_id'])) {
            $userModel = $this->loadModel('User_model');
            return $userModel->getById($this->session['user_id']);
        }
        return null;
    }
    
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function setFlashMessage($type, $message) {
        $this->session['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    protected function getFlashMessage() {
        if (isset($this->session['flash_message'])) {
            $message = $this->session['flash_message'];
            unset($this->session['flash_message']);
            return $message;
        }
        return null;
    }
    
    protected function requirePermission($module, $permission) {
        if (!isset($this->session['user_id'])) {
            redirect('login');
        }
        
        // Super admin and admin bypass
        if (isset($this->session['role']) && ($this->session['role'] === 'super_admin' || $this->session['role'] === 'admin')) {
            return true;
        }
        
        $permissionModel = $this->loadModel('User_permission_model');
        
        // Debug logging for permission checks
        $hasPermission = $permissionModel->hasPermission($this->session['user_id'], $module, $permission);
        
        if (!$hasPermission) {
            // Log the failed permission check for debugging
            error_log("Permission check failed: User ID {$this->session['user_id']}, Role: {$this->session['role']}, Module: {$module}, Permission: {$permission}");
            
            $this->setFlashMessage('danger', 'You do not have permission to perform this action.');
            redirect('dashboard');
        }
        
        return true;
    }
    
    protected function requireRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!isset($this->session['role']) || !in_array($this->session['role'], $roles)) {
            $this->setFlashMessage('danger', 'You do not have sufficient privileges.');
            redirect('dashboard');
        }
        
        return true;
    }
}

