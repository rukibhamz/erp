<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounts extends Base_Controller {
    private $accountModel;
    private $accountNumberEnabled;
    private $transactionModel;
    private $activityModel;
    private $balanceCalculator;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('accounts', 'read');
        $this->accountModel = $this->loadModel('Account_model');
        $this->accountNumberEnabled = $this->accountModel->hasAccountNumberColumn();
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->activityModel = $this->loadModel('Activity_model');
        require_once BASEPATH . 'services/Balance_calculator.php';
        $this->balanceCalculator = new Balance_calculator();
    }
    
    public function index() {
        $type = $_GET['type'] ?? null;
        $search = $_GET['search'] ?? '';
        
        try {
            if (empty($search)) {
                $accounts = $this->accountModel->getTreeWithDepth($type);
            } else {
                $accounts = $this->accountModel->getFiltered($type, $search);
            }
        } catch (Exception $e) {
            error_log('Accounts index error: ' . $e->getMessage());
            $accounts = [];
        }
        
        $data = [
            'page_title' => 'Chart of Accounts',
            'accounts' => $accounts,
            'selected_type' => $type,
            'search' => $search,
            'account_number_enabled' => $this->accountNumberEnabled,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('accounts/index', $data);
    }
    
    public function create() {
        $this->requirePermission('accounts', 'create');

        $systemCurrency = $this->getSetting('currency_code') ?: 'NGN';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $id = intval($_POST['id'] ?? 0);
            $data = [
                'account_code' => sanitize_input($_POST['account_code'] ?? ''),
                'account_name' => sanitize_input($_POST['account_name'] ?? ''),
                'account_type' => sanitize_input($_POST['account_type'] ?? 'Assets'),
                'parent_account_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
                'description' => sanitize_input($_POST['description'] ?? ''),
                'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
                'balance' => floatval($_POST['opening_balance'] ?? 0),
                'currency' => $systemCurrency, // Always use system currency
            ];
            
            if ($this->accountNumberEnabled) {
                $accountNumber = sanitize_input($_POST['account_number'] ?? '');
                $data['account_number'] = $accountNumber !== '' ? $accountNumber : null;
            }
            
            // Auto-generate account code if empty
            if (empty($data['account_code'])) {
                $data['account_code'] = $this->accountModel->getNextAccountCode($data['account_type'], $data['parent_id']);
            }
            
            try {
                if ($this->accountModel->create($data)) {
                    $this->activityModel->log($this->session['user_id'], 'create', 'Accounts', 'Created account: ' . $data['account_name']);
                    $this->setFlashMessage('success', 'Account created successfully.');
                    redirect('accounts');
                } else {
                    $this->setFlashMessage('danger', 'Failed to create account.');
                }
            } catch (Exception $e) {
                $this->setFlashMessage('danger', $e->getMessage());
            }
        }
        
        try {
            $parentAccounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $parentAccounts = [];
        }
        
        $data = [
            'page_title' => 'Create Account',
            'parent_accounts' => $parentAccounts,
            'account_number_enabled' => $this->accountNumberEnabled,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('accounts/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('accounts', 'update');
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid account ID.');
            redirect('accounts');
            return;
        }
        
        $systemCurrency = $this->getSetting('currency_code') ?: 'NGN';

        try {
            // Load complete account data with all columns
            $account = $this->accountModel->getById($id);
            if (!$account) {
                $this->setFlashMessage('danger', 'Account not found.');
                redirect('accounts');
                return;
            }
            
            // Ensure all fields are present with defaults
            $account['account_code'] = $account['account_code'] ?? '';
            $account['account_name'] = $account['account_name'] ?? '';
            $account['account_type'] = $account['account_type'] ?? 'Assets';
            $account['parent_id'] = $account['parent_account_id'] ?? null;
            $account['opening_balance'] = $account['opening_balance'] ?? 0;
            $account['balance'] = $account['balance'] ?? 0;
            $account['currency'] = $systemCurrency; // Always use system currency
            if ($this->accountNumberEnabled) {
                $account['account_number'] = $account['account_number'] ?? '';
            }
            $account['description'] = $account['description'] ?? '';
            $account['status'] = $account['status'] ?? 'active';
            
            error_log("Accounts edit: Successfully loaded account ID: {$id} with all fields");
        } catch (Exception $e) {
            error_log('Accounts edit load error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading account.');
            redirect('accounts');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $data = [
                'account_code' => sanitize_input($_POST['account_code'] ?? ''),
                'account_name' => sanitize_input($_POST['account_name'] ?? ''),
                'account_type' => sanitize_input($_POST['account_type'] ?? 'Assets'),
                'parent_account_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
                'currency' => $systemCurrency, // Always use system currency
                'description' => sanitize_input($_POST['description'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'active'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->accountNumberEnabled) {
                $accountNumber = sanitize_input($_POST['account_number'] ?? '');
                $data['account_number'] = $accountNumber !== '' ? $accountNumber : null;
            }
            
            try {
                if ($this->accountModel->update($id, $data)) {
                    $this->activityModel->log($this->session['user_id'], 'update', 'Accounts', 'Updated account: ' . $data['account_name']);
                    $this->setFlashMessage('success', 'Account updated successfully.');
                    redirect('accounts');
                } else {
                    $this->setFlashMessage('danger', 'Failed to update account.');
                }
            } catch (Exception $e) {
                $this->setFlashMessage('danger', $e->getMessage());
            }
        }
        
        try {
            $parentAccounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $parentAccounts = [];
        }
        
        $data = [
            'page_title' => 'Edit Account',
            'account' => $account,
            'parent_accounts' => $parentAccounts,
            'account_number_enabled' => $this->accountNumberEnabled,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('accounts/edit', $data);
    }
    
    public function view($id) {
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid account ID.');
            redirect('accounts');
            return;
        }
        
        try {
            $account = $this->accountModel->getById($id);
            if (!$account) {
                $this->setFlashMessage('danger', 'Account not found.');
                redirect('accounts');
                return;
            }
            
            if ($this->accountNumberEnabled) {
                $account['account_number'] = $account['account_number'] ?? '';
            }
            
            // Get account transactions
            $transactions = $this->transactionModel->getByAccount($id);
            
            // Get ledger with running balance
            $ledger = $this->transactionModel->getLedger($id);

            // Display balance from ledger math so UI is resilient to any historical
            // denormalized `accounts.balance` drift.
            if (!empty($ledger)) {
                $lastEntry = end($ledger);
                if (isset($lastEntry['running_balance'])) {
                    $account['balance'] = floatval($lastEntry['running_balance']);
                }
            } else {
                $account['balance'] = floatval($account['opening_balance'] ?? 0);
            }
        } catch (Exception $e) {
            error_log('Accounts view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading account details.');
            redirect('accounts');
            return;
        }
        
        $data = [
            'page_title' => 'Account: ' . ($account['account_name'] ?? 'N/A'),
            'account' => $account,
            'transactions' => $transactions,
            'ledger' => $ledger,
            'account_number_enabled' => $this->accountNumberEnabled,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('accounts/view', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('accounts', 'delete');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('accounts');
            return;
        }
        
        check_csrf();
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid account ID.');
            redirect('accounts');
            return;
        }
        
        try {
            $account = $this->accountModel->getById($id);
            if (!$account) {
                $this->setFlashMessage('danger', 'Account not found.');
                redirect('accounts');
                return;
            }
            
            // Check if account has transactions
            $transactions = $this->transactionModel->getByAccount($id);
            if (!empty($transactions)) {
                $this->setFlashMessage('danger', 'Cannot delete account with existing transactions. Deactivate it instead.');
                redirect('accounts');
                return;
            }
            
            if ($this->accountModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Accounts', 'Deleted account: ' . $account['account_name']);
                $this->setFlashMessage('success', 'Account deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete account.');
            }
        } catch (Exception $e) {
            error_log('Accounts delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting account.');
        }
        
        redirect('accounts');
    }

    public function reconcile($id) {
        $this->requirePermission('accounts', 'update');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('accounts/view/' . intval($id));
            return;
        }

        check_csrf();

        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid account ID.');
            redirect('accounts');
            return;
        }

        try {
            $account = $this->accountModel->getById($id);
            if (!$account) {
                $this->setFlashMessage('danger', 'Account not found.');
                redirect('accounts');
                return;
            }

            $previousBalance = floatval($account['balance'] ?? 0);
            $reconciledBalance = floatval($this->balanceCalculator->calculateBalance($id, null, false));

            $this->accountModel->update($id, [
                'balance' => $reconciledBalance,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->activityModel->log(
                $this->session['user_id'],
                'update',
                'Accounts',
                'Reconciled account balance for ' . ($account['account_name'] ?? ('ID ' . $id)) .
                ' (from ' . number_format($previousBalance, 2) . ' to ' . number_format($reconciledBalance, 2) . ')'
            );

            $this->setFlashMessage('success', 'Account balance reconciled successfully.');
            redirect('accounts/view/' . $id);
        } catch (Exception $e) {
            error_log('Accounts reconcile error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to reconcile account balance.');
            redirect('accounts/view/' . $id);
        }
    }

    public function reconcileAll() {
        $this->requirePermission('accounts', 'update');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('accounts');
            return;
        }

        check_csrf();

        try {
            $accounts = $this->accountModel->getAll();
            if (empty($accounts)) {
                $this->setFlashMessage('info', 'No accounts found to reconcile.');
                redirect('accounts');
                return;
            }

            $processedCount = 0;
            $changedCount = 0;
            $unchangedCount = 0;
            foreach ($accounts as $account) {
                $accountId = intval($account['id'] ?? 0);
                if ($accountId <= 0) {
                    continue;
                }

                $previousBalance = floatval($account['balance'] ?? 0);
                $reconciledBalance = floatval($this->balanceCalculator->calculateBalance($accountId, null, false));

                if (abs($reconciledBalance - $previousBalance) < 0.01) {
                    $unchangedCount++;
                } else {
                    $changedCount++;
                }

                $this->accountModel->update($accountId, [
                    'balance' => $reconciledBalance,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $processedCount++;
            }

            $this->activityModel->log(
                $this->session['user_id'],
                'update',
                'Accounts',
                'Reconciled all accounts - processed: ' . $processedCount .
                ', changed: ' . $changedCount .
                ', unchanged: ' . $unchangedCount
            );

            $this->setFlashMessage(
                'success',
                'Reconciled ' . $processedCount . ' account(s): ' .
                $changedCount . ' changed, ' . $unchangedCount . ' unchanged.'
            );
            redirect('accounts');
        } catch (Exception $e) {
            error_log('Accounts reconcileAll error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to reconcile all accounts.');
            redirect('accounts');
        }
    }
}

