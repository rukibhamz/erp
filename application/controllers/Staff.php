<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Staff extends Base_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('employees', 'read');
    }
    
    public function index() {
        redirect('employees');
    }
}
