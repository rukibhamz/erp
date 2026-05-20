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
        $params = $this->paginationParams();
        $total = $this->activityModel->count();
        $pagination = pagination_build_meta($total, $params['page'], $params['per_page']);

        $data = [
            'page_title' => 'Activity Log',
            'activities' => $this->activityModel->getAll(null, [], $params['offset'], 'created_at DESC', $params['per_page']),
            'pagination' => $pagination,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('activity/index', $data);
    }
}

