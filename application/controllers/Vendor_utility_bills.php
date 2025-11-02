<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Vendor_utility_bills extends Base_Controller {
    private $vendorBillModel;
    private $providerModel;
    private $transactionModel;
    private $accountModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->vendorBillModel = $this->loadModel('Vendor_utility_bill_model');
        $this->providerModel = $this->loadModel('Utility_provider_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $status = $_GET['status'] ?? 'all';
        
        try {
            $allBills = $this->vendorBillModel->getAll();
            if ($status !== 'all') {
                $bills = array_filter($allBills, function($b) use ($status) {
                    return ($b['status'] ?? '') === $status;
                });
            } else {
                $bills = $allBills;
            }
            
            // Enhance with provider details
            foreach ($bills as &$bill) {
                if (!empty($bill['provider_id'])) {
                    try {
                        $provider = $this->providerModel->getWithUtilityType($bill['provider_id']);
                        if ($provider) {
                            $bill['provider_name'] = $provider['provider_name'] ?? null;
                            $bill['utility_type_name'] = $provider['utility_type_name'] ?? null;
                        }
                    } catch (Exception $e) {
                        // Continue without provider details
                    }
                }
            }
            unset($bill);
            
            $overdueBills = $this->vendorBillModel->getOverdue();
        } catch (Exception $e) {
            $bills = [];
            $overdueBills = [];
        }
        
        $data = [
            'page_title' => 'Vendor Utility Bills',
            'bills' => $bills,
            'overdue_bills' => $overdueBills,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/vendor_bills/index', $data);
    }
    
    public function create() {
        $this->requirePermission('utilities', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'vendor_bill_number' => sanitize_input($_POST['vendor_bill_number'] ?? ''),
                'provider_id' => intval($_POST['provider_id'] ?? 0),
                'bill_date' => sanitize_input($_POST['bill_date'] ?? date('Y-m-d')),
                'due_date' => sanitize_input($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'))),
                'period_start' => !empty($_POST['period_start']) ? sanitize_input($_POST['period_start']) : null,
                'period_end' => !empty($_POST['period_end']) ? sanitize_input($_POST['period_end']) : null,
                'consumption' => !empty($_POST['consumption']) ? floatval($_POST['consumption']) : null,
                'amount' => floatval($_POST['amount'] ?? 0),
                'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
                'total_amount' => floatval($_POST['total_amount'] ?? 0),
                'balance_amount' => floatval($_POST['total_amount'] ?? 0),
                'status' => 'pending',
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $billId = $this->vendorBillModel->create($data);
            
            if ($billId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Vendor Utility Bills', 'Created vendor bill: ' . $data['vendor_bill_number']);
                $this->setFlashMessage('success', 'Vendor bill created successfully.');
                redirect('utilities/vendor-bills/view/' . $billId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create vendor bill.');
            }
        }
        
        try {
            $providers = $this->providerModel->getActive();
        } catch (Exception $e) {
            $providers = [];
        }
        
        $data = [
            'page_title' => 'Create Vendor Bill',
            'providers' => $providers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/vendor_bills/create', $data);
    }
    
    public function view($id) {
        try {
            $bill = $this->vendorBillModel->getWithProvider($id);
            if (!$bill) {
                $this->setFlashMessage('danger', 'Vendor bill not found.');
                redirect('utilities/vendor-bills');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading vendor bill.');
            redirect('utilities/vendor-bills');
        }
        
        $data = [
            'page_title' => 'Vendor Bill: ' . $bill['vendor_bill_number'],
            'bill' => $bill,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/vendor_bills/view', $data);
    }
    
    public function approve($id) {
        $this->requirePermission('utilities', 'update');
        
        try {
            $bill = $this->vendorBillModel->getById($id);
            if (!$bill) {
                $this->setFlashMessage('danger', 'Bill not found.');
                redirect('utilities/vendor-bills');
            }
            
            if ($this->vendorBillModel->update($id, [
                'status' => 'approved',
                'approved_by' => $this->session['user_id'],
                'approved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ])) {
                // Post to accounting
                $this->postVendorBillToAccounting($id, $bill);
                
                $this->activityModel->log($this->session['user_id'], 'update', 'Vendor Utility Bills', 'Approved vendor bill: ' . $bill['vendor_bill_number']);
                $this->setFlashMessage('success', 'Vendor bill approved successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to approve vendor bill.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error approving vendor bill: ' . $e->getMessage());
        }
        
        redirect('utilities/vendor-bills/view/' . $id);
    }
    
    private function postVendorBillToAccounting($billId, $bill) {
        try {
            // Find Utility Expense account
            $expenseAccounts = $this->accountModel->getByType('Expenses');
            $utilityExpenseAccount = null;
            foreach ($expenseAccounts as $acc) {
                if (stripos($acc['account_name'], 'utility') !== false) {
                    $utilityExpenseAccount = $acc;
                    break;
                }
            }
            if (!$utilityExpenseAccount && !empty($expenseAccounts)) {
                $utilityExpenseAccount = $expenseAccounts[0]; // Fallback
            }
            
            // Find Accounts Payable account
            $liabilityAccounts = $this->accountModel->getByType('Liabilities');
            $apAccount = null;
            foreach ($liabilityAccounts as $acc) {
                if (stripos($acc['account_name'], 'payable') !== false || 
                    stripos($acc['account_name'], 'ap') !== false) {
                    $apAccount = $acc;
                    break;
                }
            }
            if (!$apAccount && !empty($liabilityAccounts)) {
                $apAccount = $liabilityAccounts[0]; // Fallback
            }
            
            if (!$utilityExpenseAccount || !$apAccount) {
                error_log('No utility expense or AP account found for vendor bill accounting entry.');
                return;
            }
            
            // Entry 1: Debit Utility Expense
            $this->transactionModel->create([
                'transaction_number' => 'VBIL-' . $billId . '-EXP',
                'transaction_date' => $bill['bill_date'],
                'transaction_type' => 'expense',
                'reference_id' => $billId,
                'reference_type' => 'vendor_utility_bill',
                'account_id' => $utilityExpenseAccount['id'],
                'description' => 'Vendor utility bill - ' . $bill['vendor_bill_number'],
                'debit' => $bill['total_amount'],
                'credit' => 0,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($utilityExpenseAccount['id'], $bill['total_amount'], 'debit');
            
            // Entry 2: Credit Accounts Payable
            $this->transactionModel->create([
                'transaction_number' => 'VBIL-' . $billId . '-AP',
                'transaction_date' => $bill['bill_date'],
                'transaction_type' => 'bill',
                'reference_id' => $billId,
                'reference_type' => 'vendor_utility_bill',
                'account_id' => $apAccount['id'],
                'description' => 'Vendor utility bill payable - ' . $bill['vendor_bill_number'],
                'debit' => 0,
                'credit' => $bill['total_amount'],
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($apAccount['id'], $bill['total_amount'], 'credit');
        } catch (Exception $e) {
            error_log('Vendor_utility_bills postVendorBillToAccounting error: ' . $e->getMessage());
        }
    }
}

