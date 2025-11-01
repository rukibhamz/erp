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
        $publicControllers = ['Auth', 'Error404'];
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
}

