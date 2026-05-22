<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared P&L, cash, and ledger helpers used by Reports, Accounting dashboard, and year-end close.
 */
class Financial_reporting_service {
    private $db;
    private $accountModel;

    public function __construct() {
        $this->db = Database::getInstance();
        require_once BASEPATH . 'models/Account_model.php';
        $this->accountModel = new Account_model();
    }

    public function accountCodeNumber(array $account): int {
        return intval(preg_replace('/\D/', '', $account['account_code'] ?? ''));
    }

    public function isProfitLossRevenueAccount(array $account): bool {
        $code = $this->accountCodeNumber($account);
        return $code >= 4000 && $code <= 4999;
    }

    public function isProfitLossExpenseAccount(array $account, bool $cogs): bool {
        $code = $this->accountCodeNumber($account);
        if ($cogs) {
            return $code >= 5000 && $code <= 5999;
        }
        return $code >= 6000 && $code <= 9999;
    }

    public function accountIncreasesWithDebit($accountType): bool {
        return in_array(strtolower((string) $accountType), ['assets', 'asset', 'expenses', 'expense'], true);
    }

    public function applyMovement(float $balance, float $debit, float $credit, $accountType): float {
        $debit = floatval($debit);
        $credit = floatval($credit);
        if ($this->accountIncreasesWithDebit($accountType)) {
            return $balance + $debit - $credit;
        }
        return $balance + $credit - $debit;
    }

    /**
     * Net income for a period (journals on chart 4000–4999 / 5000+), aligned with P&L report.
     */
    public function calculateNetIncomeForPeriod(string $startDate, string $endDate): float {
        $prefix = $this->db->getPrefix();
        $totalRevenue = 0.0;
        $totalCogs = 0.0;
        $totalExpenses = 0.0;

        foreach ($this->accountModel->getByType('Revenue') as $account) {
            if (!$this->isProfitLossRevenueAccount($account)) {
                continue;
            }
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(credit - debit), 0) AS balance
                 FROM `{$prefix}journal_entry_lines` jel
                 JOIN `{$prefix}journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? AND je.entry_date BETWEEN ? AND ? AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            $totalRevenue += floatval($balance['balance'] ?? 0);
        }

        foreach ($this->accountModel->getByType('Expenses') as $account) {
            $isCogs = $this->isProfitLossExpenseAccount($account, true);
            if (!$isCogs && !$this->isProfitLossExpenseAccount($account, false)) {
                continue;
            }
            $balance = $this->db->fetchOne(
                "SELECT COALESCE(SUM(debit - credit), 0) AS balance
                 FROM `{$prefix}journal_entry_lines` jel
                 JOIN `{$prefix}journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ? AND je.entry_date BETWEEN ? AND ? AND je.status = 'posted'",
                [$account['id'], $startDate, $endDate]
            );
            $amount = floatval($balance['balance'] ?? 0);
            if ($isCogs) {
                $totalCogs += $amount;
            } else {
                $totalExpenses += $amount;
            }
        }

        return $totalRevenue - $totalCogs - $totalExpenses;
    }

    /**
     * Sum balances on cash GL accounts (1000–1099) as of a date.
     */
    public function getTotalCashGlBalance(string $asOfDate, Balance_calculator $calculator): float {
        $total = 0.0;
        foreach ($this->accountModel->getCashGlAccounts() as $account) {
            $total += $calculator->calculateBalance((int) $account['id'], $asOfDate, false);
        }
        return $total;
    }
}
