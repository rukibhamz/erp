<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity extends Base_Controller {
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('activity', 'read');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        $data = [
            'page_title' => 'Activity Log',
            'activities' => $this->activityModel->getAll(null, [], $offset, 'created_at DESC', $perPage),
            'total' => $this->activityModel->count(),
            'current_page' => $page,
            'per_page' => $perPage,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('activity/index', $data);
    }
}

