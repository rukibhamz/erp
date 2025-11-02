<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_payments extends Base_Controller {
    private $taxPaymentModel;
    private $taxTypeModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->taxPaymentModel = $this->loadModel('Tax_payment_model');
        $this->taxTypeModel = $this->loadModel('Tax_type_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $taxType = $_GET['tax_type'] ?? 'all';
        $periodStart = $_GET['period_start'] ?? '';
        $periodEnd = $_GET['period_end'] ?? '';
        
        try {
            if ($taxType !== 'all') {
                $payments = $this->taxPaymentModel->getByTaxType($taxType, 50);
            } else {
                // Get all payments
                $payments = $this->db->fetchAll(
                    "SELECT * FROM `" . $this->db->getPrefix() . "tax_payments` 
                     ORDER BY payment_date DESC 
                     LIMIT 50"
                );
            }
            
            $taxTypes = $this->taxTypeModel->getAllActive();
        } catch (Exception $e) {
            error_log('Tax_payments index error: ' . $e->getMessage());
            $payments = [];
            $taxTypes = [];
        }
        
        $data = [
            'page_title' => 'Tax Payments',
            'payments' => $payments,
            'tax_types' => $taxTypes,
            'selected_tax_type' => $taxType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/payments/index', $data);
    }
    
    public function create() {
        $this->requirePermission('tax', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'tax_type' => sanitize_input($_POST['tax_type'] ?? ''),
                'amount' => floatval($_POST['amount'] ?? 0),
                'payment_date' => sanitize_input($_POST['payment_date'] ?? date('Y-m-d')),
                'payment_method' => sanitize_input($_POST['payment_method'] ?? 'bank_transfer'),
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'period_covered' => sanitize_input($_POST['period_covered'] ?? ''),
                'bank_name' => sanitize_input($_POST['bank_name'] ?? ''),
                'account_number' => sanitize_input($_POST['account_number'] ?? ''),
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'created_by' => $this->session['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->taxPaymentModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Tax', 'Recorded tax payment: ' . $data['tax_type'] . ' - ' . format_currency($data['amount']));
                $this->setFlashMessage('success', 'Tax payment recorded successfully.');
                redirect('tax/payments');
            } else {
                $this->setFlashMessage('danger', 'Failed to record tax payment.');
            }
        }
        
        try {
            $taxTypes = $this->taxTypeModel->getAllActive();
        } catch (Exception $e) {
            $taxTypes = [];
        }
        
        $data = [
            'page_title' => 'Record Tax Payment',
            'tax_types' => $taxTypes,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('tax/payments/create', $data);
    }
}

