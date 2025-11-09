<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accounts extends Base_Controller {
    private $accountModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('accounts', 'read');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $type = $_GET['type'] ?? null;
        $search = $_GET['search'] ?? '';
        
        try {
            if ($type) {
                $accounts = $this->accountModel->getByType($type);
            } elseif ($search) {
                $accounts = $this->accountModel->search($search);
            } else {
                $accounts = $this->accountModel->getHierarchy();
            }
            
            // Build hierarchy tree
            $tree = $this->buildAccountTree($accounts);
            
            // Get parent account names for display
            $allAccounts = $this->accountModel->getAll();
            $parentMap = [];
            foreach ($allAccounts as $acc) {
                $parentMap[$acc['id']] = $acc;
            }
        } catch (Exception $e) {
            $accounts = [];
            $tree = [];
            $parentMap = [];
        }
        
        $data = [
            'page_title' => 'Chart of Accounts',
            'accounts' => $accounts,
            'tree' => $tree,
            'parent_map' => $parentMap,
            'selected_type' => $type,
            'search' => $search,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('accounts/index', $data);
    }
    
    public function create() {
        $this->requirePermission('accounts', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'account_code' => sanitize_input($_POST['account_code'] ?? ''),
                'account_name' => sanitize_input($_POST['account_name'] ?? ''),
                'account_type' => sanitize_input($_POST['account_type'] ?? ''),
                'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
                'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
                'balance' => floatval($_POST['opening_balance'] ?? 0),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'active'),
                'created_by' => $this->session['user_id']
            ];
            
            // Only add is_default if column exists (for backward compatibility)
            if (isset($_POST['is_default'])) {
                $data['is_default'] = !empty($_POST['is_default']) ? 1 : 0;
            }
            
            // Generate account_number if not provided (leave blank to auto-generate)
            if (is_empty_or_whitespace($_POST['account_number'] ?? '')) {
                $data['account_number'] = $this->accountModel->getNextAccountCode($data['account_type'], $data['parent_id']);
            } else {
                // Validate account_number is numeric only
                $accountNumber = sanitize_input($_POST['account_number']);
                if (!preg_match('/^\d+$/', $accountNumber)) {
                    $this->setFlashMessage('danger', 'Account number must contain only numbers.');
                    redirect('accounts/create');
                }
                $data['account_number'] = $accountNumber;
            }
            
            // Auto-generate account_code if empty (leave blank to auto-generate)
            if (is_empty_or_whitespace($data['account_code'])) {
                $data['account_code'] = $data['account_number']; // Use account_number as code if code is empty
            }
            
            if ($this->accountModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Accounts', 'Created account: ' . $data['account_name']);
                $this->setFlashMessage('success', 'Account created successfully.');
                redirect('accounts');
            } else {
                $this->setFlashMessage('danger', 'Failed to create account.');
            }
        }
        
        try {
            $parentAccounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $parentAccounts = [];
        }
        
        $data = [
            'page_title' => 'Create Account',
            'accounts' => $parentAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('accounts/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('accounts', 'update');
        
        $account = $this->accountModel->getById($id);
        if (!$account) {
            $this->setFlashMessage('danger', 'Account not found.');
            redirect('accounts');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'account_code' => sanitize_input($_POST['account_code'] ?? ''),
                'account_name' => sanitize_input($_POST['account_name'] ?? ''),
                'account_type' => sanitize_input($_POST['account_type'] ?? ''),
                'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
                'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD'),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'active'),
                'is_default' => !empty($_POST['is_default']) ? 1 : 0
            ];
            
            // Update account_number if provided
            if (!empty($_POST['account_number'])) {
                $data['account_number'] = sanitize_input($_POST['account_number']);
            }
            
            // If setting as default, handle it
            if (!empty($_POST['is_default'])) {
                $this->accountModel->setDefaultAccount($id);
            }
            
            if ($this->accountModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Accounts', 'Updated account: ' . $data['account_name']);
                $this->setFlashMessage('success', 'Account updated successfully.');
                redirect('accounts');
            } else {
                $this->setFlashMessage('danger', 'Failed to update account.');
            }
        }
        
        $parentAccounts = $this->accountModel->getAll();
        $data = [
            'page_title' => 'Edit Account',
            'account' => $account,
            'parent_accounts' => $parentAccounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('accounts/edit', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('accounts', 'delete');
        
        $account = $this->accountModel->getById($id);
        if (!$account) {
            $this->setFlashMessage('danger', 'Account not found.');
            redirect('accounts');
        }
        
        // Check if account has transactions
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . "transactions` WHERE account_id = ?",
                [$id]
            );
            $hasTransactions = ($result && isset($result['count']) && $result['count'] > 0);
        } catch (Exception $e) {
            $hasTransactions = false;
        }
        
        if ($hasTransactions) {
            $this->setFlashMessage('danger', 'Cannot delete account with existing transactions.');
            redirect('accounts');
        }
        
        if ($this->accountModel->delete($id)) {
            $this->activityModel->log($this->session['user_id'], 'delete', 'Accounts', 'Deleted account: ' . $account['account_name']);
            $this->setFlashMessage('success', 'Account deleted successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to delete account.');
        }
        
        redirect('accounts');
    }
    
    private function buildAccountTree($accounts, $parentId = null) {
        $tree = [];
        foreach ($accounts as $account) {
            if ($account['parent_id'] == $parentId) {
                $account['children'] = $this->buildAccountTree($accounts, $account['id']);
                $tree[] = $account;
            }
        }
        return $tree;
    }
}

