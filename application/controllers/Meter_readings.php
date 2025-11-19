<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meter_readings extends Base_Controller {
    private $readingModel;
    private $meterModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->readingModel = $this->loadModel('Meter_reading_model');
        $this->meterModel = $this->loadModel('Meter_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $meterId = $_GET['meter_id'] ?? null;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        try {
            if ($meterId) {
                $readings = $this->readingModel->getByMeter($meterId, $startDate, $endDate);
                $meter = $this->meterModel->getById($meterId);
            } else {
                $readings = [];
                $meter = null;
            }
            
            $meters = $this->meterModel->getActive();
        } catch (Exception $e) {
            $readings = [];
            $meter = null;
            $meters = [];
        }
        
        $data = [
            'page_title' => 'Meter Readings',
            'readings' => $readings,
            'meters' => $meters,
            'selected_meter_id' => $meterId,
            'meter' => $meter,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/readings/index', $data);
    }
    
    public function create() {
        $this->requirePermission('utilities', 'create');
        
        $meterId = $_GET['meter_id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $data = [
                'meter_id' => intval($_POST['meter_id'] ?? 0),
                'reading_date' => sanitize_input($_POST['reading_date'] ?? date('Y-m-d')),
                'reading_value' => floatval($_POST['reading_value'] ?? 0),
                'reading_type' => sanitize_input($_POST['reading_type'] ?? 'actual'),
                'reader_id' => $this->session['user_id'] ?? null,
                'reader_name' => sanitize_input($_POST['reader_name'] ?? ''),
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'is_verified' => !empty($_POST['is_verified']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $readingId = $this->readingModel->create($data);
            
            if ($readingId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Meter Readings', 'Recorded reading for meter: ' . $data['meter_id']);
                $this->setFlashMessage('success', 'Reading recorded successfully.');
                redirect('utilities/readings?meter_id=' . $data['meter_id']);
            } else {
                $this->setFlashMessage('danger', 'Failed to record reading.');
            }
        }
        
        try {
            $meters = $this->meterModel->getActive();
            $meter = $meterId ? $this->meterModel->getWithDetails($meterId) : null;
            $lastReading = $meterId ? $this->meterModel->getLastReading($meterId) : null;
        } catch (Exception $e) {
            $meters = [];
            $meter = null;
            $lastReading = null;
        }
        
        $data = [
            'page_title' => 'Record Meter Reading',
            'meters' => $meters,
            'selected_meter_id' => $meterId,
            'meter' => $meter,
            'last_reading' => $lastReading,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/readings/create', $data);
    }
}

