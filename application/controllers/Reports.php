<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Base_Controller {
    private $accountModel;
    private $transactionModel;
    private $invoiceModel;
    private $billModel;
    private $customerModel;
    private $vendorModel;
    private $journalModel;
    private $financialYearModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('reports', 'read');
        $this->accountModel = $this->loadModel('Account_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->billModel = $this->loadModel('Bill_model');
        $this->customerModel = $this->loadModel('Customer_model');
        $this->vendorModel = $this->loadModel('Vendor_model');
        $this->journalModel = $this->loadModel('Journal_entry_model');
        $this->financialYearModel = $this->loadModel('Financial_year_model');
    }

    public function index() {
        $data = [
            'page_title' => 'Financial Reports',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('reports/index', $data);
    }

    public function profitLoss() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $comparison = $_GET['comparison'] ?? 'none'; // none, previous_period, previous_year
        $financialYearId = $_GET['financial_year_id'] ?? null;

        try {
            // Get revenue accounts
            $revenueAccounts = $this->accountModel->getByType('Revenue');
            $expenseAccounts = $this->accountModel->getByType('Expenses');

            // Calculate totals
            $revenue = $this->calculateAccountTotal($revenueAccounts, $startDate, $endDate);
            $expenses = $this->calculateAccountTotal($expenseAccounts, $startDate, $endDate);
            $netIncome = $revenue['total'] - $expenses['total'];

            // Comparison data
            $comparisonData = null;
            if ($comparison === 'previous_period') {
                $periodDays = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
                $prevStart = date('Y-m-d', strtotime($startDate . ' -' . $periodDays . ' days'));
                $prevEnd = date('Y-m-d', strtotime($endDate . ' -' . $periodDays . ' days'));
                $comparisonData = [
                    'revenue' => $this->calculateAccountTotal($revenueAccounts, $prevStart, $prevEnd),
                    'expenses' => $this->calculateAccountTotal($expenseAccounts, $prevStart, $prevEnd)
                ];
                $comparisonData['net_income'] = $comparisonData['revenue']['total'] - $comparisonData['expenses']['total'];
            } elseif ($comparison === 'previous_year') {
                $prevStart = date('Y-m-d', strtotime($startDate . ' -1 year'));
                $prevEnd = date('Y-m-d', strtotime($endDate . ' -1 year'));
                $comparisonData = [
                    'revenue' => $this->calculateAccountTotal($revenueAccounts, $prevStart, $prevEnd),
                    'expenses' => $this->calculateAccountTotal($expenseAccounts, $prevStart, $prevEnd)
                ];
                $comparisonData['net_income'] = $comparisonData['revenue']['total'] - $comparisonData['expenses']['total'];
            }

            $financialYears = $this->financialYearModel->getOpen();
        } catch (Exception $e) {
            $revenue = ['accounts' => [], 'total' => 0];
            $expenses = ['accounts' => [], 'total' => 0];
            $netIncome = 0;
            $comparisonData = null;
            $financialYears = [];
        }

        $data = [
            'page_title' => 'Profit & Loss Statement',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'comparison' => $comparison,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_income' => $netIncome,
            'comparison_data' => $comparisonData,
            'financial_years' => $financialYears,
            'selected_year_id' => $financialYearId,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('reports/profit_loss', $data);
    }

    public function balanceSheet() {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $comparison = $_GET['comparison'] ?? 'none';

        try {
            // Assets
            $assetAccounts = $this->accountModel->getByType('Assets');
            $assets = $this->calculateAccountTotal($assetAccounts, null, $asOfDate);

            // Liabilities
            $liabilityAccounts = $this->accountModel->getByType('Liabilities');
            $liabilities = $this->calculateAccountTotal($liabilityAccounts, null, $asOfDate);

            // Equity
            $equityAccounts = $this->accountModel->getByType('Equity');
            $equity = $this->calculateAccountTotal($equityAccounts, null, $asOfDate);

            // Calculate retained earnings (Revenue - Expenses up to date)
            $revenueAccounts = $this->accountModel->getByType('Revenue');
            $expenseAccounts = $this->accountModel->getByType('Expenses');
            $revenue = $this->calculateAccountTotal($revenueAccounts, null, $asOfDate);
            $expenses = $this->calculateAccountTotal($expenseAccounts, null, $asOfDate);
            $retainedEarnings = $revenue['total'] - $expenses['total'];
            $equity['total'] += $retainedEarnings;

            // Comparison data
            $comparisonData = null;
            if ($comparison === 'previous_period') {
                $prevDate = date('Y-m-d', strtotime($asOfDate . ' -1 month'));
                $comparisonData = [
                    'assets' => $this->calculateAccountTotal($assetAccounts, null, $prevDate),
                    'liabilities' => $this->calculateAccountTotal($liabilityAccounts, null, $prevDate),
                    'equity' => $this->calculateAccountTotal($equityAccounts, null, $prevDate)
                ];
            }

            $totalAssets = $assets['total'];
            $totalLiabilitiesEquity = $liabilities['total'] + $equity['total'];
        } catch (Exception $e) {
            $assets = ['accounts' => [], 'total' => 0];
            $liabilities = ['accounts' => [], 'total' => 0];
            $equity = ['accounts' => [], 'total' => 0];
            $retainedEarnings = 0;
            $comparisonData = null;
            $totalAssets = 0;
            $totalLiabilitiesEquity = 0;
        }

        $data = [
            'page_title' => 'Balance Sheet',
            'as_of_date' => $asOfDate,
            'comparison' => $comparison,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'retained_earnings' => $retainedEarnings,
            'total_assets' => $totalAssets,
            'total_liabilities_equity' => $totalLiabilitiesEquity,
            'comparison_data' => $comparisonData,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('reports/balance_sheet', $data);
    }

    public function cashFlow() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $method = $_GET['method'] ?? 'indirect'; // indirect or direct

        try {
            // Operating Activities (Indirect Method)
            $netIncome = 0;
            if ($method === 'indirect') {
                $revenueAccounts = $this->accountModel->getByType('Revenue');
                $expenseAccounts = $this->accountModel->getByType('Expenses');
                $revenue = $this->calculateAccountTotal($revenueAccounts, $startDate, $endDate);
                $expenses = $this->calculateAccountTotal($expenseAccounts, $startDate, $endDate);
                $netIncome = $revenue['total'] - $expenses['total'];
            }

            // Investing Activities
            $investingAccounts = $this->accountModel->getByType('Assets');
            // Filter for fixed assets, investments, etc.
            $investing = $this->calculateAccountTotal($investingAccounts, $startDate, $endDate, 'investing');

            // Financing Activities
            $financingAccounts = $this->accountModel->getByType('Liabilities');
            $equityAccounts = $this->accountModel->getByType('Equity');
            $financing = [
                'liabilities' => $this->calculateAccountTotal($financingAccounts, $startDate, $endDate),
                'equity' => $this->calculateAccountTotal($equityAccounts, $startDate, $endDate)
            ];

            $cashFlowOperating = $netIncome; // Simplified
            $cashFlowInvesting = $investing['total'];
            $cashFlowFinancing = $financing['liabilities']['total'] + $financing['equity']['total'];
            $netCashFlow = $cashFlowOperating + $cashFlowInvesting + $cashFlowFinancing;
        } catch (Exception $e) {
            $netIncome = 0;
            $investing = ['accounts' => [], 'total' => 0];
            $financing = ['liabilities' => ['accounts' => [], 'total' => 0], 'equity' => ['accounts' => [], 'total' => 0]];
            $cashFlowOperating = 0;
            $cashFlowInvesting = 0;
            $cashFlowFinancing = 0;
            $netCashFlow = 0;
        }

        $data = [
            'page_title' => 'Cash Flow Statement',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'method' => $method,
            'net_income' => $netIncome,
            'cash_flow_operating' => $cashFlowOperating,
            'cash_flow_investing' => $cashFlowInvesting,
            'cash_flow_financing' => $cashFlowFinancing,
            'net_cash_flow' => $netCashFlow,
            'investing' => $investing,
            'financing' => $financing,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('reports/cash_flow', $data);
    }

    public function trialBalance() {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $accountId = $_GET['account_id'] ?? null;

        try {
            // Use Transaction_model's getTrialBalance method
            $trialBalanceData = $this->transactionModel->getTrialBalance(null, $asOfDate);
            
            $trialBalance = [];
            $totalDebits = 0;
            $totalCredits = 0;

            foreach ($trialBalanceData as $item) {
                if ($accountId && $item['id'] != $accountId) {
                    continue;
                }

                $debit = floatval($item['total_debit'] ?? 0);
                $credit = floatval($item['total_credit'] ?? 0);

                $trialBalance[] = [
                    'account' => [
                        'id' => $item['id'],
                        'account_code' => $item['account_code'],
                        'account_name' => $item['account_name'],
                        'account_type' => $item['account_type']
                    ],
                    'debit' => $debit,
                    'credit' => $credit
                ];

                $totalDebits += $debit;
                $totalCredits += $credit;
            }
        } catch (Exception $e) {
            error_log('Reports trialBalance error: ' . $e->getMessage());
            $trialBalance = [];
            $totalDebits = 0;
            $totalCredits = 0;
        }

        try {
            $accounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $accounts = [];
        }

        $data = [
            'page_title' => 'Trial Balance',
            'as_of_date' => $asOfDate,
            'trial_balance' => $trialBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'selected_account_id' => $accountId,
            'accounts' => $accounts,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('reports/trial_balance', $data);
    }

    public function generalLedger() {
        $accountId = $_GET['account_id'] ?? null;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        if (!$accountId) {
            $this->setFlashMessage('danger', 'Please select an account.');
            redirect('reports');
        }

        try {
            $account = $this->accountModel->getById($accountId);
            if (!$account) {
                $this->setFlashMessage('danger', 'Account not found.');
                redirect('reports');
            }

            // Get transactions
            $transactions = $this->transactionModel->getByAccount($accountId, $startDate, $endDate);

            // Calculate running balance
            $runningBalance = $this->getAccountBalance($accountId, $startDate);
            foreach ($transactions as &$transaction) {
                $isDebitAccount = in_array($account['account_type'], ['Assets', 'Expenses']);
                if ($isDebitAccount) {
                    $runningBalance += ($transaction['debit'] - $transaction['credit']);
                } else {
                    $runningBalance += ($transaction['credit'] - $transaction['debit']);
                }
                $transaction['running_balance'] = $runningBalance;
            }

            $accounts = $this->accountModel->getAll();
        } catch (Exception $e) {
            $account = null;
            $transactions = [];
            $accounts = [];
        }

        $data = [
            'page_title' => 'General Ledger',
            'account' => $account,
            'transactions' => $transactions,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'accounts' => $accounts,
            'selected_account_id' => $accountId,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('reports/general_ledger', $data);
    }

    // Helper Methods
    private function calculateAccountTotal($accounts, $startDate = null, $endDate = null, $filter = null) {
        $result = ['accounts' => [], 'total' => 0];

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account['id'], $endDate, $startDate);

            // Apply filters if needed
            if ($filter === 'investing' && !in_array(strtolower($account['account_name']), ['fixed assets', 'equipment', 'investments'])) {
                continue;
            }

            if ($balance != 0) {
                $result['accounts'][] = [
                    'account' => $account,
                    'balance' => $balance
                ];
                $result['total'] += abs($balance);
            }
        }

        return $result;
    }

    private function getAccountBalance($accountId, $endDate, $startDate = null) {
        try {
            $account = $this->accountModel->getById($accountId);
            if (!$account) {
                return 0;
            }

            $sql = "SELECT 
                        COALESCE(SUM(CASE WHEN account_id = ? THEN debit - credit ELSE 0 END), 0) as balance
                    FROM `" . $this->db->getPrefix() . "transactions`
                    WHERE account_id = ? AND status = 'posted'";
            $params = [$accountId, $accountId];

            if ($startDate) {
                $sql .= " AND transaction_date >= ?";
                $params[] = $startDate;
            }

            if ($endDate) {
                $sql .= " AND transaction_date <= ?";
                $params[] = $endDate;
            }

            $result = $this->db->fetchOne($sql, $params);
            $balance = $result ? floatval($result['balance']) : 0;

            // For revenue/expense accounts, calculate credit - debit
            if (in_array($account['account_type'], ['Revenue', 'Expenses'])) {
                $sql = str_replace('debit - credit', 'credit - debit', $sql);
                $result = $this->db->fetchOne($sql, $params);
                $balance = $result ? floatval($result['balance']) : 0;
            }

            return $balance;
        } catch (Exception $e) {
            error_log('Reports getAccountBalance error: ' . $e->getMessage());
            return 0;
        }
    }
}

