<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Base_Controller {
    private $transactionModel;
    private $accountModel;
    private $invoiceModel;
    private $billModel;
    private $balanceCalculator;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('reports', 'read');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->billModel = $this->loadModel('Bill_model');
        
        // Load Balance Calculator
        require_once BASEPATH . 'services/Balance_calculator.php';
        $this->balanceCalculator = new Balance_calculator();
        
        // Load export helper
        $this->load->helper('export');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Financial Reports',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('reports/index', $data);
    }
    
    /**
     * Profit & Loss Statement
     */
    public function profitLoss() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $format = $_GET['format'] ?? 'html';
        
        // Get revenue accounts (4000-4999)
        $revenueAccounts = $this->accountModel->getByType('Revenue');
        $revenue = [];
        $totalRevenue = 0;
        
        foreach ($revenueAccounts as $account) {
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(credit - debit), 0) as balance
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? 
                 AND je.entry_date BETWEEN ? AND ?
                 AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            
            if ($balance['balance'] > 0) {
                $revenue[] = [
                    'account' => $account['account_name'],
                    'amount' => $balance['balance']
                ];
                $totalRevenue += $balance['balance'];
            }
        }
        
        // Get COGS accounts (5000-5999)
        $cogsAccounts = $this->accountModel->getByType('Expenses');
        $cogs = [];
        $totalCOGS = 0;
        
        foreach ($cogsAccounts as $account) {
            // Only include accounts starting with 5 (COGS range)
            $code = $account['account_code'] ?? '';
            if (substr($code, 0, 1) !== '5') continue;
            
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(debit - credit), 0) as balance
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? 
                 AND je.entry_date BETWEEN ? AND ?
                 AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            
            if ($balance && ($balance['balance'] ?? 0) > 0) {
                $cogs[] = [
                    'account' => $account['account_name'],
                    'amount' => $balance['balance']
                ];
                $totalCOGS += $balance['balance'];
            }
        }
        
        $grossProfit = $totalRevenue - $totalCOGS;
        
        // Get expense accounts (6000-9999)
        $expenseAccounts = $this->accountModel->getByType('Expenses');
        $expenses = [];
        $totalExpenses = 0;
        
        foreach ($expenseAccounts as $account) {
            // Skip COGS accounts
            if (substr($account['account_code'], 0, 1) == '5') continue;
            
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(debit - credit), 0) as balance
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? 
                 AND je.entry_date BETWEEN ? AND ?
                 AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            
            if ($balance['balance'] > 0) {
                $expenses[] = [
                    'account' => $account['account_name'],
                    'amount' => $balance['balance']
                ];
                $totalExpenses += $balance['balance'];
            }
        }
        
        $netIncome = $grossProfit - $totalExpenses;
        
        $data = [
            'page_title' => 'Profit & Loss Statement',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'revenue' => $revenue,
            'total_revenue' => $totalRevenue,
            'cogs' => $cogs,
            'total_cogs' => $totalCOGS,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'flash' => $this->getFlashMessage()
        ];
        
        // Handle export formats
        if ($format == 'pdf') {
            $this->exportProfitLossPDF($data);
        } elseif ($format == 'excel') {
            $this->exportProfitLossExcel($data);
        } else {
            $this->loadView('reports/profit_loss', $data);
        }
    }
    
    /**
     * Balance Sheet
     */
    public function balanceSheet() {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $format = $_GET['format'] ?? 'html';
        
        // Assets
        $assetAccounts = $this->accountModel->getByType('Assets');
        $assets = [];
        $totalAssets = 0;
        
        foreach ($assetAccounts as $account) {
            $balance = $this->balanceCalculator->calculateBalance($account['id'], $asOfDate);
            if ($balance != 0) {
                $assets[] = [
                    'account' => $account['account_name'],
                    'amount' => $balance
                ];
                $totalAssets += $balance;
            }
        }
        
        // Liabilities
        $liabilityAccounts = $this->accountModel->getByType('Liabilities');
        $liabilities = [];
        $totalLiabilities = 0;
        
        foreach ($liabilityAccounts as $account) {
            $balance = $this->balanceCalculator->calculateBalance($account['id'], $asOfDate);
            if ($balance != 0) {
                $liabilities[] = [
                    'account' => $account['account_name'],
                    'amount' => $balance
                ];
                $totalLiabilities += $balance;
            }
        }
        
        // Equity
        $equityAccounts = $this->accountModel->getByType('Equity');
        $equity = [];
        $totalEquity = 0;
        
        foreach ($equityAccounts as $account) {
            $balance = $this->balanceCalculator->calculateBalance($account['id'], $asOfDate);
            if ($balance != 0) {
                $equity[] = [
                    'account' => $account['account_name'],
                    'amount' => $balance
                ];
                $totalEquity += $balance;
            }
        }
        
        // Calculate retained earnings (Net Income)
        $retainedEarnings = $totalAssets - $totalLiabilities - $totalEquity;
        $equity[] = [
            'account' => 'Retained Earnings',
            'amount' => $retainedEarnings
        ];
        $totalEquity += $retainedEarnings;
        
        $data = [
            'page_title' => 'Balance Sheet',
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'total_equity' => $totalEquity,
            'flash' => $this->getFlashMessage()
        ];
        
        // Handle export formats
        if ($format == 'pdf') {
            $this->exportBalanceSheetPDF($data);
        } elseif ($format == 'excel') {
            $this->exportBalanceSheetExcel($data);
        } else {
            $this->loadView('reports/balance_sheet', $data);
        }
    }
    
    /**
     * Trial Balance
     */
    public function trialBalance() {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $format = $_GET['format'] ?? 'html';
        
        $accounts = $this->accountModel->getAll();
        $balances = [];
        $totalDebits = 0;
        $totalCredits = 0;
        
        foreach ($accounts as $account) {
            $balance = $this->balanceCalculator->calculateBalance($account['id'], $asOfDate);
            
            if ($balance != 0) {
                $debit = 0;
                $credit = 0;
                
                // Determine if debit or credit based on account type
                $accountType = $account['account_type'];
                if (in_array($accountType, ['Assets', 'Expenses'])) {
                    $debit = abs($balance);
                } else {
                    $credit = abs($balance);
                }
                
                $balances[] = [
                    'code' => $account['account_code'],
                    'account' => $account['account_name'],
                    'debit' => $debit,
                    'credit' => $credit
                ];
                
                $totalDebits += $debit;
                $totalCredits += $credit;
            }
        }
        
        $data = [
            'page_title' => 'Trial Balance',
            'as_of_date' => $asOfDate,
            'balances' => $balances,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'in_balance' => abs($totalDebits - $totalCredits) < 0.01,
            'flash' => $this->getFlashMessage()
        ];
        
        // Handle export formats
        if ($format == 'pdf') {
            $this->exportTrialBalancePDF($data);
        } elseif ($format == 'excel') {
            $this->exportTrialBalanceExcel($data);
        } else {
            $this->loadView('reports/trial_balance', $data);
        }
    }
    
    /**
     * General Ledger
     */
    public function generalLedger() {
        $accountId = $_GET['account_id'] ?? null;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $format = $_GET['format'] ?? 'html';
        
        $accounts = $this->accountModel->getAll();
        $transactions = [];
        $selectedAccount = null;
        
        if ($accountId) {
            $selectedAccount = $this->accountModel->getById($accountId);
            if ($selectedAccount) {
                $openingBalance = $this->balanceCalculator->calculateBalance(
                    $accountId, 
                    date('Y-m-d', strtotime($startDate . ' -1 day'))
                );
                
                $transactions = $this->db->fetchAll(
                    "SELECT je.entry_date, je.description, je.reference_type, je.reference_id,
                            jel.debit, jel.credit, jel.description as line_description
                     FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                     JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                     WHERE jel.account_id = ? 
                     AND je.entry_date BETWEEN ? AND ?
                     AND je.status = 'posted'
                     ORDER BY je.entry_date, je.id",
                    [$accountId, $startDate, $endDate]
                );
            }
        }
        
        $data = [
            'page_title' => 'General Ledger',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'accounts' => $accounts,
            'selected_account_id' => $accountId,
            'selected_account' => $selectedAccount,
            'opening_balance' => $openingBalance ?? 0,
            'transactions' => $transactions,
            'flash' => $this->getFlashMessage()
        ];
        
        if ($format == 'pdf') {
            $this->exportGeneralLedgerPDF($data);
        } elseif ($format == 'excel') {
            $this->exportGeneralLedgerExcel($data);
        } else {
            $this->loadView('reports/general_ledger', $data);
        }
    }
    
    private function exportGeneralLedgerPDF($data) {
        $html = '<h1>General Ledger</h1>';
        $html .= '<p class="subtitle">Account: ' . htmlspecialchars($data['selected_account']['account_name'] ?? 'All Accounts') . '</p>';
        $html .= '<p class="subtitle">Period: ' . $data['start_date'] . ' to ' . $data['end_date'] . '</p>';
        
        $html .= '<p><strong>Opening Balance:</strong> ₦' . number_format($data['opening_balance'], 2) . '</p>';
        
        $html .= '<table>';
        $html .= '<tr><th>Date</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th></tr>';
        
        $runningBalance = $data['opening_balance'];
        foreach ($data['transactions'] as $txn) {
            $runningBalance += $txn['debit'] - $txn['credit'];
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($txn['entry_date']) . '</td>';
            $html .= '<td>' . htmlspecialchars($txn['description'] . ($txn['line_description'] ? ' - ' . $txn['line_description'] : '')) . '</td>';
            $html .= '<td class="text-right">' . ($txn['debit'] > 0 ? '₦' . number_format($txn['debit'], 2) : '-') . '</td>';
            $html .= '<td class="text-right">' . ($txn['credit'] > 0 ? '₦' . number_format($txn['credit'], 2) : '-') . '</td>';
            $html .= '<td class="text-right">₦' . number_format($runningBalance, 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '<p><strong>Closing Balance:</strong> ₦' . number_format($runningBalance, 2) . '</p>';
        
        exportToPDF(wrapPdfHtml('General Ledger', $html), 'general_ledger_' . date('Y-m-d') . '.pdf');
    }
    
    private function exportGeneralLedgerExcel($data) {
        $csvData = [];
        $csvData[] = ['General Ledger'];
        $csvData[] = ['Account:', $data['selected_account']['account_name'] ?? ''];
        $csvData[] = ['Period:', $data['start_date'] . ' to ' . $data['end_date']];
        $csvData[] = [];
        $csvData[] = ['Opening Balance:', $data['opening_balance']];
        $csvData[] = [];
        $csvData[] = ['Date', 'Description', 'Debit', 'Credit', 'Balance'];
        
        $runningBalance = $data['opening_balance'];
        foreach ($data['transactions'] as $txn) {
            $runningBalance += $txn['debit'] - $txn['credit'];
            $csvData[] = [
                $txn['entry_date'],
                $txn['description'],
                $txn['debit'],
                $txn['credit'],
                $runningBalance
            ];
        }
        $csvData[] = [];
        $csvData[] = ['Closing Balance:', '', '', '', $runningBalance];
        
        exportToExcel($csvData, 'general_ledger_' . date('Y-m-d') . '.csv');
    }
    
    /**
     * Cash Flow Statement
     */
    public function cashFlow() {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        $format = $_GET['format'] ?? 'html';
        
        // Get net income from P&L
        $revenueAccounts = $this->accountModel->getByType('Revenue');
        $totalRevenue = 0;
        foreach ($revenueAccounts as $account) {
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(credit - debit), 0) as balance
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? 
                 AND je.entry_date BETWEEN ? AND ?
                 AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            $totalRevenue += $balance['balance'];
        }
        
        $expenseAccounts = $this->accountModel->getByType('Expenses');
        $totalExpenses = 0;
        foreach ($expenseAccounts as $account) {
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(debit - credit), 0) as balance
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? 
                 AND je.entry_date BETWEEN ? AND ?
                 AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            $totalExpenses += $balance['balance'];
        }
        
        $netIncome = $totalRevenue - $totalExpenses;
        
        // Get cash accounts (1000-1999)
        $cashAccounts = $this->accountModel->getByType('Assets');
        $cashAccount = null;
        foreach ($cashAccounts as $account) {
            $code = $account['account_code'] ?? '';
            if (substr($code, 0, 1) === '1') { // Assets start with 1
                $cashAccount = $account;
                break;
            }
        }
        
        $beginningCash = 0;
        $endingCash = 0;
        $operatingActivities = [];
        
        if ($cashAccount) {
            // Calculate beginning balance (before start date)
            $beginningCash = $this->balanceCalculator->calculateBalance(
                $cashAccount['id'], 
                date('Y-m-d', strtotime($startDate . ' -1 day'))
            );
            
            // Calculate ending balance
            $endingCash = $this->balanceCalculator->calculateBalance($cashAccount['id'], $endDate);
            
            // Get cash transactions for operating activities
            $transactions = $this->db->fetchAll(
                "SELECT je.entry_date, je.description, jel.debit, jel.credit
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? 
                 AND je.entry_date BETWEEN ? AND ?
                 AND je.status = 'posted'
                 ORDER BY je.entry_date",
                [$cashAccount['id'], $startDate, $endDate]
            );
            
            foreach ($transactions as $txn) {
                $operatingActivities[] = [
                    'date' => $txn['entry_date'],
                    'description' => $txn['description'],
                    'amount' => $txn['debit'] - $txn['credit']
                ];
            }
        }
        
        $netCashFlow = $endingCash - $beginningCash;
        
        $data = [
            'page_title' => 'Cash Flow Statement',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'net_income' => $netIncome,
            'beginning_cash' => $beginningCash,
            'ending_cash' => $endingCash,
            'net_cash_flow' => $netCashFlow,
            'operating_activities' => $operatingActivities,
            'investing' => [],
            'financing' => [],
            'flash' => $this->getFlashMessage()
        ];
        
        // Handle export formats
        if ($format == 'pdf') {
            $this->exportCashFlowPDF($data);
        } elseif ($format == 'excel') {
            $this->exportCashFlowExcel($data);
        } else {
            $this->loadView('reports/cash_flow', $data);
        }
    }
    
    /**
     * Statement of Changes in Equity
     */
    public function equityStatement() {
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate = $_GET['end_date'] ?? date('Y-12-31');
        $format = $_GET['format'] ?? 'html';
        
        // Opening Balances (Equity accounts before start date)
        $equityAccounts = $this->accountModel->getByType('Equity');
        $openingEquity = 0;
        foreach ($equityAccounts as $account) {
            $openingEquity += $this->balanceCalculator->calculateBalance(
                $account['id'], 
                date('Y-m-d', strtotime($startDate . ' -1 day'))
            );
        }
        
        // Net Income for the period
        $netIncomeData = $this->calculateNetIncome($startDate, $endDate);
        $periodNetIncome = $netIncomeData['net_income'];
        
        // Period Transactions (Excluding transfers between equity accounts)
        $equityChanges = [];
        $totalAdjustments = 0;
        
        foreach ($equityAccounts as $account) {
            $txns = $this->db->fetchAll(
                "SELECT je.entry_date, je.description, jel.debit, jel.credit
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? 
                 AND je.entry_date BETWEEN ? AND ?
                 AND je.status = 'posted'
                 AND je.reference_type NOT IN ('net_income_close')", // Avoid closing entries if any
                [$account['id'], $startDate, $endDate]
            );
            
            foreach ($txns as $txn) {
                $amount = $txn['credit'] - $txn['debit'];
                if ($amount != 0) {
                    $equityChanges[] = [
                        'date' => $txn['entry_date'],
                        'description' => $txn['description'],
                        'amount' => $amount
                    ];
                    $totalAdjustments += $amount;
                }
            }
        }
        
        $closingEquity = $openingEquity + $periodNetIncome + $totalAdjustments;
        
        $data = [
            'page_title' => 'Statement of Changes in Equity',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingEquity,
            'net_income' => $periodNetIncome,
            'adjustments' => $equityChanges,
            'total_adjustments' => $totalAdjustments,
            'closing_balance' => $closingEquity,
            'flash' => $this->getFlashMessage()
        ];
        
        if ($format == 'pdf') {
            $this->exportEquityPDF($data);
        } else {
            $this->loadView('reports/equity_statement', $data);
        }
    }
    
    /**
     * Helper to calculate net income for a period
     */
    private function calculateNetIncome($startDate, $endDate) {
        $revenueTotal = $this->db->fetchOne(
            "SELECT COALESCE(SUM(credit - debit), 0) as total
             FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
             JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
             JOIN `" . $this->db->getPrefix() . "accounts` a ON jel.account_id = a.id
             WHERE a.account_type = 'Revenue' 
             AND je.entry_date BETWEEN ? AND ?
             AND je.status = 'posted'",
            [$startDate, $endDate]
        );
        
        $expenseTotal = $this->db->fetchOne(
            "SELECT COALESCE(SUM(debit - credit), 0) as total
             FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
             JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
             JOIN `" . $this->db->getPrefix() . "accounts` a ON jel.account_id = a.id
             WHERE a.account_type = 'Expenses' 
             AND je.entry_date BETWEEN ? AND ?
             AND je.status = 'posted'",
            [$startDate, $endDate]
        );
        
        return [
            'revenue' => floatval($revenueTotal['total'] ?? 0),
            'expenses' => floatval($expenseTotal['total'] ?? 0),
            'net_income' => floatval($revenueTotal['total'] ?? 0) - floatval($expenseTotal['total'] ?? 0)
        ];
    }
    
    private function exportEquityPDF($data) {
        $html = '<h1>Statement of Changes in Equity</h1>';
        $html .= '<p class="subtitle">Period: ' . $data['start_date'] . ' to ' . $data['end_date'] . '</p>';
        
        $html .= '<table>';
        $html .= '<tr><td>Opening Balance</td><td class="text-right">₦' . number_format($data['opening_balance'], 2) . '</td></tr>';
        $html .= '<tr><td>Net Income for the Period</td><td class="text-right">₦' . number_format($data['net_income'], 2) . '</td></tr>';
        
        if (!empty($data['adjustments'])) {
            $html .= '<tr><td colspan="2"><strong>Other Adjustments</strong></td></tr>';
            foreach ($data['adjustments'] as $adj) {
                $html .= '<tr><td>' . htmlspecialchars($adj['description']) . '</td><td class="text-right">₦' . number_format($adj['amount'], 2) . '</td></tr>';
            }
        }
        
        $html .= '<tr class="total-row"><td><strong>Closing Balance</strong></td><td class="text-right"><strong>₦' . number_format($data['closing_balance'], 2) . '</strong></td></tr>';
        $html .= '</table>';
        
        exportToPDF(wrapPdfHtml('Statement of Changes in Equity', $html), 'equity_statement_' . date('Y-m-d') . '.pdf');
    }
    
    // ==================== PDF Export Methods ====================
    
    private function exportProfitLossPDF($data) {
        $html = '<h1>Profit & Loss Statement</h1>';
        $html .= '<p class="subtitle">Period: ' . $data['start_date'] . ' to ' . $data['end_date'] . '</p>';
        
        $html .= '<h2>Revenue</h2><table>';
        foreach ($data['revenue'] as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['account']) . '</td><td class="text-right">₦' . number_format($item['amount'], 2) . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Revenue</td><td class="text-right">₦' . number_format($data['total_revenue'], 2) . '</td></tr>';
        $html .= '</table>';
        
        if (!empty($data['cogs'])) {
            $html .= '<h2>Cost of Goods Sold</h2><table>';
            foreach ($data['cogs'] as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['account']) . '</td><td class="text-right">₦' . number_format($item['amount'], 2) . '</td></tr>';
            }
            $html .= '<tr class="total-row"><td>Total COGS</td><td class="text-right">₦' . number_format($data['total_cogs'], 2) . '</td></tr>';
            $html .= '</table>';
            
            $html .= '<table><tr class="total-row"><td><strong>Gross Profit</strong></td><td class="text-right"><strong>₦' . number_format($data['gross_profit'], 2) . '</strong></td></tr></table>';
        }
        
        $html .= '<h2>Expenses</h2><table>';
        foreach ($data['expenses'] as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['account']) . '</td><td class="text-right">₦' . number_format($item['amount'], 2) . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Expenses</td><td class="text-right">₦' . number_format($data['total_expenses'], 2) . '</td></tr>';
        $html .= '</table>';
        
        $html .= '<table><tr class="total-row"><td><strong>Net Income</strong></td><td class="text-right"><strong>₦' . number_format($data['net_income'], 2) . '</strong></td></tr></table>';
        
        exportToPDF(wrapPdfHtml('Profit & Loss Statement', $html), 'profit_loss_' . date('Y-m-d') . '.pdf');
    }
    
    private function exportBalanceSheetPDF($data) {
        $html = '<h1>Balance Sheet</h1>';
        $html .= '<p class="subtitle">As of: ' . $data['as_of_date'] . '</p>';
        
        $html .= '<h2>Assets</h2><table>';
        foreach ($data['assets'] as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['account']) . '</td><td class="text-right">₦' . number_format($item['amount'], 2) . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Assets</td><td class="text-right">₦' . number_format($data['total_assets'], 2) . '</td></tr>';
        $html .= '</table>';
        
        $html .= '<h2>Liabilities</h2><table>';
        foreach ($data['liabilities'] as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['account']) . '</td><td class="text-right">₦' . number_format($item['amount'], 2) . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Liabilities</td><td class="text-right">₦' . number_format($data['total_liabilities'], 2) . '</td></tr>';
        $html .= '</table>';
        
        $html .= '<h2>Equity</h2><table>';
        foreach ($data['equity'] as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['account']) . '</td><td class="text-right">₦' . number_format($item['amount'], 2) . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Equity</td><td class="text-right">₦' . number_format($data['total_equity'], 2) . '</td></tr>';
        $html .= '</table>';
        
        exportToPDF(wrapPdfHtml('Balance Sheet', $html), 'balance_sheet_' . date('Y-m-d') . '.pdf');
    }
    
    private function exportTrialBalancePDF($data) {
        $html = '<h1>Trial Balance</h1>';
        $html .= '<p class="subtitle">As of: ' . $data['as_of_date'] . '</p>';
        
        $html .= '<table>';
        $html .= '<tr><th>Account Code</th><th>Account Name</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr>';
        
        foreach ($data['balances'] as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['code']) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['account']) . '</td>';
            $html .= '<td class="text-right">' . ($item['debit'] > 0 ? '₦' . number_format($item['debit'], 2) : '-') . '</td>';
            $html .= '<td class="text-right">' . ($item['credit'] > 0 ? '₦' . number_format($item['credit'], 2) : '-') . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr class="total-row">';
        $html .= '<td colspan="2"><strong>Total</strong></td>';
        $html .= '<td class="text-right"><strong>₦' . number_format($data['total_debits'], 2) . '</strong></td>';
        $html .= '<td class="text-right"><strong>₦' . number_format($data['total_credits'], 2) . '</strong></td>';
        $html .= '</tr>';
        $html .= '</table>';
        
        if ($data['in_balance']) {
            $html .= '<p style="color: green; font-weight: bold; margin-top: 20px;">✓ Trial Balance is in balance</p>';
        } else {
            $html .= '<p style="color: red; font-weight: bold; margin-top: 20px;">✗ Trial Balance is OUT of balance</p>';
        }
        
        exportToPDF(wrapPdfHtml('Trial Balance', $html), 'trial_balance_' . date('Y-m-d') . '.pdf');
    }
    
    private function exportCashFlowPDF($data) {
        $html = '<h1>Cash Flow Statement</h1>';
        $html .= '<p class="subtitle">Period: ' . $data['start_date'] . ' to ' . $data['end_date'] . '</p>';
        
        $html .= '<table>';
        $html .= '<tr><td>Net Income</td><td class="text-right">₦' . number_format($data['net_income'], 2) . '</td></tr>';
        $html .= '</table>';
        
        $html .= '<h2>Operating Activities</h2><table>';
        foreach ($data['operating_activities'] as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['description']) . '</td><td class="text-right">₦' . number_format($item['amount'], 2) . '</td></tr>';
        }
        $html .= '</table>';
        
        $html .= '<h2>Cash Summary</h2><table>';
        $html .= '<tr><td>Beginning Cash</td><td class="text-right">₦' . number_format($data['beginning_cash'], 2) . '</td></tr>';
        $html .= '<tr><td>Net Cash Flow</td><td class="text-right">₦' . number_format($data['net_cash_flow'], 2) . '</td></tr>';
        $html .= '<tr class="total-row"><td><strong>Ending Cash</strong></td><td class="text-right"><strong>₦' . number_format($data['ending_cash'], 2) . '</strong></td></tr>';
        $html .= '</table>';
        
        exportToPDF(wrapPdfHtml('Cash Flow Statement', $html), 'cash_flow_' . date('Y-m-d') . '.pdf');
    }
    
    // ==================== Excel Export Methods ====================
    
    private function exportProfitLossExcel($data) {
        $csvData = [];
        $csvData[] = ['Profit & Loss Statement'];
        $csvData[] = ['Period:', $data['start_date'] . ' to ' . $data['end_date']];
        $csvData[] = [];
        
        $csvData[] = ['Revenue'];
        foreach ($data['revenue'] as $item) {
            $csvData[] = [$item['account'], $item['amount']];
        }
        $csvData[] = ['Total Revenue', $data['total_revenue']];
        $csvData[] = [];
        
        if (!empty($data['cogs'])) {
            $csvData[] = ['Cost of Goods Sold'];
            foreach ($data['cogs'] as $item) {
                $csvData[] = [$item['account'], $item['amount']];
            }
            $csvData[] = ['Total COGS', $data['total_cogs']];
            $csvData[] = ['Gross Profit', $data['gross_profit']];
            $csvData[] = [];
        }
        
        $csvData[] = ['Expenses'];
        foreach ($data['expenses'] as $item) {
            $csvData[] = [$item['account'], $item['amount']];
        }
        $csvData[] = ['Total Expenses', $data['total_expenses']];
        $csvData[] = [];
        $csvData[] = ['Net Income', $data['net_income']];
        
        exportToExcel($csvData, 'profit_loss_' . date('Y-m-d') . '.csv');
    }
    
    private function exportBalanceSheetExcel($data) {
        $csvData = [];
        $csvData[] = ['Balance Sheet'];
        $csvData[] = ['As of:', $data['as_of_date']];
        $csvData[] = [];
        
        $csvData[] = ['Assets'];
        foreach ($data['assets'] as $item) {
            $csvData[] = [$item['account'], $item['amount']];
        }
        $csvData[] = ['Total Assets', $data['total_assets']];
        $csvData[] = [];
        
        $csvData[] = ['Liabilities'];
        foreach ($data['liabilities'] as $item) {
            $csvData[] = [$item['account'], $item['amount']];
        }
        $csvData[] = ['Total Liabilities', $data['total_liabilities']];
        $csvData[] = [];
        
        $csvData[] = ['Equity'];
        foreach ($data['equity'] as $item) {
            $csvData[] = [$item['account'], $item['amount']];
        }
        $csvData[] = ['Total Equity', $data['total_equity']];
        
        exportToExcel($csvData, 'balance_sheet_' . date('Y-m-d') . '.csv');
    }
    
    private function exportTrialBalanceExcel($data) {
        $csvData = [];
        $csvData[] = ['Trial Balance'];
        $csvData[] = ['As of:', $data['as_of_date']];
        $csvData[] = [];
        
        $csvData[] = ['Account Code', 'Account Name', 'Debit', 'Credit'];
        foreach ($data['balances'] as $item) {
            $csvData[] = [$item['code'], $item['account'], $item['debit'], $item['credit']];
        }
        $csvData[] = ['', 'Total', $data['total_debits'], $data['total_credits']];
        $csvData[] = [];
        $csvData[] = ['In Balance:', $data['in_balance'] ? 'Yes' : 'No'];
        
        exportToExcel($csvData, 'trial_balance_' . date('Y-m-d') . '.csv');
    }
    
    private function exportCashFlowExcel($data) {
        $csvData = [];
        $csvData[] = ['Cash Flow Statement'];
        $csvData[] = ['Period:', $data['start_date'] . ' to ' . $data['end_date']];
        $csvData[] = [];
        
        $csvData[] = ['Net Income', $data['net_income']];
        $csvData[] = [];
        
        $csvData[] = ['Operating Activities'];
        foreach ($data['operating_activities'] as $item) {
            $csvData[] = [$item['description'], $item['amount']];
        }
        $csvData[] = [];
        
        $csvData[] = ['Cash Summary'];
        $csvData[] = ['Beginning Cash', $data['beginning_cash']];
        $csvData[] = ['Net Cash Flow', $data['net_cash_flow']];
        $csvData[] = ['Ending Cash', $data['ending_cash']];
        
        exportToExcel($csvData, 'cash_flow_' . date('Y-m-d') . '.csv');
    }
}
