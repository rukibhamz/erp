<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cit extends Base_Controller {
    private $citCalculationModel;
    private $accountModel;
    private $journalModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->citCalculationModel = $this->loadModel('Cit_calculation_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $year = $_GET['year'] ?? date('Y');
        
        try {
            $citCalculation = $this->citCalculationModel->getByYear($year);
        } catch (Exception $e) {
            error_log('Cit index error: ' . $e->getMessage());
            $citCalculation = false;
        }
        
        $data = [
            'page_title' => 'Company Income Tax (CIT)',
            'cit_calculation' => $citCalculation,
            'selected_year' => $year,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/cit/index', $data);
    }
    
    public function calculate() {
        $this->requirePermission('tax', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $year = intval($_POST['year'] ?? date('Y'));
            
            // Check if calculation already exists
            $existing = $this->citCalculationModel->getByYear($year);
            if ($existing) {
                $this->setFlashMessage('danger', 'CIT calculation for this year already exists.');
                redirect('tax/cit');
            }
            
            // Get profit before tax from accounting (would need to pull from P&L or journal entries)
            $profitBeforeTax = floatval($_POST['profit_before_tax'] ?? 0);
            
            // Get adjustments
            $adjustments = [];
            if (!empty($_POST['adjustments'])) {
                foreach ($_POST['adjustments'] as $adj) {
                    if (!empty($adj['description']) && isset($adj['amount'])) {
                        $adjustments[] = [
                            'description' => sanitize_input($adj['description']),
                            'amount' => floatval($adj['amount'])
                        ];
                    }
                }
            }
            
            $capitalAllowances = floatval($_POST['capital_allowances'] ?? 0);
            $taxReliefs = floatval($_POST['tax_reliefs'] ?? 0);
            
            // Calculate CIT
            $result = $this->citCalculationModel->calculateCIT($year, $profitBeforeTax, $adjustments, $capitalAllowances, $taxReliefs);
            
            // Get minimum tax (0.5% of turnover or N500,000)
            $turnover = floatval($_POST['turnover'] ?? 0);
            $minimumTax = max($turnover * 0.005, 500000);
            
            $result['minimum_tax'] = $minimumTax;
            $result['turnover_for_min_tax'] = $turnover;
            $result['final_tax_liability'] = max($result['cit_amount'], $minimumTax);
            
            // Save calculation
            $data = [
                'year' => $year,
                'profit_before_tax' => $profitBeforeTax,
                'adjustments_json' => json_encode($adjustments),
                'total_adjustments' => $result['total_adjustments'],
                'assessable_profit' => $result['assessable_profit'],
                'cit_amount' => $result['cit_amount'],
                'capital_allowances_total' => $capitalAllowances,
                'tax_reliefs_total' => $taxReliefs,
                'minimum_tax' => $minimumTax,
                'turnover_for_min_tax' => $turnover,
                'final_tax_liability' => $result['final_tax_liability'],
                'status' => 'calculated',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->citCalculationModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Tax', 'Calculated CIT for year: ' . $year);
                $this->setFlashMessage('success', 'CIT calculation completed successfully.');
                redirect('tax/cit');
            } else {
                $this->setFlashMessage('danger', 'Failed to save CIT calculation.');
            }
        }
        
        $data = [
            'page_title' => 'Calculate CIT',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/cit/calculate', $data);
    }
    
    public function view($id) {
        try {
            $citCalculation = $this->citCalculationModel->getById($id);
            if (!$citCalculation) {
                $this->setFlashMessage('danger', 'CIT calculation not found.');
                redirect('tax/cit');
            }
            
            $adjustments = json_decode($citCalculation['adjustments_json'] ?? '[]', true);
        } catch (Exception $e) {
            error_log('Cit view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading CIT calculation.');
            redirect('tax/cit');
        }
        
        $data = [
            'page_title' => 'CIT Calculation: ' . ($citCalculation['year'] ?? ''),
            'cit_calculation' => $citCalculation,
            'adjustments' => $adjustments,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/cit/view', $data);
    }
}

