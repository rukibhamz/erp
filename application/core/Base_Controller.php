<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_Controller {
    protected $db;
    protected $loader;
    protected $load;
    protected $config;
    protected $session;
    protected $router;
    public $input; // Explicitly declared for PHP 8.2 compatibility
    
    public function __construct() {
        $this->loader = new Loader();
        $this->load = $this->loader;
        $this->config = require BASEPATH . 'config/config.php';
        $this->session = &$_SESSION;
        
        // Initialize Input class (mimics CodeIgniter 3's Input class)
        $this->input = new Input();
        
        // Load common helper
        require_once BASEPATH . '../application/helpers/common_helper.php';
        
        // Load validation helper
        require_once BASEPATH . '../application/helpers/validation_helper.php';
        
        // Load CSRF protection helper (SECURITY: Required for CSRF protection)
        require_once BASEPATH . '../application/helpers/csrf_helper.php';
        
        // Load Composer autoloader if available (for PHPMailer and other dependencies)
        $composerAutoload = BASEPATH . '../vendor/autoload.php';
        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
        }
        
        // Load email helper (for secure email sending)
        require_once BASEPATH . '../application/helpers/email_helper.php';
        
        // Load permission helper
        require_once BASEPATH . '../application/helpers/permission_helper.php';
        
        // Load security helper (for security headers and session config)
        require_once BASEPATH . '../application/helpers/security_helper.php';
        
        // Set security headers on all responses
        set_security_headers();
        
        // Load module helper
        require_once BASEPATH . '../application/helpers/module_helper.php';
        
        // Load JSON helper (for safe JSON decoding and validation)
        require_once BASEPATH . '../application/helpers/json_helper.php';
        
        // Load number helper (for formatting large numbers)
        require_once BASEPATH . '../application/helpers/number_helper.php';
        
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
        
        // Check session timeout (30 minutes inactivity) - Only for logged in users
        if (isset($this->session['user_id']) && isset($this->session['last_activity']) && 
            (time() - $this->session['last_activity'] > 1800)) {
            error_log("Base_Controller: Session expired for user " . $this->session['user_id']);
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
        
        
        // Run authorization checks (before filter)
        $this->checkAuthorization();
        
        // Final check: Maintenance Mode enforcement
        $this->checkMaintenanceMode();
    }
    
    protected function checkModuleAccess() {
        // Skip module check for public controllers (guest-accessible)
        $publicControllers = ['auth', 'error404', 'payment', 'booking_wizard', 'customer_portal'];
        $currentClass = strtolower(get_class($this));
        
        if (in_array($currentClass, $publicControllers)) {
            return;
        }
        
        // Super admin can access all modules
        if (isset($this->session['role']) && $this->session['role'] === 'super_admin') {
            return;
        }
        
        // Map controllers to module keys
        $controllerModuleMap = [
            'Accounting' => 'accounting',
            // ... (rest of map) ...
        ];
        
        // ...
        
        // Check if controller is mapped to a module
        if (isset($controllerModuleMap[$currentClass])) {
            $moduleKey = $controllerModuleMap[$currentClass];
            
            // Check if module is active
            if (!is_module_active($moduleKey)) {
                error_log("Base_Controller: Module inactive redirect - Class: $currentClass, Module: $moduleKey");
                $this->setFlashMessage('danger', 'This module is currently inactive. Please contact your administrator.');
                redirect('dashboard');
            }
        }
    }
    
    protected function checkAuth() {
        $publicControllers = ['auth', 'error404', 'payment', 'booking_wizard', 'customer_portal'];
        $currentController = strtolower(get_class($this));
        
        // Always require authentication for non-public controllers
        if (!in_array($currentController, $publicControllers)) {
            // Check if user is authenticated
            if (empty($this->session['user_id'])) {
                error_log("Base_Controller: Auth required redirect - Class: $currentController");
                // Check if we're trying to access login page (avoid redirect loop)
                if ($currentController !== 'auth') {
                    redirect('login');
                }
            }
        }
    }
    
    protected function loadModel($model) {
        return $this->loader->model($model);
    }
    
    protected function loadLibrary($library, $params = null) {
        $libraryFile = BASEPATH . 'libraries/' . $library . '.php';
        
        if (file_exists($libraryFile)) {
            require_once $libraryFile;
            
            // Create instance with params if provided
            if ($params !== null) {
                return new $library($params);
            } else {
                return new $library();
            }
        }
        
        error_log("Library not found: {$libraryFile}");
        return null;
    }
    
    protected function loadView($view, $data = []) {
        $data['config'] = $this->config;
        $data['session'] = $this->session;
        
        // Pass maintenance mode to all views
        $data['maintenance_mode'] = $this->getSetting('maintenance_mode');
        $data['is_super_admin'] = isset($this->session['role']) && $this->session['role'] === 'super_admin';
        
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
    
    /**
     * Require user to have one of the specified roles
     * 
     * @param string|array $roles Required role(s)
     * @return bool True if user has required role
     * @throws void Redirects if role check fails
     */
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
    
    /**
     * Require user to be authenticated
     * 
     * Centralized authentication check. Redirects to login if not authenticated.
     * 
     * @return bool True if authenticated
     * @throws void Redirects to login if not authenticated
     */
    protected function requireAuth() {
        if (empty($this->session['user_id'])) {
            $this->setFlashMessage('info', 'Please login to access this page.');
            redirect('login');
        }
        return true;
    }
    
    /**
     * Check if user has specific permission
     * 
     * Centralized permission check. Returns true/false without redirecting.
     * Use requirePermission() if you want automatic redirect on failure.
     * 
     * @param string $module Module name
     * @param string $permission Permission name
     * @return bool True if user has permission
     */
    protected function checkPermission($module, $permission) {
        if (!isset($this->session['user_id'])) {
            return false;
        }
        
        // Super admin and admin bypass
        if (isset($this->session['role']) && ($this->session['role'] === 'super_admin' || $this->session['role'] === 'admin')) {
            return true;
        }
        
        try {
            $permissionModel = $this->loadModel('User_permission_model');
            return $permissionModel->hasPermission($this->session['user_id'], $module, $permission);
        } catch (Exception $e) {
            error_log("Permission check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Before filter hook for authorization
     * 
     * Override this method in child controllers to add authorization checks
     * that run before any action method. This centralizes authorization logic.
     * 
     * Example:
     * protected function beforeFilter() {
     *     parent::beforeFilter();
     *     $this->requireAuth();
     *     $this->requireRole('admin');
     * }
     * 
     * @return void
     */
    protected function beforeFilter() {
        // Override in child controllers to add authorization
        // This method is called automatically before action methods
    }
    
    /**
     * Check authorization before executing action
     * 
     * This method is called automatically before action methods.
     * It calls beforeFilter() which can be overridden in child controllers.
     * 
     * @return void
     */
    protected function checkAuthorization() {
        // Call before filter hook
        $this->beforeFilter();
        
        // Additional centralized checks can be added here
        // For example, checking module access is already done in checkModuleAccess()
    }
    
    /**
     * Check if maintenance mode is enabled and enforce restrictions
     */
    protected function checkMaintenanceMode() {
        // Super Admin bypasses maintenance mode
        if (isset($this->session['role']) && $this->session['role'] === 'super_admin') {
            return;
        }

        $maintenanceMode = $this->getSetting('maintenance_mode');
        // DEBUG: error_log("Maintenance Mode Debug: value=" . var_export($maintenanceMode, true));
        
        if ($maintenanceMode && intval($maintenanceMode) === 1) {
            $currentClass = strtolower(get_class($this));
            
            // Exemptions: Auth (so admins can login) and Booking_wizard (per request)
            $exemptions = ['auth', 'booking_wizard', 'error404'];
            
            if (!in_array($currentClass, $exemptions)) {
                // If not exempt, redirect to the maintenance page
                $this->showMaintenanceView();
            }
        }
    }

    /**
     * Get a setting value from the database
     */
    protected function getSetting($key) {
        if (!$this->db) {
            return null;
        }
        
        try {
            $prefix = $this->db->getPrefix();
            $result = $this->db->fetchOne(
                "SELECT setting_value FROM `{$prefix}settings` WHERE setting_key = ?",
                [$key]
            );
            return $result ? $result['setting_value'] : null;
        } catch (Exception $e) {
            error_log("Base_Controller getSetting error ($key): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Show the dedicated maintenance view and terminate
     */
    protected function showMaintenanceView() {
        $data = [
            'page_title' => 'System Maintenance',
            'config' => $this->config,
            'session' => $this->session
        ];
        
        // We'll use a simple standalone layout for maintenance
        $this->loader->view('layouts/header_public', $data);
        $this->loader->view('errors/html/maintenance', $data);
        $this->loader->view('layouts/footer_public', $data);
        exit;
    }

    /**
     * Check if a column exists in a table
     * SECURITY: Uses parameterized query to prevent SQL injection
     * 
     * @param string $table Table name (without prefix)
     * @param string $column Column name
     * @return bool True if column exists, false otherwise
     */
    protected function checkColumnExists($table, $column) {
        try {
            if (!$this->db) {
                return false;
            }
            $prefix = $this->db->getPrefix();
            $result = $this->db->fetchOne(
                "SHOW COLUMNS FROM `{$prefix}{$table}` LIKE ?",
                [$column]
            );
            return !empty($result);
        } catch (Exception $e) {
            error_log("Error checking column {$table}.{$column}: " . $e->getMessage());
            return false;
        }
    }
}

