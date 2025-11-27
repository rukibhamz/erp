<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_bills extends Base_Controller {
    private $billModel;
    private $meterModel;
    private $readingModel;
    private $tariffModel;
    private $providerModel;
    private $paymentModel;
    private $transactionModel;
    private $accountModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        try {
            $allBills = $this->billModel->getAll();
            if ($status !== 'all') {
                $bills = array_filter($allBills, function($b) use ($status) {
                    return ($b['status'] ?? '') === $status;
                });
            } else {
                $bills = $allBills;
            }
            
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
            
            $overdueBills = $this->billModel->getOverdue();
        } catch (Exception $e) {
            $bills = [];
            $overdueBills = [];
        }
        
        $data = [
            'page_title' => 'Utility Bills',
            'bills' => $bills,
            'overdue_bills' => $overdueBills,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/bills/index', $data);
    }
    
    public function generate($meterId = null) {
        $this->requirePermission('utilities', 'create');
        
        $meterId = $meterId ?: intval($_GET['meter_id'] ?? 0);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $meterId = intval($_POST['meter_id'] ?? 0);
            $periodStart = sanitize_input($_POST['billing_period_start'] ?? '');
            $periodEnd = sanitize_input($_POST['billing_period_end'] ?? '');
            $currentReading = floatval($_POST['current_reading'] ?? 0);
            $previousReading = floatval($_POST['previous_reading'] ?? 0);
            
            try {
                $meter = $this->meterModel->getWithDetails($meterId);
                if (!$meter) {
                    throw new Exception('Meter not found');
                }
                
                $consumption = $currentReading - $previousReading;
                
                // Get provider (if meter has provider_id, otherwise use utility type's default provider)
                $provider = null;
                if ($meter['provider_id'] ?? null) {
                    $provider = $this->providerModel->getById($meter['provider_id']);
                } else {
                    // Try to get default provider for this utility type
                    $providers = $this->providerModel->getByUtilityType($meter['utility_type_id']);
                    if (!empty($providers)) {
                        $provider = $providers[0];
                    }
                }
                
                // Get tariff for provider
                $tariff = null;
                if ($provider && isset($provider['id'])) {
                    $tariff = $this->tariffModel->getActiveByProvider($provider['id']);
                }
                
                // Calculate bill amount
                $billCalculation = $tariff ? $this->tariffModel->calculateBillAmount($tariff, $consumption) : [
                    'fixed_charge' => 0,
                    'variable_charge' => $consumption * 10, // Default rate
                    'demand_charge' => 0,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'subtotal' => $consumption * 10,
                    'total_amount' => $consumption * 10
                ];
                
                $billData = [
                    'bill_number' => $this->billModel->getNextBillNumber(),
                    'meter_id' => $meterId,
                    'provider_id' => $provider['id'] ?? null,
                    'billing_period_start' => $periodStart,
                    'billing_period_end' => $periodEnd,
                    'billing_date' => date('Y-m-d'),
                    'due_date' => date('Y-m-d', strtotime('+30 days')),
                    'previous_reading' => $previousReading,
                    'current_reading' => $currentReading,
                    'consumption' => $consumption,
                    'consumption_unit' => $meter['unit_of_measure'] ?? 'units',
                    'fixed_charge' => $billCalculation['fixed_charge'],
                    'variable_charge' => $billCalculation['variable_charge'],
                    'demand_charge' => $billCalculation['demand_charge'],
                    'tax_amount' => $billCalculation['tax_amount'],
                    'tax_rate' => $billCalculation['tax_rate'],
                    'amount' => $billCalculation['subtotal'],
                    'total_amount' => $billCalculation['total_amount'],
                    'balance_amount' => $billCalculation['total_amount'],
                    'bill_type' => 'actual',
                    'status' => 'draft',
                    'created_by' => $this->session['user_id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $billId = $this->billModel->create($billData);
                
                if ($billId) {
                    // Post to accounting if needed
                    $this->postBillToAccounting($billId, $billData, $meter);
                    
                    $this->activityModel->log($this->session['user_id'], 'create', 'Utility Bills', 'Generated bill: ' . $billData['bill_number']);
                    $this->setFlashMessage('success', 'Bill generated successfully.');
                    redirect('utilities/bills/view/' . $billId);
                } else {
                    throw new Exception('Failed to create bill');
                }
            } catch (Exception $e) {
                error_log('Utility_bills generate error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Failed to generate bill: ' . $e->getMessage());
            }
        }
        
        try {
            $meters = $this->meterModel->getActive();
            $meter = $meterId ? $this->meterModel->getWithDetails($meterId) : null;
            $lastReading = $meterId ? $this->readingModel->getLatestReading($meterId) : null;
            if (!$lastReading && $meterId) {
                // Fallback to meter's last reading
                $lastReading = [
                    'reading_value' => $meter['last_reading'] ?? 0,
                    'reading_date' => $meter['last_reading_date'] ?? null
                ];
            }
        } catch (Exception $e) {
            $meters = [];
            $meter = null;
            $lastReading = null;
        }
        
        $data = [
            'page_title' => 'Generate Utility Bill',
            'meters' => $meters,
            'selected_meter_id' => $meterId,
            'meter' => $meter,
            'last_reading' => $lastReading,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/bills/generate', $data);
    }
    
    public function view($id) {
        try {
            $bill = $this->billModel->getWithDetails($id);
            if (!$bill) {
                $this->setFlashMessage('danger', 'Bill not found.');
                redirect('utilities/bills');
            }
            
            try {
                $payments = $this->paymentModel->getByBill($id);
            } catch (Exception $e) {
                $payments = [];
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading bill.');
            redirect('utilities/bills');
        }
        
        $data = [
            'page_title' => 'Utility Bill: ' . $bill['bill_number'],
            'bill' => $bill,
            'payments' => $payments,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/bills/view', $data);
    }
    
    private function postBillToAccounting($billId, $billData, $meter) {
        try {
            // Find Utility Expense account (6100)
            $utilityExpenseAccount = $this->accountModel->getByCode('6100');
            if (!$utilityExpenseAccount) {
                // Fallback search
                $expenseAccounts = $this->accountModel->getByType('Expenses');
                foreach ($expenseAccounts as $acc) {
                    if (stripos($acc['account_name'], 'utility') !== false) {
                        $utilityExpenseAccount = $acc;
                        break;
                    }
                }
                if (!$utilityExpenseAccount && !empty($expenseAccounts)) {
                    $utilityExpenseAccount = $expenseAccounts[0];
                }
            }
            
            // Find Accounts Payable account (2000)
            $apAccount = $this->accountModel->getByCode('2000');
            if (!$apAccount) {
                // Fallback search
                $liabilityAccounts = $this->accountModel->getByType('Liabilities');
                foreach ($liabilityAccounts as $acc) {
                    if (stripos($acc['account_name'], 'payable') !== false || 
                        stripos($acc['account_name'], 'ap') !== false) {
                        $apAccount = $acc;
                        break;
                    }
                }
                if (!$apAccount && !empty($liabilityAccounts)) {
                    $apAccount = $liabilityAccounts[0];
                }
            }
            
            if (!$utilityExpenseAccount || !$apAccount) {
                error_log('No utility expense or AP account found for bill accounting entry.');
                return;
            }
            
            // Create Journal Entry
            $journalData = [
                'date' => $billData['billing_date'],
                'reference_type' => 'utility_bill',
                'reference_id' => $billId,
                'description' => 'Utility Bill #' . $billData['bill_number'],
                'journal_type' => 'purchase',
                'entries' => [
                    // Debit Utility Expense
                    [
                        'account_id' => $utilityExpenseAccount['id'],
                        'debit' => $billData['total_amount'],
                        'credit' => 0,
                        'description' => 'Utility Expense - ' . $meter['meter_number']
                    ],
                    // Credit Accounts Payable
                    [
                        'account_id' => $apAccount['id'],
                        'debit' => 0,
                        'credit' => $billData['total_amount'],
                        'description' => 'Utility Bill Payable'
                    ]
                ],
                'created_by' => $this->session['user_id'] ?? null,
                'auto_post' => true
            ];
            
            $this->transactionService->postJournalEntry($journalData);
            
        } catch (Exception $e) {
            error_log('Utility_bills postBillToAccounting error: ' . $e->getMessage());
        }
    }
}

