<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Error404 {
    
    public function index() {
        http_response_code(404);
        $loader = new Loader();
        $loader->view('errors/404');
    }
}

