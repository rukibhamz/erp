<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Base_Controller {
    private $transactionModel;
    private $accountModel;
    private $invoiceModel;
    private $billModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('reports', 'read');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->billModel = $this->loadModel('Bill_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Financial Reports',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('reports/index', $data);
    }
    
    public function trialBalance() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        try {
            $trialBalance = $this->transactionModel->getTrialBalance($startDate, $endDate);
        } catch (Exception $e) {
            error_log('Reports trialBalance error: ' . $e->getMessage());
            $trialBalance = [];
        }
        
        $data = [
            'page_title' => 'Trial Balance',
            'trial_balance' => $trialBalance,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('reports/trial_balance', $data);
    }
    
    public function generalLedger() {
        $accountId = !empty($_GET['account_id']) ? intval($_GET['account_id']) : null;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        try {
            $accounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $accounts = [];
        }
        
        $account = null;
        $ledger = [];
        
        if ($accountId) {
            try {
                $account = $this->accountModel->getById($accountId);
                if ($account) {
                    $ledger = $this->transactionModel->getLedger($accountId, $startDate, $endDate);
                }
            } catch (Exception $e) {
                error_log('Reports generalLedger error: ' . $e->getMessage());
            }
        }
        
        $data = [
            'page_title' => 'General Ledger',
            'account' => $account,
            'ledger' => $ledger,
            'accounts' => $accounts,
            'selected_account_id' => $accountId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('reports/general_ledger', $data);
    }
    
    public function profitLoss() {
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-12-31');
        
        try {
            // Get revenue
            $revenue = $this->db->fetchAll(
                "SELECT a.id, a.account_code, a.account_name, 
                        COALESCE(SUM(t.credit - t.debit), 0) as total
                 FROM `" . $this->db->getPrefix() . "accounts` a
                 LEFT JOIN `" . $this->db->getPrefix() . "transactions` t ON a.id = t.account_id 
                     AND t.status = 'posted' AND t.transaction_date >= ? AND t.transaction_date <= ?
                 WHERE a.account_type = 'Revenue' AND a.status = 'active'
                 GROUP BY a.id, a.account_code, a.account_name
                 HAVING total > 0
                 ORDER BY a.account_code",
                [$startDate, $endDate]
            );
            
            // Get expenses
            $expenses = $this->db->fetchAll(
                "SELECT a.id, a.account_code, a.account_name, 
                        COALESCE(SUM(t.debit - t.credit), 0) as total
                 FROM `" . $this->db->getPrefix() . "accounts` a
                 LEFT JOIN `" . $this->db->getPrefix() . "transactions` t ON a.id = t.account_id 
                     AND t.status = 'posted' AND t.transaction_date >= ? AND t.transaction_date <= ?
                 WHERE a.account_type = 'Expenses' AND a.status = 'active'
                 GROUP BY a.id, a.account_code, a.account_name
                 HAVING total > 0
                 ORDER BY a.account_code",
                [$startDate, $endDate]
            );
            
            $totalRevenue = array_sum(array_column($revenue, 'total'));
            $totalExpenses = array_sum(array_column($expenses, 'total'));
            $netIncome = $totalRevenue - $totalExpenses;
        } catch (Exception $e) {
            error_log('Reports profitLoss error: ' . $e->getMessage());
            $revenue = [];
            $expenses = [];
            $totalRevenue = 0;
            $totalExpenses = 0;
            $netIncome = 0;
        }
        
        $data = [
            'page_title' => 'Profit & Loss Statement',
            'revenue' => $revenue,
            'expenses' => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('reports/profit_loss', $data);
    }
    
    public function balanceSheet() {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-t');
        
        try {
            // Get assets
            $assets = $this->db->fetchAll(
                "SELECT a.id, a.account_code, a.account_name, 
                        COALESCE(a.opening_balance, 0) + COALESCE(SUM(CASE 
                            WHEN a.account_type = 'Assets' THEN t.debit - t.credit
                            ELSE 0
                        END), 0) as balance
                 FROM `" . $this->db->getPrefix() . "accounts` a
                 LEFT JOIN `" . $this->db->getPrefix() . "transactions` t ON a.id = t.account_id 
                     AND t.status = 'posted' AND t.transaction_date <= ?
                 WHERE a.account_type = 'Assets' AND a.status = 'active'
                 GROUP BY a.id, a.account_code, a.account_name, a.opening_balance
                 HAVING balance != 0
                 ORDER BY a.account_code",
                [$asOfDate]
            );
            
            // Get liabilities
            $liabilities = $this->db->fetchAll(
                "SELECT a.id, a.account_code, a.account_name, 
                        COALESCE(a.opening_balance, 0) + COALESCE(SUM(CASE 
                            WHEN a.account_type = 'Liabilities' THEN t.credit - t.debit
                            ELSE 0
                        END), 0) as balance
                 FROM `" . $this->db->getPrefix() . "accounts` a
                 LEFT JOIN `" . $this->db->getPrefix() . "transactions` t ON a.id = t.account_id 
                     AND t.status = 'posted' AND t.transaction_date <= ?
                 WHERE a.account_type = 'Liabilities' AND a.status = 'active'
                 GROUP BY a.id, a.account_code, a.account_name, a.opening_balance
                 HAVING balance != 0
                 ORDER BY a.account_code",
                [$asOfDate]
            );
            
            // Get equity
            $equity = $this->db->fetchAll(
                "SELECT a.id, a.account_code, a.account_name, 
                        COALESCE(a.opening_balance, 0) + COALESCE(SUM(CASE 
                            WHEN a.account_type = 'Equity' THEN t.credit - t.debit
                            ELSE 0
                        END), 0) as balance
                 FROM `" . $this->db->getPrefix() . "accounts` a
                 LEFT JOIN `" . $this->db->getPrefix() . "transactions` t ON a.id = t.account_id 
                     AND t.status = 'posted' AND t.transaction_date <= ?
                 WHERE a.account_type = 'Equity' AND a.status = 'active'
                 GROUP BY a.id, a.account_code, a.account_name, a.opening_balance
                 HAVING balance != 0
                 ORDER BY a.account_code",
                [$asOfDate]
            );
            
            $totalAssets = array_sum(array_column($assets, 'balance'));
            $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
            $totalEquity = array_sum(array_column($equity, 'balance'));
        } catch (Exception $e) {
            error_log('Reports balanceSheet error: ' . $e->getMessage());
            $assets = [];
            $liabilities = [];
            $equity = [];
            $totalAssets = 0;
            $totalLiabilities = 0;
            $totalEquity = 0;
        }
        
        $data = [
            'page_title' => 'Balance Sheet',
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'as_of_date' => $asOfDate,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('reports/balance_sheet', $data);
    }
    
    public function cashFlow() {
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-12-31');
        
        try {
            // Operating activities (cash accounts transactions)
            $operating = $this->db->fetchAll(
                "SELECT t.*, a.account_name
                 FROM `" . $this->db->getPrefix() . "transactions` t
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                 JOIN `" . $this->db->getPrefix() . "cash_accounts` ca ON ca.account_id = a.id
                 WHERE t.status = 'posted' AND t.transaction_date >= ? AND t.transaction_date <= ?
                 ORDER BY t.transaction_date",
                [$startDate, $endDate]
            );
            
            // Investing activities
            $investing = $this->db->fetchAll(
                "SELECT t.*, a.account_name
                 FROM `" . $this->db->getPrefix() . "transactions` t
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                 WHERE a.account_type = 'Assets' AND t.reference_type = 'asset'
                 AND t.status = 'posted' AND t.transaction_date >= ? AND t.transaction_date <= ?
                 ORDER BY t.transaction_date",
                [$startDate, $endDate]
            );
            
            // Financing activities
            $financing = $this->db->fetchAll(
                "SELECT t.*, a.account_name
                 FROM `" . $this->db->getPrefix() . "transactions` t
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON t.account_id = a.id
                 WHERE a.account_type IN ('Liabilities', 'Equity')
                 AND t.status = 'posted' AND t.transaction_date >= ? AND t.transaction_date <= ?
                 ORDER BY t.transaction_date",
                [$startDate, $endDate]
            );
        } catch (Exception $e) {
            error_log('Reports cashFlow error: ' . $e->getMessage());
            $operating = [];
            $investing = [];
            $financing = [];
        }
        
        $data = [
            'page_title' => 'Cash Flow Statement',
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('reports/cash_flow', $data);
    }
}

