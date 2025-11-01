<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cash extends Base_Controller {
    private $cashAccountModel;
    private $accountModel;
    private $paymentModel;
    private $transactionModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('cash', 'read');
        $this->cashAccountModel = $this->loadModel('Cash_account_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->paymentModel = $this->loadModel('Payment_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            $cashAccounts = [];
        }
        
        $data = [
            'page_title' => 'Cash Management',
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/index', $data);
    }
    
    public function accounts() {
        $this->requirePermission('cash', 'read');
        
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            $cashAccounts = [];
        }
        
        $data = [
            'page_title' => 'Cash Accounts',
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/accounts', $data);
    }
    
    public function createAccount() {
        $this->requirePermission('cash', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // First create the account
            $accountData = [
                'account_code' => sanitize_input($_POST['account_code'] ?? ''),
                'account_name' => sanitize_input($_POST['account_name'] ?? 'Cash Account: ' . ($_POST['account_name'] ?? '')),
                'account_type' => 'Assets',
                'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
                'balance' => floatval($_POST['opening_balance'] ?? 0),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'status' => 'active',
                'created_by' => $this->session['user_id']
            ];
            
            if (empty($accountData['account_code'])) {
                $accountData['account_code'] = $this->accountModel->getNextAccountCode('Assets');
            }
            
            $accountId = $this->accountModel->create($accountData);
            
            if ($accountId) {
                // Create cash account
                $cashAccountData = [
                    'account_name' => sanitize_input($_POST['account_name'] ?? ''),
                    'account_type' => sanitize_input($_POST['account_type'] ?? 'bank_account'),
                    'account_id' => $accountId,
                    'bank_name' => sanitize_input($_POST['bank_name'] ?? ''),
                    'account_number' => sanitize_input($_POST['account_number'] ?? ''),
                    'routing_number' => sanitize_input($_POST['routing_number'] ?? ''),
                    'swift_code' => sanitize_input($_POST['swift_code'] ?? ''),
                    'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
                    'current_balance' => floatval($_POST['opening_balance'] ?? 0),
                    'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                    'status' => 'active'
                ];
                
                if ($this->cashAccountModel->create($cashAccountData)) {
                    $this->activityModel->log($this->session['user_id'], 'create', 'Cash', 'Created cash account: ' . $cashAccountData['account_name']);
                    $this->setFlashMessage('success', 'Cash account created successfully.');
                    redirect('cash/accounts');
                } else {
                    $this->accountModel->delete($accountId);
                    $this->setFlashMessage('danger', 'Failed to create cash account.');
                }
            } else {
                $this->setFlashMessage('danger', 'Failed to create account.');
            }
        }
        
        $data = [
            'page_title' => 'Create Cash Account',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/create_account', $data);
    }
    
    public function receipts() {
        $this->requirePermission('cash', 'create');
        
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
        } catch (Exception $e) {
            $cashAccounts = [];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $description = sanitize_input($_POST['description'] ?? '');
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
            
            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('cash/receipts');
            }
            
            // Create payment record
            $paymentData = [
                'payment_number' => $this->paymentModel->getNextPaymentNumber('receipt'),
                'payment_date' => $paymentDate,
                'payment_type' => 'receipt',
                'account_id' => $cashAccount['account_id'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $description,
                'status' => 'posted',
                'created_by' => $this->session['user_id']
            ];
            
            $paymentId = $this->paymentModel->create($paymentData);
            
            if ($paymentId) {
                // Update cash account balance
                $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'deposit');
                
                // Create transaction
                $this->transactionModel->create([
                    'transaction_number' => $paymentData['payment_number'] . '-TXN',
                    'transaction_date' => $paymentDate,
                    'transaction_type' => 'receipt',
                    'reference_id' => $paymentId,
                    'reference_type' => 'payment',
                    'account_id' => $cashAccount['account_id'],
                    'description' => $description ?: 'Cash Receipt',
                    'debit' => $amount,
                    'credit' => 0,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ]);
                
                // Update account balance
                $this->accountModel->updateBalance($cashAccount['account_id'], $amount, 'debit');
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Cash', 'Recorded cash receipt: ' . format_currency($amount));
                $this->setFlashMessage('success', 'Cash receipt recorded successfully.');
                redirect('cash/receipts');
            } else {
                $this->setFlashMessage('danger', 'Failed to record cash receipt.');
            }
        }
        
        $data = [
            'page_title' => 'Cash Receipts',
            'cash_accounts' => $cashAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/receipts', $data);
    }
    
    public function payments() {
        $this->requirePermission('cash', 'create');
        
        try {
            $cashAccounts = $this->cashAccountModel->getActive();
            $expenseAccounts = $this->accountModel->getByType('Expenses');
        } catch (Exception $e) {
            $cashAccounts = [];
            $expenseAccounts = [];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cashAccountId = intval($_POST['cash_account_id'] ?? 0);
            $amount = floatval($_POST['amount'] ?? 0);
            $paymentDate = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
            $description = sanitize_input($_POST['description'] ?? '');
            $paymentMethod = sanitize_input($_POST['payment_method'] ?? 'cash');
            $accountId = intval($_POST['account_id'] ?? 0);
            
            $cashAccount = $this->cashAccountModel->getById($cashAccountId);
            if (!$cashAccount) {
                $this->setFlashMessage('danger', 'Cash account not found.');
                redirect('cash/payments');
            }
            
            // Create payment record
            $paymentData = [
                'payment_number' => $this->paymentModel->getNextPaymentNumber('payment'),
                'payment_date' => $paymentDate,
                'payment_type' => 'payment',
                'account_id' => $cashAccount['account_id'],
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $description,
                'status' => 'posted',
                'created_by' => $this->session['user_id']
            ];
            
            $paymentId = $this->paymentModel->create($paymentData);
            
            if ($paymentId) {
                // Update cash account balance
                $this->cashAccountModel->updateBalance($cashAccountId, $amount, 'withdrawal');
                
                // Create transactions
                // Debit the expense/payable account
                if ($accountId) {
                    $this->transactionModel->create([
                        'transaction_number' => $paymentData['payment_number'] . '-DEBIT',
                        'transaction_date' => $paymentDate,
                        'transaction_type' => 'payment',
                        'reference_id' => $paymentId,
                        'reference_type' => 'payment',
                        'account_id' => $accountId,
                        'description' => $description ?: 'Cash Payment',
                        'debit' => $amount,
                        'credit' => 0,
                        'status' => 'posted',
                        'created_by' => $this->session['user_id']
                    ]);
                    
                    $this->accountModel->updateBalance($accountId, $amount, 'debit');
                }
                
                // Credit the cash account
                $this->transactionModel->create([
                    'transaction_number' => $paymentData['payment_number'] . '-CREDIT',
                    'transaction_date' => $paymentDate,
                    'transaction_type' => 'payment',
                    'reference_id' => $paymentId,
                    'reference_type' => 'payment',
                    'account_id' => $cashAccount['account_id'],
                    'description' => $description ?: 'Cash Payment',
                    'debit' => 0,
                    'credit' => $amount,
                    'status' => 'posted',
                    'created_by' => $this->session['user_id']
                ]);
                
                $this->accountModel->updateBalance($cashAccount['account_id'], $amount, 'credit');
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Cash', 'Recorded cash payment: ' . format_currency($amount));
                $this->setFlashMessage('success', 'Cash payment recorded successfully.');
                redirect('cash/payments');
            } else {
                $this->setFlashMessage('danger', 'Failed to record cash payment.');
            }
        }
        
        $data = [
            'page_title' => 'Cash Payments',
            'cash_accounts' => $cashAccounts,
            'expense_accounts' => $expenseAccounts ?? [],
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('cash/payments', $data);
    }
}

