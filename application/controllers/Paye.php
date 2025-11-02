<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paye extends Base_Controller {
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
    }
    
    public function index() {
        $data = ['page_title' => 'PAYE Management', 'flash' => $this->getFlashMessage()];
        $this->loadView('tax/paye/index', $data);
    }
}

