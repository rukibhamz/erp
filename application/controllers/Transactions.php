<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends Base_Controller {
    private $transactionModel;
    private $accountModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('accounting', 'read');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $accountId = !empty($_GET['account_id']) ? intval($_GET['account_id']) : null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $status = $_GET['status'] ?? null;
        
        try {
            if ($accountId) {
                $transactions = $this->transactionModel->getByAccount($accountId, $startDate, $endDate);
            } else {
                $sql = "SELECT t.*, a.account_code, a.account_name 
                        FROM `" . $this->db->getPrefix() . "transactions` t
                        JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                        WHERE 1=1";
                $params = [];
                
                if ($startDate) {
                    $sql .= " AND t.transaction_date >= ?";
                    $params[] = $startDate;
                }
                if ($endDate) {
                    $sql .= " AND t.transaction_date <= ?";
                    $params[] = $endDate;
                }
                if ($status) {
                    $sql .= " AND t.status = ?";
                    $params[] = $status;
                }
                
                $sql .= " ORDER BY t.transaction_date DESC, t.id DESC LIMIT 500";
                $transactions = $this->db->fetchAll($sql, $params);
            }
        } catch (Exception $e) {
            error_log('Transactions index error: ' . $e->getMessage());
            $transactions = [];
        }
        
        try {
            $accounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $accounts = [];
        }
        
        $data = [
            'page_title' => 'Transactions',
            'transactions' => $transactions,
            'accounts' => $accounts,
            'selected_account_id' => $accountId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('transactions/index', $data);
    }
    
    public function create() {
        $this->requirePermission('accounting', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $data = [
                'transaction_date' => sanitize_input($_POST['transaction_date'] ?? date('Y-m-d')),
                'transaction_type' => sanitize_input($_POST['transaction_type'] ?? 'manual'),
                'account_id' => intval($_POST['account_id'] ?? 0),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'debit' => floatval($_POST['debit'] ?? 0),
                'credit' => floatval($_POST['credit'] ?? 0),
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'reference_type' => sanitize_input($_POST['reference_type'] ?? ''),
                'reference_id' => !empty($_POST['reference_id']) ? intval($_POST['reference_id']) : null,
                'status' => sanitize_input($_POST['status'] ?? 'draft'),
                'created_by' => $this->session['user_id']
            ];
            
            // Validate account
            if ($data['account_id'] <= 0) {
                $this->setFlashMessage('danger', 'Please select an account.');
                redirect('transactions/create');
                return;
            }
            
            // Validate debit or credit
            if ($data['debit'] <= 0 && $data['credit'] <= 0) {
                $this->setFlashMessage('danger', 'Either debit or credit amount must be greater than zero.');
                redirect('transactions/create');
                return;
            }
            
            if ($data['debit'] > 0 && $data['credit'] > 0) {
                $this->setFlashMessage('danger', 'Transaction cannot have both debit and credit amounts.');
                redirect('transactions/create');
                return;
            }
            
            // Generate transaction number
            $data['transaction_number'] = $this->generateTransactionNumber();
            
            if ($this->transactionModel->create($data)) {
                // Update account balance if posted
                if ($data['status'] === 'posted') {
                    $account = $this->accountModel->getById($data['account_id']);
                    if ($account) {
                        $this->accountModel->updateBalance($data['account_id'], $data['debit'] > 0 ? $data['debit'] : $data['credit'], $data['debit'] > 0 ? 'debit' : 'credit');
                    }
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Transactions', 'Created transaction: ' . $data['transaction_number']);
                $this->setFlashMessage('success', 'Transaction created successfully.');
                redirect('transactions');
            } else {
                $this->setFlashMessage('danger', 'Failed to create transaction.');
            }
        }
        
        try {
            $accounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $accounts = [];
        }
        
        $data = [
            'page_title' => 'Create Transaction',
            'accounts' => $accounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('transactions/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('accounting', 'update');
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid transaction ID.');
            redirect('transactions');
            return;
        }
        
        try {
            // Load complete transaction data with all columns
            $transaction = $this->transactionModel->getById($id);
            if (!$transaction) {
                $this->setFlashMessage('danger', 'Transaction not found.');
                redirect('transactions');
                return;
            }
            
            // Don't allow editing posted transactions
            if ($transaction['status'] === 'posted') {
                $this->setFlashMessage('danger', 'Cannot edit posted transactions.');
                redirect('transactions');
                return;
            }
            
            // Ensure all fields are present with defaults
            $transaction['transaction_date'] = $transaction['transaction_date'] ?? date('Y-m-d');
            $transaction['transaction_type'] = $transaction['transaction_type'] ?? 'manual';
            $transaction['account_id'] = $transaction['account_id'] ?? 0;
            $transaction['description'] = $transaction['description'] ?? '';
            $transaction['debit'] = $transaction['debit'] ?? 0;
            $transaction['credit'] = $transaction['credit'] ?? 0;
            $transaction['reference'] = $transaction['reference'] ?? '';
            $transaction['reference_type'] = $transaction['reference_type'] ?? '';
            $transaction['reference_id'] = $transaction['reference_id'] ?? null;
            $transaction['status'] = $transaction['status'] ?? 'draft';
            
            error_log("Transactions edit: Successfully loaded transaction ID: {$id} with all fields");
        } catch (Exception $e) {
            error_log('Transactions edit load error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading transaction.');
            redirect('transactions');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $data = [
                'transaction_date' => sanitize_input($_POST['transaction_date'] ?? date('Y-m-d')),
                'transaction_type' => sanitize_input($_POST['transaction_type'] ?? 'manual'),
                'account_id' => intval($_POST['account_id'] ?? 0),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'debit' => floatval($_POST['debit'] ?? 0),
                'credit' => floatval($_POST['credit'] ?? 0),
                'reference' => sanitize_input($_POST['reference'] ?? ''),
                'reference_type' => sanitize_input($_POST['reference_type'] ?? ''),
                'reference_id' => !empty($_POST['reference_id']) ? intval($_POST['reference_id']) : null,
                'status' => sanitize_input($_POST['status'] ?? 'draft'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Validate account
            if ($data['account_id'] <= 0) {
                $this->setFlashMessage('danger', 'Please select an account.');
                redirect('transactions/edit/' . $id);
                return;
            }
            
            // Validate debit or credit
            if ($data['debit'] <= 0 && $data['credit'] <= 0) {
                $this->setFlashMessage('danger', 'Either debit or credit amount must be greater than zero.');
                redirect('transactions/edit/' . $id);
                return;
            }
            
            if ($data['debit'] > 0 && $data['credit'] > 0) {
                $this->setFlashMessage('danger', 'Transaction cannot have both debit and credit amounts.');
                redirect('transactions/edit/' . $id);
                return;
            }
            
            if ($this->transactionModel->update($id, $data)) {
                // Update account balance if status changed to posted
                if ($data['status'] === 'posted' && $transaction['status'] !== 'posted') {
                    $account = $this->accountModel->getById($data['account_id']);
                    if ($account) {
                        $this->accountModel->updateBalance($data['account_id'], $data['debit'] > 0 ? $data['debit'] : $data['credit'], $data['debit'] > 0 ? 'debit' : 'credit');
                    }
                }
                
                $this->activityModel->log($this->session['user_id'], 'update', 'Transactions', 'Updated transaction: ' . $transaction['transaction_number']);
                $this->setFlashMessage('success', 'Transaction updated successfully.');
                redirect('transactions');
            } else {
                $this->setFlashMessage('danger', 'Failed to update transaction.');
            }
        }
        
        try {
            $accounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $accounts = [];
        }
        
        $data = [
            'page_title' => 'Edit Transaction',
            'transaction' => $transaction,
            'accounts' => $accounts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('transactions/edit', $data);
    }
    
    public function view($id) {
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid transaction ID.');
            redirect('transactions');
            return;
        }
        
        try {
            $transaction = $this->transactionModel->getById($id);
            if (!$transaction) {
                $this->setFlashMessage('danger', 'Transaction not found.');
                redirect('transactions');
                return;
            }
            
            // Get account details
            $account = $this->accountModel->getById($transaction['account_id']);
        } catch (Exception $e) {
            error_log('Transactions view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading transaction details.');
            redirect('transactions');
            return;
        }
        
        $data = [
            'page_title' => 'Transaction: ' . ($transaction['transaction_number'] ?? 'N/A'),
            'transaction' => $transaction,
            'account' => $account,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('transactions/view', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('accounting', 'delete');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('transactions');
            return;
        }
        
        check_csrf();
        
        $id = intval($id);
        if ($id <= 0) {
            $this->setFlashMessage('danger', 'Invalid transaction ID.');
            redirect('transactions');
            return;
        }
        
        try {
            $transaction = $this->transactionModel->getById($id);
            if (!$transaction) {
                $this->setFlashMessage('danger', 'Transaction not found.');
                redirect('transactions');
                return;
            }
            
            // Don't allow deleting posted transactions
            if ($transaction['status'] === 'posted') {
                $this->setFlashMessage('danger', 'Cannot delete posted transactions.');
                redirect('transactions');
                return;
            }
            
            if ($this->transactionModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Transactions', 'Deleted transaction: ' . $transaction['transaction_number']);
                $this->setFlashMessage('success', 'Transaction deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete transaction.');
            }
        } catch (Exception $e) {
            error_log('Transactions delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error deleting transaction.');
        }
        
        redirect('transactions');
    }
    
    private function generateTransactionNumber() {
        $year = date('Y');
        $result = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(transaction_number, -6) AS UNSIGNED)) as max_num 
             FROM `" . $this->db->getPrefix() . "transactions` 
             WHERE transaction_number LIKE 'TXN-{$year}-%'"
        );
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return 'TXN-' . $year . '-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    }
}

