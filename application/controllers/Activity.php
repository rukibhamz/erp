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
        $search = list_search_term();
        $where = null;
        $whereParams = [];
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where = '(a.description LIKE ? OR a.module LIKE ? OR a.action LIKE ? OR u.username LIKE ? OR CAST(a.id AS CHAR) LIKE ?)';
            $whereParams = [$like, $like, $like, $like, $like];
            $prefix = $this->db->getPrefix();
            $countRow = $this->db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM `{$prefix}activity_log` a
                 LEFT JOIN `{$prefix}users` u ON a.user_id = u.id
                 WHERE {$where}",
                $whereParams
            );
            $total = intval($countRow['cnt'] ?? 0);
        } else {
            $total = $this->activityModel->count();
        }
        $pagination = pagination_build_meta($total, $params['page'], $params['per_page']);

        $data = [
            'page_title' => 'Activity Log',
            'activities' => $this->activityModel->getAll($where, $whereParams, $params['offset'], 'created_at DESC', $params['per_page']),
            'pagination' => $pagination,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('activity/index', $data);
    }
}

