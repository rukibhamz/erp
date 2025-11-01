<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends Base_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Settings',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/index', $data);
    }
    
    public function modules() {
        $this->requirePermission('settings', 'update');
        
        $data = [
            'page_title' => 'Module Settings',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/modules', $data);
    }
}

