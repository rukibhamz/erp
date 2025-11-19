<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wht extends Base_Controller {
    private $whtReturnModel;
    private $whtTransactionModel;
    private $taxTypeModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->whtReturnModel = $this->loadModel('Wht_return_model');
        $this->whtTransactionModel = $this->loadModel('Wht_transaction_model');
        $this->taxTypeModel = $this->loadModel('Tax_type_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $whtReturns = $this->whtReturnModel->getRecentReturns(20);
        } catch (Exception $e) {
            error_log('Wht index error: ' . $e->getMessage());
            $whtReturns = [];
        }
        
        $data = [
            'page_title' => 'WHT Returns',
            'wht_returns' => $whtReturns,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/wht/index', $data);
    }
    
    public function create() {
        $this->requirePermission('tax', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $month = intval($_POST['month'] ?? date('m'));
            $year = intval($_POST['year'] ?? date('Y'));
            
            // Check if return already exists
            $existing = $this->whtReturnModel->getByPeriod($month, $year);
            if ($existing) {
                $this->setFlashMessage('danger', 'WHT return for this period already exists.');
                redirect('tax/wht');
            }
            
            // Calculate return
            $calculations = $this->whtReturnModel->calculateReturn($month, $year);
            
            // Calculate deadlines (21st of next month)
            $deadlineDate = date('Y-m-21', strtotime(sprintf('%04d-%02d-01', $year, $month) . ' +1 month'));
            
            $returnNumber = $this->whtReturnModel->getNextReturnNumber();
            
            $data = [
                'return_number' => $returnNumber,
                'month' => $month,
                'year' => $year,
                'total_wht' => $calculations['total_wht'],
                'schedule_json' => json_encode($calculations['by_type']),
                'filing_deadline' => $deadlineDate,
                'payment_deadline' => $deadlineDate,
                'status' => 'draft',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->whtReturnModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Tax', 'Created WHT return: ' . $returnNumber);
                $this->setFlashMessage('success', 'WHT return created successfully.');
                redirect('tax/wht');
            } else {
                $this->setFlashMessage('danger', 'Failed to create WHT return.');
            }
        }
        
        $data = [
            'page_title' => 'Create WHT Return',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/wht/create', $data);
    }
    
    public function view($id) {
        try {
            $whtReturn = $this->whtReturnModel->getById($id);
            if (!$whtReturn) {
                $this->setFlashMessage('danger', 'WHT return not found.');
                redirect('tax/wht');
            }
            
            // Get transactions for this period
            $startDate = sprintf('%04d-%02d-01', $whtReturn['year'], $whtReturn['month']);
            $endDate = date('Y-m-t', strtotime($startDate));
            $transactions = $this->whtTransactionModel->getByPeriod($startDate, $endDate);
            
            $byType = json_decode($whtReturn['schedule_json'] ?? '{}', true);
            
        } catch (Exception $e) {
            error_log('Wht view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading WHT return.');
            redirect('tax/wht');
        }
        
        $data = [
            'page_title' => 'WHT Return: ' . ($whtReturn['return_number'] ?? ''),
            'wht_return' => $whtReturn,
            'transactions' => $transactions ?? [],
            'by_type' => $byType ?? [],
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/wht/view', $data);
    }
    
    public function transactions() {
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        try {
            $transactions = $this->whtTransactionModel->getByPeriod($startDate, $endDate);
            $totals = $this->whtTransactionModel->getTotalByPeriod($startDate, $endDate);
        } catch (Exception $e) {
            error_log('Wht transactions error: ' . $e->getMessage());
            $transactions = [];
            $totals = ['total_wht' => 0, 'transaction_count' => 0];
        }
        
        $data = [
            'page_title' => 'WHT Transactions',
            'transactions' => $transactions,
            'totals' => $totals,
            'selected_month' => $month,
            'selected_year' => $year,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/wht/transactions', $data);
    }
}

