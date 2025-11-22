<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Error404 {
    
    public function index() {
        http_response_code(404);
        
        // Load URL helper for base_url function
        require_once BASEPATH . 'helpers/url_helper.php';
        
        $loader = new Loader();
        
        // Check if user is logged in
        $isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
        $dashboardUrl = $isLoggedIn ? base_url('dashboard') : base_url();
        
        $data = [
            'is_logged_in' => $isLoggedIn,
            'dashboard_url' => $dashboardUrl
        ];
        
        $loader->view('errors/404', $data);
    }
}

