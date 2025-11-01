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
        
        // Initialize database only if installed
        if (isset($this->config['installed']) && $this->config['installed'] === true) {
            try {
                $this->db = Database::getInstance();
            } catch (Exception $e) {
                // Database connection failed
                error_log('Database connection failed: ' . $e->getMessage());
            }
        }
        
        // Check authentication for protected pages
        $this->checkAuth();
    }
    
    protected function checkAuth() {
        $publicControllers = ['Auth', 'Error404', 'Payment', 'Booking_wizard', 'Customer_portal'];
        $currentController = get_class($this);
        
        if (!in_array($currentController, $publicControllers)) {
            if (!isset($this->session['user_id'])) {
                redirect('login');
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
        
        // Super admin bypass
        if (isset($this->session['role']) && $this->session['role'] === 'super_admin') {
            return true;
        }
        
        $permissionModel = $this->loadModel('User_permission_model');
        if (!$permissionModel->hasPermission($this->session['user_id'], $module, $permission)) {
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

