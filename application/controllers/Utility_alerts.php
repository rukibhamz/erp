<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_alerts extends Base_Controller {
    private $alertModel;
    private $meterModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->alertModel = $this->loadModel('Meter_alert_model');
        $this->meterModel = $this->loadModel('Meter_model');
    }
    
    public function index() {
        $status = $_GET['status'] ?? 'unresolved';
        
        try {
            if ($status === 'unresolved') {
                $all = $this->alertModel->getUnresolved();
            } else {
                $allAlerts = $this->alertModel->getAll();
                $all = array_values(array_filter($allAlerts, function($a) use ($status) {
                    return $status === 'all' || ($status === 'resolved' && $a['is_resolved']) || ($status === 'unresolved' && !$a['is_resolved']);
                }));
            }
            $paged = $this->paginateList($all, null, standard_list_search_fields('generic'));
            $alerts = $paged['items'];
        } catch (Exception $e) {
            $alerts = [];
            $paged = ['pagination' => pagination_build_meta(0, 1, 50)];
        }
        
        $data = [
            'page_title' => 'Meter Alerts',
            'alerts' => $alerts,
            'pagination' => $paged['pagination'] ?? pagination_build_meta(0, 1, 50),
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/alerts/index', $data);
    }
    
    public function resolve($id) {
        $this->requirePermission('utilities', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resolutionNotes = sanitize_input($_POST['resolution_notes'] ?? '');
            
            try {
                if ($this->alertModel->update($id, [
                    'is_resolved' => 1,
                    'resolved_at' => date('Y-m-d H:i:s'),
                    'resolved_by' => $this->session['user_id'],
                    'resolution_notes' => $resolutionNotes
                ])) {
                    $this->setFlashMessage('success', 'Alert resolved successfully.');
                } else {
                    $this->setFlashMessage('danger', 'Failed to resolve alert.');
                }
            } catch (Exception $e) {
                $this->setFlashMessage('danger', 'Error resolving alert: ' . $e->getMessage());
            }
        }
        
        redirect('utilities/alerts');
    }
}

