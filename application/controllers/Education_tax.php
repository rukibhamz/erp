<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Education_tax extends Base_Controller {
    private $taxModel;
    private $activityModel;
    private $transactionService;
    private $accountModel;
    private $cashAccountModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('education_tax', 'read');
        $this->taxModel = $this->loadModel('Education_tax_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        
        // Load Transaction Service
        $transactionServicePath = BASEPATH . 'services/Transaction_service.php';
        if (file_exists($transactionServicePath)) {
            require_once $transactionServicePath;
            $this->transactionService = new Transaction_service();
        } else {
            $this->transactionService = null;
        }
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
                $paymentId = $this->db->lastInsertId();
                
                // Create journal entry: Dr Tax Expense, Cr Cash/Bank
                if ($this->transactionService) {
                    try {
                        $taxExpenseAccount = $this->accountModel->getByCode('5300'); // Education Tax Expense
                        if (!$taxExpenseAccount) {
                            // Fallback: try generic tax expense
                            $taxExpenseAccount = $this->accountModel->getByCode('5200');
                        }
                        
                        $defaultCashAccount = $this->cashAccountModel->getDefault();
                        
                        if ($taxExpenseAccount && $defaultCashAccount) {
                            $cashAccountId = $defaultCashAccount['account_id'] ?? $defaultCashAccount['id'];
                            
                            $journalData = [
                                'date' => $data['payment_date'],
                                'reference_type' => 'education_tax_payment',
                                'reference_id' => $paymentId,
                                'description' => 'Education Tax Payment - Year ' . $data['tax_year'] . ' (Ref: ' . $data['payment_reference'] . ')',
                                'journal_type' => 'payment',
                                'entries' => [
                                    [
                                        'account_id' => $taxExpenseAccount['id'],
                                        'debit' => $data['amount_paid'],
                                        'credit' => 0.00,
                                        'description' => 'Education Tax Expense'
                                    ],
                                    [
                                        'account_id' => $cashAccountId,
                                        'debit' => 0.00,
                                        'credit' => $data['amount_paid'],
                                        'description' => 'Cash Payment'
                                    ]
                                ],
                                'created_by' => $this->session['user_id'],
                                'auto_post' => true
                            ];
                            
                            $this->transactionService->postJournalEntry($journalData);
                            
                            // Update cash account balance
                            $this->cashAccountModel->updateBalance($defaultCashAccount['id'], $data['amount_paid'], 'withdrawal');
                        } else {
                            error_log('Education Tax: Tax expense account (5300) or cash account not found for journal entry');
                        }
                    } catch (Exception $e) {
                        error_log('Education Tax payment journal entry error: ' . $e->getMessage());
                    }
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Education Tax', 'Recorded tax payment of ' . number_format($data['amount_paid'], 2) . ' for year ' . $data['tax_year']);
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
