<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Base_Controller {
    private $transactionModel;
    private $accountModel;
    private $invoiceModel;
    private $billModel;
    private $balanceCalculator;
    
    private $companyModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('reports', 'read');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->invoiceModel = $this->loadModel('Invoice_model');
        $this->billModel = $this->loadModel('Bill_model');
        $this->companyModel = $this->loadModel('Company_model');
        
        // Load Balance Calculator
        require_once BASEPATH . 'services/Balance_calculator.php';
        $this->balanceCalculator = new Balance_calculator();
        
        // Load export helper
        $this->load->helper('export');
    }

    /**
     * Get the business name from the first company record
     */
    private function getBusinessName() {
        try {
            $company = $this->db->fetchOne(
                "SELECT name FROM `" . $this->db->getPrefix() . "companies` ORDER BY id ASC LIMIT 1"
            );
            return $company['name'] ?? 'Business';
        } catch (Exception $e) {
            return 'Business';
        }
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
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'total' => $balance['balance']
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
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'total' => $balance['balance']
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
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'total' => $balance['balance']
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
            // Bypass cache to ensure booking payments and recent entries are included
            $balance = $this->balanceCalculator->calculateBalance($account['id'], $asOfDate, false);
            
            if ($balance != 0) {
                $debit = 0;
                $credit = 0;
                
                // Determine if debit or credit based on account type
                $accountType = $account['account_type'];
                if (in_array(strtolower($accountType), ['assets', 'asset', 'expenses', 'expense'])) {
                    $debit = abs($balance);
                } else {
                    $credit = abs($balance);
                }
                
                $balances[] = [
                    'account_code'  => $account['account_code'],
                    'account_name'  => $account['account_name'],
                    'account_type'  => $account['account_type'],
                    'total_debit'   => $debit,
                    'total_credit'  => $credit
                ];
                
                $totalDebits += $debit;
                $totalCredits += $credit;
            }
        }
        
        $data = [
            'page_title' => 'Trial Balance',
            'as_of_date' => $asOfDate,
            'trial_balance' => $balances,
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
        $businessName = $this->getBusinessName();
        $html = '<h1>' . htmlspecialchars($businessName) . '</h1>';
        $html .= '<h2>General Ledger</h2>';
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

        $netIncome = $this->calculateNetIncomeForPeriod($startDate, $endDate);
        $cashAccounts = $this->accountModel->getCashGlAccounts();
        $cashAccountIds = array_map(function ($a) {
            return (int) $a['id'];
        }, $cashAccounts);

        $beginningCash = 0;
        $endingCash = 0;
        $dayBeforeStart = date('Y-m-d', strtotime($startDate . ' -1 day'));
        foreach ($cashAccounts as $account) {
            $beginningCash += $this->balanceCalculator->calculateBalance($account['id'], $dayBeforeStart, false);
            $endingCash += $this->balanceCalculator->calculateBalance($account['id'], $endDate, false);
        }

        $activities = $this->buildCashFlowActivities($cashAccountIds, $startDate, $endDate);
        $netCashFlow = $endingCash - $beginningCash;

        $data = [
            'page_title' => 'Cash Flow Statement',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'net_income' => $netIncome,
            'beginning_cash' => $beginningCash,
            'ending_cash' => $endingCash,
            'net_cash_flow' => $netCashFlow,
            'cash_accounts' => $cashAccounts,
            'operating' => $activities['operating'],
            'investing' => $activities['investing'],
            'financing' => $activities['financing'],
            'total_operating' => $activities['total_operating'],
            'total_investing' => $activities['total_investing'],
            'total_financing' => $activities['total_financing'],
            'flash' => $this->getFlashMessage(),
        ];

        if ($format === 'pdf') {
            $this->exportCashFlowPDF($data);
        } elseif ($format === 'excel') {
            $this->exportCashFlowExcel($data);
        } else {
            $this->loadView('reports/cash_flow', $data);
        }
    }

    /**
     * Net income for a period (aligned with P&L logic).
     */
    private function calculateNetIncomeForPeriod($startDate, $endDate) {
        $totalRevenue = 0;
        foreach ($this->accountModel->getByType('Revenue') as $account) {
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(credit - debit), 0) as balance
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? AND je.entry_date BETWEEN ? AND ? AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            $totalRevenue += floatval($balance['balance'] ?? 0);
        }

        $totalCogs = 0;
        $totalExpenses = 0;
        foreach ($this->accountModel->getByType('Expenses') as $account) {
            $code = $account['account_code'] ?? '';
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(debit - credit), 0) as balance
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? AND je.entry_date BETWEEN ? AND ? AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            $amount = floatval($balance['balance'] ?? 0);
            if (substr($code, 0, 1) === '5') {
                $totalCogs += $amount;
            } else {
                $totalExpenses += $amount;
            }
        }

        return $totalRevenue - $totalCogs - $totalExpenses;
    }

    /**
     * Classify journal counter-accounts into operating / investing / financing.
     */
    private function classifyCashFlowCategory(array $account, array $cashAccountIds) {
        if (in_array((int) ($account['id'] ?? 0), $cashAccountIds, true)) {
            return 'operating';
        }

        $type = strtolower(trim($account['account_type'] ?? ''));
        $codeNum = intval(preg_replace('/\D/', '', $account['account_code'] ?? ''));

        if (in_array($type, ['equity'], true)) {
            return 'financing';
        }
        if (in_array($type, ['liability', 'liabilities'], true)) {
            return ($codeNum >= 2500) ? 'financing' : 'operating';
        }
        if (in_array($type, ['asset', 'assets'], true)) {
            if ($codeNum >= 1500 && $codeNum < 2000) {
                return 'investing';
            }
            return 'operating';
        }
        return 'operating';
    }

    /**
     * Build cash flow activity lines from posted journal entries on cash GL accounts.
     */
    private function buildCashFlowActivities(array $cashAccountIds, $startDate, $endDate) {
        $operating = [];
        $investing = [];
        $financing = [];
        $totalOperating = 0.0;
        $totalInvesting = 0.0;
        $totalFinancing = 0.0;

        if (empty($cashAccountIds)) {
            return [
                'operating' => [],
                'investing' => [],
                'financing' => [],
                'total_operating' => 0,
                'total_investing' => 0,
                'total_financing' => 0,
            ];
        }

        $placeholders = implode(',', array_fill(0, count($cashAccountIds), '?'));
        $params = array_merge($cashAccountIds, [$startDate, $endDate]);

        $cashLines = $this->db->fetchAll(
            "SELECT je.id AS entry_id, je.entry_date, je.description, je.reference,
                    jel.id AS line_id, jel.account_id AS cash_account_id, jel.debit, jel.credit,
                    ca.account_code AS cash_code, ca.account_name AS cash_name
             FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
             JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
             JOIN `" . $this->db->getPrefix() . "accounts` ca ON jel.account_id = ca.id
             WHERE jel.account_id IN ({$placeholders})
             AND je.entry_date BETWEEN ? AND ?
             AND je.status = 'posted'
             ORDER BY je.entry_date ASC, je.id ASC, jel.id ASC",
            $params
        );

        foreach ($cashLines as $line) {
            $cashDebit = floatval($line['debit'] ?? 0);
            $cashCredit = floatval($line['credit'] ?? 0);
            $netCash = $cashDebit - $cashCredit;
            if (abs($netCash) < 0.005) {
                continue;
            }

            $counterLines = $this->db->fetchAll(
                "SELECT jel.debit, jel.credit, a.id, a.account_code, a.account_name, a.account_type
                 FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON jel.account_id = a.id
                 WHERE jel.journal_entry_id = ? AND jel.id != ?",
                [(int) $line['entry_id'], (int) $line['line_id']]
            );

            $category = 'operating';
            $counterName = '';
            $bestWeight = 0.0;
            foreach ($counterLines as $counter) {
                $weight = abs(floatval($counter['debit'] ?? 0) - floatval($counter['credit'] ?? 0));
                if ($weight >= $bestWeight) {
                    $bestWeight = $weight;
                    $counterName = trim(($counter['account_code'] ?? '') . ' ' . ($counter['account_name'] ?? ''));
                    $category = $this->classifyCashFlowCategory($counter, $cashAccountIds);
                }
            }

            if ($counterName === '' && !empty($line['description'])) {
                $counterName = $line['description'];
            }

            $row = [
                'transaction_date' => $line['entry_date'],
                'account_name' => $counterName ?: ($line['cash_name'] ?? '—'),
                'description' => $line['description'] ?? $line['reference'] ?? '—',
                'debit' => $cashCredit,
                'credit' => $cashDebit,
                'amount' => $netCash,
            ];

            if ($category === 'investing') {
                $investing[] = $row;
                $totalInvesting += $netCash;
            } elseif ($category === 'financing') {
                $financing[] = $row;
                $totalFinancing += $netCash;
            } else {
                $operating[] = $row;
                $totalOperating += $netCash;
            }
        }

        return [
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'total_operating' => $totalOperating,
            'total_investing' => $totalInvesting,
            'total_financing' => $totalFinancing,
        ];
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
             WHERE a.account_type IN ('Revenue', 'income') 
             AND je.entry_date BETWEEN ? AND ?
             AND je.status = 'posted'",
            [$startDate, $endDate]
        );
        
        $expenseTotal = $this->db->fetchOne(
            "SELECT COALESCE(SUM(debit - credit), 0) as total
             FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
             JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
             JOIN `" . $this->db->getPrefix() . "accounts` a ON jel.account_id = a.id
             WHERE a.account_type IN ('Expenses', 'expense') 
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
        $businessName = $this->getBusinessName();
        $html = '<h1>' . htmlspecialchars($businessName) . '</h1>';
        $html .= '<h2>Statement of Changes in Equity</h2>';
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
        $businessName = $this->getBusinessName();
        $html = '<h1>' . htmlspecialchars($businessName) . '</h1>';
        $html .= '<h2>Profit & Loss Statement</h2>';
        $html .= '<p class="subtitle">Period: ' . $data['start_date'] . ' to ' . $data['end_date'] . '</p>';
        
        $html .= '<h2>Revenue</h2><table>';
        foreach ($data['revenue'] as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['account_code'] . ' - ' . $item['account_name']) . '</td><td class="text-right">₦' . number_format($item['total'], 2) . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Revenue</td><td class="text-right">₦' . number_format($data['total_revenue'], 2) . '</td></tr>';
        $html .= '</table>';
        
        if (!empty($data['cogs'])) {
            $html .= '<h2>Cost of Goods Sold</h2><table>';
            foreach ($data['cogs'] as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['account_code'] . ' - ' . $item['account_name']) . '</td><td class="text-right">₦' . number_format($item['total'], 2) . '</td></tr>';
            }
            $html .= '<tr class="total-row"><td>Total COGS</td><td class="text-right">₦' . number_format($data['total_cogs'], 2) . '</td></tr>';
            $html .= '</table>';
            
            $html .= '<table><tr class="total-row"><td><strong>Gross Profit</strong></td><td class="text-right"><strong>₦' . number_format($data['gross_profit'], 2) . '</strong></td></tr></table>';
        }
        
        $html .= '<h2>Expenses</h2><table>';
        foreach ($data['expenses'] as $item) {
            $html .= '<tr><td>' . htmlspecialchars($item['account_code'] . ' - ' . $item['account_name']) . '</td><td class="text-right">₦' . number_format($item['total'], 2) . '</td></tr>';
        }
        $html .= '<tr class="total-row"><td>Total Expenses</td><td class="text-right">₦' . number_format($data['total_expenses'], 2) . '</td></tr>';
        $html .= '</table>';
        
        $html .= '<table><tr class="total-row"><td><strong>Net Income</strong></td><td class="text-right"><strong>₦' . number_format($data['net_income'], 2) . '</strong></td></tr></table>';
        
        exportToPDF(wrapPdfHtml('Profit & Loss Statement', $html), 'profit_loss_' . date('Y-m-d') . '.pdf');
    }
    
    private function exportBalanceSheetPDF($data) {
        $businessName = $this->getBusinessName();
        $html = '<h1>' . htmlspecialchars($businessName) . '</h1>';
        $html .= '<h2>Balance Sheet</h2>';
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
        $businessName = $this->getBusinessName();
        $html = '<h1>' . htmlspecialchars($businessName) . '</h1>';
        $html .= '<h2>Trial Balance</h2>';
        $html .= '<p class="subtitle">As of: ' . $data['as_of_date'] . '</p>';
        
        $html .= '<table>';
        $html .= '<tr><th>Account Code</th><th>Account Name</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr>';
        
        foreach ($data['trial_balance'] as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['account_code']) . '</td>';
            $html .= '<td>' . htmlspecialchars($item['account_name']) . '</td>';
            $html .= '<td class="text-right">' . ($item['total_debit'] > 0 ? '₦' . number_format($item['total_debit'], 2) : '-') . '</td>';
            $html .= '<td class="text-right">' . ($item['total_credit'] > 0 ? '₦' . number_format($item['total_credit'], 2) : '-') . '</td>';
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
        $businessName = $this->getBusinessName();
        $html = '<h1>' . htmlspecialchars($businessName) . '</h1>';
        $html .= '<h2>Cash Flow Statement</h2>';
        $html .= '<p class="subtitle">Period: ' . htmlspecialchars($data['start_date']) . ' to ' . htmlspecialchars($data['end_date']) . '</p>';

        $html .= '<table><tr><td>Beginning Cash</td><td class="text-right">₦' . number_format($data['beginning_cash'], 2) . '</td></tr>';
        $html .= '<tr><td>Net Income</td><td class="text-right">₦' . number_format($data['net_income'], 2) . '</td></tr></table>';

        foreach ([
            'Operating Activities' => ['rows' => $data['operating'] ?? [], 'total' => $data['total_operating'] ?? 0],
            'Investing Activities' => ['rows' => $data['investing'] ?? [], 'total' => $data['total_investing'] ?? 0],
            'Financing Activities' => ['rows' => $data['financing'] ?? [], 'total' => $data['total_financing'] ?? 0],
        ] as $title => $section) {
            $html .= '<h2>' . htmlspecialchars($title) . '</h2><table>';
            foreach ($section['rows'] as $item) {
                $html .= '<tr><td>' . htmlspecialchars($item['transaction_date'] . ' — ' . ($item['account_name'] ?? '') . ' — ' . ($item['description'] ?? '')) . '</td>';
                $html .= '<td class="text-right">₦' . number_format($item['amount'] ?? 0, 2) . '</td></tr>';
            }
            $html .= '<tr class="total-row"><td><strong>Net cash from ' . htmlspecialchars($title) . '</strong></td>';
            $html .= '<td class="text-right"><strong>₦' . number_format($section['total'], 2) . '</strong></td></tr></table>';
        }

        $html .= '<h2>Cash Summary</h2><table>';
        $html .= '<tr><td>Net Change in Cash</td><td class="text-right">₦' . number_format($data['net_cash_flow'], 2) . '</td></tr>';
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
            $csvData[] = [$item['account_code'], $item['account_name'], $item['total']];
        }
        $csvData[] = ['Total Revenue', '', $data['total_revenue']];
        $csvData[] = [];
        
        if (!empty($data['cogs'])) {
            $csvData[] = ['Cost of Goods Sold'];
            foreach ($data['cogs'] as $item) {
                $csvData[] = [$item['account_code'], $item['account_name'], $item['total']];
            }
            $csvData[] = ['Total COGS', '', $data['total_cogs']];
            $csvData[] = ['Gross Profit', '', $data['gross_profit']];
            $csvData[] = [];
        }
        
        $csvData[] = ['Expenses'];
        foreach ($data['expenses'] as $item) {
            $csvData[] = [$item['account_code'], $item['account_name'], $item['total']];
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
        foreach ($data['trial_balance'] as $item) {
            $csvData[] = [$item['account_code'], $item['account_name'], $item['total_debit'], $item['total_credit']];
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
        $csvData[] = ['Beginning Cash', $data['beginning_cash']];
        $csvData[] = ['Net Income', $data['net_income']];
        $csvData[] = [];

        foreach ([
            'Operating Activities' => ['rows' => $data['operating'] ?? [], 'total' => $data['total_operating'] ?? 0],
            'Investing Activities' => ['rows' => $data['investing'] ?? [], 'total' => $data['total_investing'] ?? 0],
            'Financing Activities' => ['rows' => $data['financing'] ?? [], 'total' => $data['total_financing'] ?? 0],
        ] as $title => $section) {
            $csvData[] = [$title];
            $csvData[] = ['Date', 'Account', 'Description', 'Amount'];
            foreach ($section['rows'] as $item) {
                $csvData[] = [
                    $item['transaction_date'] ?? '',
                    $item['account_name'] ?? '',
                    $item['description'] ?? '',
                    $item['amount'] ?? 0,
                ];
            }
            $csvData[] = ['Net cash from ' . $title, '', '', $section['total']];
            $csvData[] = [];
        }

        $csvData[] = ['Net Change in Cash', $data['net_cash_flow']];
        $csvData[] = ['Ending Cash', $data['ending_cash']];

        exportToExcel($csvData, 'cash_flow_' . date('Y-m-d') . '.csv');
    }
}
