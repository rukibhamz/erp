<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Base_Controller {
    private $userModel;
    private $companyModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = $this->loadModel('User_model');
        $this->companyModel = $this->loadModel('Company_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Dashboard',
            'total_users' => $this->userModel->count(),
            'total_companies' => $this->companyModel->count(),
            'recent_activities' => $this->activityModel->getRecent(10)
        ];
        
        $this->loadView('dashboard/index', $data);
    }
}

