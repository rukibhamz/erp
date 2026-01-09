<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Error404 extends Base_Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        http_response_code(404);
        
        $isLoggedIn = isset($this->session['user_id']) && !empty($this->session['user_id']);
        $dashboardUrl = $isLoggedIn ? base_url('dashboard') : base_url();
        
        $data = [
            'is_logged_in' => $isLoggedIn,
            'dashboard_url' => $dashboardUrl,
            'page_title' => '404 Page Not Found'
        ];
        
        $this->loadView('errors/404', $data);
    }
}
