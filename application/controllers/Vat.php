<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vat extends Base_Controller {
    private $vatReturnModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->vatReturnModel = $this->loadModel('Vat_return_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $period = $_GET['period'] ?? date('Y-m');
        list($year, $month) = explode('-', $period);
        
        try {
            $vatReturns = $this->vatReturnModel->getRecentReturns(20);
        } catch (Exception $e) {
            $vatReturns = [];
        }
        
        $data = [
            'page_title' => 'VAT Returns',
            'vat_returns' => $vatReturns,
            'selected_period' => $period,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/vat/index', $data);
    }
    
    public function create() {
        $this->requirePermission('tax', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $periodStart = sanitize_input($_POST['period_start'] ?? '');
            $periodEnd = sanitize_input($_POST['period_end'] ?? '');
            
            // Calculate VAT return
            $calculations = $this->vatReturnModel->calculateReturn($periodStart, $periodEnd);
            
            $returnNumber = $this->vatReturnModel->getNextReturnNumber();
            
            // Calculate deadlines (21st of next month for VAT)
            $deadlineDate = date('Y-m-21', strtotime($periodEnd . ' +1 month'));
            
            $data = [
                'return_number' => $returnNumber,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'output_vat' => $calculations['output_vat'],
                'input_vat' => $calculations['input_vat'],
                'net_vat' => $calculations['net_vat'],
                'vat_payable' => max(0, $calculations['net_vat']),
                'vat_refundable' => max(0, -$calculations['net_vat']),
                'filing_deadline' => $deadlineDate,
                'payment_deadline' => $deadlineDate,
                'status' => 'draft',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->vatReturnModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Tax', 'Created VAT return: ' . $returnNumber);
                $this->setFlashMessage('success', 'VAT return created successfully.');
                redirect('tax/vat');
            } else {
                $this->setFlashMessage('danger', 'Failed to create VAT return.');
            }
        }
        
        $data = [
            'page_title' => 'Create VAT Return',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/vat/create', $data);
    }
    
    public function view($id) {
        try {
            $vatReturn = $this->vatReturnModel->getById($id);
            if (!$vatReturn) {
                $this->setFlashMessage('danger', 'VAT return not found.');
                redirect('tax/vat');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading VAT return.');
            redirect('tax/vat');
        }
        
        $data = [
            'page_title' => 'VAT Return: ' . ($vatReturn['return_number'] ?? ''),
            'vat_return' => $vatReturn,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/vat/view', $data);
    }
}

