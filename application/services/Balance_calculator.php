<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Balance Calculator Service
 * 
 * Centralized service for calculating account balances from journal entries.
 * Ensures accuracy and provides caching for performance.
 */
class Balance_calculator {
    private $db;
    private $journalModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->journalModel = $this->loadModel('Journal_entry_model');
    }
    
    /**
     * Calculate account balance from journal entries
     * 
     * @param int $accountId Account ID
     * @param string|null $asOfDate Calculate balance as of this date (Y-m-d format)
     * @param bool $useCache Whether to use cached balance
     * @return float Calculated balance
     */
    public function calculateBalance($accountId, $asOfDate = null, $useCache = true) {
        if ($useCache && $asOfDate) {
            $cached = $this->getCachedBalance($accountId, $asOfDate);
            if ($cached !== null) {
                return $cached;
            }
        }

        $account = $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . "accounts` WHERE id = ?",
            [$accountId]
        );

        if (!$account) {
            return 0.00;
        }

        $journalBal = $this->calculateJournalBalance($account, $asOfDate);
        $txnBal = $this->calculateTransactionBalance($account, $asOfDate);

        $journalCount = $this->countPostedJournalLines($accountId, $asOfDate);
        $txnCount = $this->countPostedTransactions($accountId, $asOfDate);

        // Prefer journals when they carry a real balance; many booking payments only hit legacy transactions.
        if ($journalCount === 0) {
            $balance = $txnBal;
        } elseif (abs($journalBal) >= 0.01) {
            $balance = $journalBal;
        } elseif ($txnCount > 0 && abs($txnBal) >= 0.01) {
            $balance = $txnBal;
        } else {
            $balance = $journalBal;
        }

        if ($asOfDate) {
            $this->cacheBalance($accountId, $balance, $asOfDate);
        }

        return $balance;
    }

    /**
     * Balance from posted journal entry lines only.
     */
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

    public function calculateJournalBalance(array $account, $asOfDate = null) {
        $accountId = (int) $account['id'];
        $balance = floatval($account['opening_balance'] ?? 0);

        $dateFilter = '';
        $params = [$accountId];
        if ($asOfDate) {
            $dateFilter = ' AND je.entry_date <= ?';
            $params[] = $asOfDate;
        }

        $sql = "SELECT jel.debit, jel.credit
                FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
                JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
                WHERE jel.account_id = ?
                AND je.status = 'posted'
                {$dateFilter}
                ORDER BY je.entry_date ASC";

        $entries = $this->db->fetchAll($sql, $params);
        $accountType = $account['account_type'];
        $increasesWithDebit = in_array(strtolower($accountType), ['assets', 'asset', 'expenses', 'expense']);

        foreach ($entries as $entry) {
            $debit = floatval($entry['debit']);
            $credit = floatval($entry['credit']);
            $balance += $increasesWithDebit ? ($debit - $credit) : ($credit - $debit);
        }

        return $balance;
    }

    /**
     * Balance from legacy posted transactions (booking payments, manual entries).
     */
    public function calculateTransactionBalance(array $account, $asOfDate = null) {
        $accountId = (int) $account['id'];
        $balance = floatval($account['opening_balance'] ?? 0);

        $dateFilter = '';
        $params = [$accountId];
        if ($asOfDate) {
            $dateFilter = ' AND transaction_date <= ?';
            $params[] = $asOfDate;
        }

        $row = $this->db->fetchOne(
            "SELECT COALESCE(SUM(debit - credit), 0) AS movement
             FROM `" . $this->db->getPrefix() . "transactions`
             WHERE account_id = ? AND status = 'posted'{$dateFilter}",
            $params
        );

        $accountType = $account['account_type'];
        $increasesWithDebit = in_array(strtolower($accountType), ['assets', 'asset', 'expenses', 'expense']);
        $movement = floatval($row['movement'] ?? 0);

        return $balance + ($increasesWithDebit ? $movement : -$movement);
    }

    private function countPostedJournalLines($accountId, $asOfDate = null) {
        $dateFilter = '';
        $params = [$accountId];
        if ($asOfDate) {
            $dateFilter = ' AND je.entry_date <= ?';
            $params[] = $asOfDate;
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM `" . $this->db->getPrefix() . "journal_entry_lines` jel
             JOIN `" . $this->db->getPrefix() . "journal_entries` je ON jel.journal_entry_id = je.id
             WHERE jel.account_id = ? AND je.status = 'posted'{$dateFilter}",
            $params
        );

        return intval($row['cnt'] ?? 0);
    }

    private function countPostedTransactions($accountId, $asOfDate = null) {
        $dateFilter = '';
        $params = [$accountId];
        if ($asOfDate) {
            $dateFilter = ' AND transaction_date <= ?';
            $params[] = $asOfDate;
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM `" . $this->db->getPrefix() . "transactions`
             WHERE account_id = ? AND status = 'posted'{$dateFilter}",
            $params
        );

        return intval($row['cnt'] ?? 0);
    }
    
    /**
     * Calculate balances for multiple accounts (batch operation)
     * 
     * @param array $accountIds Array of account IDs
     * @param string|null $asOfDate Calculate balances as of this date
     * @return array Associative array of account_id => balance
     */
    public function calculateBalances($accountIds, $asOfDate = null) {
        $balances = [];
        
        foreach ($accountIds as $accountId) {
            $balances[$accountId] = $this->calculateBalance($accountId, $asOfDate);
        }
        
        return $balances;
    }
    
    /**
     * Get cached balance
     * 
     * @param int $accountId Account ID
     * @param string $asOfDate Date (Y-m-d format)
     * @return float|null Cached balance or null if not found
     */
    public function getCachedBalance($accountId, $asOfDate) {
        try {
            $cached = $this->db->fetchOne(
                "SELECT balance FROM `" . $this->db->getPrefix() . "account_balance_cache` 
                 WHERE account_id = ? AND as_of_date = ?",
                [$accountId, $asOfDate]
            );
            
            return $cached ? floatval($cached['balance']) : null;
        } catch (Exception $e) {
            error_log('Balance_calculator getCachedBalance error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cache a calculated balance
     * 
     * @param int $accountId Account ID
     * @param float $balance Calculated balance
     * @param string $asOfDate Date (Y-m-d format)
     * @return bool Success
     */
    private function cacheBalance($accountId, $balance, $asOfDate) {
        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert
            $sql = "INSERT INTO `" . $this->db->getPrefix() . "account_balance_cache` 
                    (account_id, balance, as_of_date, last_updated)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    balance = VALUES(balance), 
                    last_updated = NOW()";
            
            $this->db->query($sql, [$accountId, $balance, $asOfDate]);
            return true;
        } catch (Exception $e) {
            error_log('Balance_calculator cacheBalance error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Refresh cached balance for an account
     * 
     * @param int $accountId Account ID
     * @param string|null $asOfDate Date to refresh (defaults to today)
     * @return float Refreshed balance
     */
    public function refreshCache($accountId, $asOfDate = null) {
        if (!$asOfDate) {
            $asOfDate = date('Y-m-d');
        }
        
        // Force recalculation without using cache
        return $this->calculateBalance($accountId, $asOfDate, false);
    }
    
    /**
     * Invalidate cache for an account
     * 
     * @param int $accountId Account ID
     * @param string|null $asOfDate Specific date to invalidate (null = all dates)
     * @return bool Success
     */
    public function invalidateCache($accountId, $asOfDate = null) {
        try {
            if ($asOfDate) {
                // Invalidate specific date
                $this->db->query(
                    "DELETE FROM `" . $this->db->getPrefix() . "account_balance_cache` 
                     WHERE account_id = ? AND as_of_date = ?",
                    [$accountId, $asOfDate]
                );
            } else {
                // Invalidate all dates for this account
                $this->db->query(
                    "DELETE FROM `" . $this->db->getPrefix() . "account_balance_cache` 
                     WHERE account_id = ?",
                    [$accountId]
                );
            }
            return true;
        } catch (Exception $e) {
            error_log('Balance_calculator invalidateCache error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get balance history for an account over a date range
     * 
     * @param int $accountId Account ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Array of ['date' => balance] pairs
     */
    public function getBalanceHistory($accountId, $startDate, $endDate) {
        $history = [];
        
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $interval = new DateInterval('P1D'); // 1 day
        
        while ($start <= $end) {
            $date = $start->format('Y-m-d');
            $history[$date] = $this->calculateBalance($accountId, $date);
            $start->add($interval);
        }
        
        return $history;
    }
    
    /**
     * Reconcile calculated balance with stored balance
     * 
     * @param int $accountId Account ID
     * @return array Reconciliation result
     */
    public function reconcileBalance($accountId) {
        $calculated = $this->calculateBalance($accountId, date('Y-m-d'), false);
        
        $account = $this->db->fetchOne(
            "SELECT balance FROM `" . $this->db->getPrefix() . "accounts` WHERE id = ?",
            [$accountId]
        );
        
        $stored = floatval($account['balance'] ?? 0);
        $difference = $calculated - $stored;
        
        return [
            'account_id' => $accountId,
            'calculated_balance' => $calculated,
            'stored_balance' => $stored,
            'difference' => $difference,
            'is_balanced' => abs($difference) < 0.01 // Allow for floating point precision
        ];
    }
    
    /**
     * Load a model
     * 
     * @param string $modelName Model name
     * @return object Model instance
     */
    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }
}
