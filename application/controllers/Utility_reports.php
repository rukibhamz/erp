<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_reports extends Base_Controller {
    private $billModel;
    private $readingModel;
    private $meterModel;
    private $allocationModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->billModel = $this->loadModel('Utility_bill_model');
        $this->readingModel = $this->loadModel('Meter_reading_model');
        $this->meterModel = $this->loadModel('Meter_model');
        $this->allocationModel = $this->loadModel('Utility_allocation_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Utility Reports',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/reports/index', $data);
    }
    
    public function consumption() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $meterId = $_GET['meter_id'] ?? null;
        $utilityTypeId = $_GET['utility_type_id'] ?? null;
        
        try {
            if ($meterId) {
                $readings = $this->readingModel->getByMeter($meterId, $startDate, $endDate);
                $totalConsumption = array_sum(array_column($readings, 'consumption'));
                $meter = $this->meterModel->getWithDetails($meterId);
            } else {
                $readings = [];
                $totalConsumption = 0;
                $meter = null;
            }
            
            $meters = $this->meterModel->getActive();
            $utilityTypes = $this->loadModel('Utility_type_model')->getActive();
        } catch (Exception $e) {
            $readings = [];
            $totalConsumption = 0;
            $meter = null;
            $meters = [];
            $utilityTypes = [];
        }
        
        $data = [
            'page_title' => 'Consumption Report',
            'readings' => $readings,
            'total_consumption' => $totalConsumption,
            'meter' => $meter,
            'meters' => $meters,
            'utility_types' => $utilityTypes,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_meter_id' => $meterId,
            'selected_utility_type_id' => $utilityTypeId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/reports/consumption', $data);
    }
    
    public function cost() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $meterId = $_GET['meter_id'] ?? null;
        
        try {
            $allBills = $this->billModel->getAll();
            $bills = array_filter($allBills, function($b) use ($startDate, $endDate, $meterId) {
                $periodStart = $b['billing_period_start'] ?? '';
                $periodEnd = $b['billing_period_end'] ?? '';
                
                if ($periodStart < $startDate || $periodEnd > $endDate) {
                    return false;
                }
                
                if ($meterId && $b['meter_id'] != $meterId) {
                    return false;
                }
                
                return true;
            });
            
            $totalCost = array_sum(array_column($bills, 'total_amount'));
            $paidAmount = array_sum(array_column($bills, 'paid_amount'));
            $outstanding = $totalCost - $paidAmount;
            
            $meters = $this->meterModel->getActive();
        } catch (Exception $e) {
            $bills = [];
            $totalCost = 0;
            $paidAmount = 0;
            $outstanding = 0;
            $meters = [];
        }
        
        $data = [
            'page_title' => 'Cost Report',
            'bills' => $bills,
            'total_cost' => $totalCost,
            'paid_amount' => $paidAmount,
            'outstanding' => $outstanding,
            'meters' => $meters,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_meter_id' => $meterId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/reports/cost', $data);
    }
    
    public function billing() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $status = $_GET['status'] ?? 'all';
        
        try {
            $allBills = $this->billModel->getAll();
            $bills = array_filter($allBills, function($b) use ($startDate, $endDate, $status) {
                $billingDate = $b['billing_date'] ?? '';
                
                if ($billingDate < $startDate || $billingDate > $endDate) {
                    return false;
                }
                
                if ($status !== 'all' && $b['status'] !== $status) {
                    return false;
                }
                
                return true;
            });
            
            // Enhance bills with meter details
            foreach ($bills as &$bill) {
                if (!empty($bill['meter_id'])) {
                    try {
                        $meter = $this->meterModel->getWithDetails($bill['meter_id']);
                        if ($meter) {
                            $bill['meter_number'] = $meter['meter_number'] ?? null;
                            $bill['utility_type_name'] = $meter['utility_type_name'] ?? null;
                        }
                    } catch (Exception $e) {
                        // Continue without meter details
                    }
                }
            }
            unset($bill);
        } catch (Exception $e) {
            $bills = [];
        }
        
        $data = [
            'page_title' => 'Billing Report',
            'bills' => $bills,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/reports/billing', $data);
    }
}

