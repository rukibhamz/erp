<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Error404 extends Base_Controller {
    
    public function __construct() {
        // Don't call parent constructor to skip auth check
    }
    
    public function index() {
        http_response_code(404);
        $this->loader = new Loader();
        $this->loader->view('errors/404');
    }
}

