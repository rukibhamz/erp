<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utilities extends Base_Controller {
    private $utilityTypeModel;
    private $providerModel;
    private $meterModel;
    private $readingModel;
    private $billModel;
    private $paymentModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->utilityTypeModel = $this->loadModel('Utility_type_model');
        $this->providerModel = $this->loadModel('Utility_provider_model');
        $this->meterModel = $this->loadModel('Meter_model');
        $this->readingModel = $this->loadModel('Meter_reading_model');
        $this->billModel = $this->loadModel('Utility_bill_model');
        $this->paymentModel = $this->loadModel('Utility_payment_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        // Dashboard - Overview of utilities
        try {
            $totalMeters = count($this->meterModel->getActive());
            $totalBills = count($this->billModel->getAll());
            $overdueBills = count($this->billModel->getOverdue());
            
            // Get consumption stats for current month
            $currentMonth = date('Y-m');
            try {
                $allReadings = $this->readingModel->getAll();
                $thisMonthReadings = array_filter($allReadings, function($r) use ($currentMonth) {
                    return isset($r['reading_date']) && strpos($r['reading_date'], $currentMonth) === 0;
                });
                $totalConsumption = array_sum(array_column($thisMonthReadings, 'consumption'));
            } catch (Exception $e) {
                $totalConsumption = 0;
            }
            
            // Get utility types
            $utilityTypes = $this->utilityTypeModel->getActive();
        } catch (Exception $e) {
            $totalMeters = 0;
            $totalBills = 0;
            $overdueBills = 0;
            $totalConsumption = 0;
            $utilityTypes = [];
        }
        
        $data = [
            'page_title' => 'Utilities Dashboard',
            'stats' => [
                'total_meters' => $totalMeters,
                'total_bills' => $totalBills,
                'overdue_bills' => $overdueBills,
                'total_consumption' => $totalConsumption
            ],
            'utility_types' => $utilityTypes,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/index', $data);
    }
}

