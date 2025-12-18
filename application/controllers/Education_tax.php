<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Education_tax extends Base_Controller {
    private $taxModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('education_tax', 'read');
        $this->taxModel = $this->loadModel('Education_tax_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $summary = $this->taxModel->getSummary();
        $data = [
            'page_title' => 'Education Tax Management',
            'summary' => $summary,
            'flash' => $this->getFlashMessage()
        ];
        $this->loadView('education_tax/index', $data);
    }
    
    public function config() {
        $this->requirePermission('education_tax', 'update');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $data = [
                'tax_year' => intval($_POST['tax_year']),
                'tax_rate' => floatval($_POST['tax_rate']),
                'threshold' => floatval($_POST['threshold'] ?? 0)
            ];
            // Check if exists
            $existing = $this->taxModel->getConfig($data['tax_year']);
            if ($existing) {
                $this->db->update('education_tax_config', $data, 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('education_tax_config', $data);
            }
            $this->setFlashMessage('success', 'Configuration updated.');
            redirect('education_tax/config');
        }
        $configs = $this->taxModel->getAllConfigs();
        $this->loadView('education_tax/config', ['page_title' => 'Tax Configuration', 'configs' => $configs]);
    }
    
    public function payments() {
        $payments = $this->taxModel->getPayments();
        $data = [
            'page_title' => 'Tax Payments',
            'payments' => $payments
        ];
        $this->loadView('education_tax/payments', $data);
    }
    
    public function record_payment() {
        $this->requirePermission('education_tax', 'create');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $data = [
                'tax_year' => intval($_POST['tax_year']),
                'amount_paid' => floatval($_POST['amount_paid']),
                'payment_date' => $_POST['payment_date'],
                'payment_reference' => sanitize_input($_POST['reference']),
                'created_by' => $this->session['user_id']
            ];
            if ($this->db->insert('education_tax_payments', $data)) {
                $this->setFlashMessage('success', 'Payment recorded.');
                redirect('education_tax/payments');
            }
        }
        $this->loadView('education_tax/payment_form', ['page_title' => 'Record Payment']);
    }

    public function returns() {
        $returns = $this->taxModel->getReturns();
        $data = [
            'page_title' => 'Tax Returns / Filings',
            'returns' => $returns
        ];
        $this->loadView('education_tax/returns', $data);
    }

    public function file_return($year = null) {
        $this->requirePermission('education_tax', 'create');
        if (!$year) $year = date('Y') - 1;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $profit = floatval($_POST['assessable_profit']);
            $config = $this->taxModel->getConfig($year);
            $rate = $config ? $config['tax_rate'] : 2.5;
            
            $data = [
                'tax_year' => $year,
                'assessable_profit' => $profit,
                'tax_due' => $profit * ($rate / 100),
                'filing_date' => date('Y-m-d'),
                'created_by' => $this->session['user_id']
            ];
            
            if ($this->db->insert('education_tax_returns', $data)) {
                $this->setFlashMessage('success', 'Tax return filed.');
                redirect('education_tax/returns');
            }
        }
        
        $profit = $this->taxModel->calculateAssessableProfit($year);
        $config = $this->taxModel->getConfig($year);
        
        $data = [
            'page_title' => 'File Tax Return: ' . $year,
            'year' => $year,
            'profit' => $profit,
            'config' => $config
        ];
        $this->loadView('education_tax/filing_form', $data);
    }
}
