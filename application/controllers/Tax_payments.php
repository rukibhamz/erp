<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_payments extends Base_Controller {
    private $taxPaymentModel;
    private $taxTypeModel;
    private $activityModel;
    private $accountModel;
    private $transactionService;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('tax', 'read');
        $this->taxPaymentModel = $this->loadModel('Tax_payment_model');
        $this->taxTypeModel = $this->loadModel('Tax_type_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->accountModel = $this->loadModel('Account_model');
        
        // Load Transaction Service
        require_once BASEPATH . 'services/Transaction_service.php';
        $this->transactionService = new Transaction_service();
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
            
            $paymentId = $this->taxPaymentModel->create($data);
            
            if ($paymentId) {
                // Post to accounting
                $this->postTaxPaymentToAccounting($paymentId, $data);
                
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
    
    private function postTaxPaymentToAccounting($paymentId, $paymentData) {
        try {
            // Determine Liability Account based on tax_type
            $liabilityAccountCode = null;
            switch (strtolower($paymentData['tax_type'])) {
                case 'vat':
                    $liabilityAccountCode = '2300'; // VAT Payable
                    break;
                case 'paye':
                    $liabilityAccountCode = '2310'; // PAYE Payable
                    break;
                case 'wht':
                    $liabilityAccountCode = '2320'; // WHT Payable
                    break;
                case 'cit':
                case 'company_income_tax':
                    $liabilityAccountCode = '2330'; // CIT Payable
                    break;
                case 'education_tax':
                    $liabilityAccountCode = '2340'; // Education Tax Payable
                    break;
                default:
                    // Generic Tax Payable or try to find by name
                    $liabilityAccountCode = '2300'; 
            }
            
            $liabilityAccount = $this->accountModel->getByCode($liabilityAccountCode);
            if (!$liabilityAccount) {
                // Fallback: search by name
                $liabilityAccounts = $this->accountModel->getByType('Liabilities');
                foreach ($liabilityAccounts as $acc) {
                    if (stripos($acc['account_name'], $paymentData['tax_type']) !== false) {
                        $liabilityAccount = $acc;
                        break;
                    }
                }
                // Ultimate fallback
                if (!$liabilityAccount && !empty($liabilityAccounts)) {
                    $liabilityAccount = $liabilityAccounts[0];
                }
            }
            
            // Find Cash Account (1000)
            $cashAccount = $this->accountModel->getByCode('1000');
            if (!$cashAccount) {
                $assetAccounts = $this->accountModel->getByType('Assets');
                $cashAccount = !empty($assetAccounts) ? $assetAccounts[0] : null;
            }
            
            if ($liabilityAccount && $cashAccount) {
                $journalData = [
                    'date' => $paymentData['payment_date'],
                    'reference_type' => 'tax_payment',
                    'reference_id' => $paymentId,
                    'description' => 'Tax Payment - ' . $paymentData['tax_type'],
                    'journal_type' => 'payment',
                    'entries' => [
                        // Debit Liability
                        [
                            'account_id' => $liabilityAccount['id'],
                            'debit' => $paymentData['amount'],
                            'credit' => 0,
                            'description' => 'Tax Payment: ' . $paymentData['tax_type']
                        ],
                        // Credit Cash
                        [
                            'account_id' => $cashAccount['id'],
                            'debit' => 0,
                            'credit' => $paymentData['amount'],
                            'description' => 'Paid via ' . $paymentData['payment_method']
                        ]
                    ],
                    'created_by' => $this->session['user_id'],
                    'auto_post' => true
                ];
                
                $this->transactionService->postJournalEntry($journalData);
            }
            
        } catch (Exception $e) {
            error_log('Tax_payments postTaxPaymentToAccounting error: ' . $e->getMessage());
        }
    }
}
