<?php
/**
 * Bug Condition Exploration Test — Accounting Reports
 *
 * Task 1: Write bug condition exploration test
 * Sub-tasks 1a, 1b, 1c, 1d
 *
 * CRITICAL: These tests MUST FAIL on unfixed code.
 * Failure confirms the bugs exist in Reports.php.
 * DO NOT fix the code — just run and document the counterexamples.
 *
 * Run: php tests/test_reports_bug_condition.php
 *
 * Validates: Requirements 1.1, 1.2
 */

define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

require_once BASEPATH . 'core/Database.php';
require_once BASEPATH . 'core/Base_Model.php';

// ── Test harness ──────────────────────────────────────────────────────────────

$passed        = 0;
$failed        = 0;
$counterexamples = [];

function assert_has_keys(string $label, array $item, array $expectedKeys): bool {
    global $passed, $failed, $counterexamples;
    $missing = [];
    foreach ($expectedKeys as $key) {
        if (!array_key_exists($key, $item)) {
            $missing[] = $key;
        }
    }
    if (empty($missing)) {
        echo "  PASS  {$label}\n";
        $passed++;
        return true;
    }
    $actualKeys = implode(', ', array_keys($item));
    echo "  FAIL  {$label}\n";
    echo "        Expected keys : " . implode(', ', $expectedKeys) . "\n";
    echo "        Actual keys   : {$actualKeys}\n";
    echo "        Missing       : " . implode(', ', $missing) . "\n";
    $counterexamples[] = [
        'test'    => $label,
        'item'    => $item,
        'missing' => $missing,
    ];
    $failed++;
    return false;
}

function assert_key_set(string $label, array $data, string $key): bool {
    global $passed, $failed, $counterexamples;
    if (isset($data[$key]) && !empty($data[$key])) {
        echo "  PASS  {$label}\n";
        $passed++;
        return true;
    }
    $actualKeys = implode(', ', array_keys($data));
    echo "  FAIL  {$label}\n";
    echo "        Expected key '{$key}' to be set and non-empty in \$data\n";
    echo "        Actual keys in \$data: {$actualKeys}\n";
    $counterexamples[] = [
        'test'        => $label,
        'data_keys'   => array_keys($data),
        'missing_key' => $key,
    ];
    $failed++;
    return false;
}

// ── Database bootstrap ────────────────────────────────────────────────────────

$db     = Database::getInstance();
$prefix = $db->getPrefix();

// ── Seed test data inside a transaction (always rolled back) ──────────────────

$db->beginTransaction();

try {
    $testDate  = '2024-06-15';
    $startDate = '2024-06-01';
    $endDate   = '2024-06-30';

    // Use codes unlikely to collide with existing data
    $revenueCode = '4801';
    $expenseCode = '6801';
    $assetCode   = '1801';

    // 1. Revenue account — DB stores type as 'Revenue'
    $revenueAccountId = $db->insert('accounts', [
        'account_code' => $revenueCode,
        'account_name' => '_Test Revenue Account',
        'account_type' => 'Revenue',
        'balance'      => 0,
        'status'       => 'active',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    // 2. Expense account — DB stores type as 'Expenses', code 6xxx (not COGS 5xxx)
    $expenseAccountId = $db->insert('accounts', [
        'account_code' => $expenseCode,
        'account_name' => '_Test Expense Account',
        'account_type' => 'Expenses',
        'balance'      => 0,
        'status'       => 'active',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    // 3. Asset account — DB stores type as 'Assets'
    $assetAccountId = $db->insert('accounts', [
        'account_code' => $assetCode,
        'account_name' => '_Test Asset Account',
        'account_type' => 'Assets',
        'balance'      => 0,
        'status'       => 'active',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    // 4. Posted journal entry
    $journalEntryId = $db->insert('journal_entries', [
        'entry_number' => '_TEST_BUG_JE_001',
        'entry_date'   => $testDate,
        'description'  => 'Test bug condition entry',
        'status'       => 'posted',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    // 5. Journal entry lines
    // Revenue: credit increases revenue balance
    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $journalEntryId,
        'account_id'       => $revenueAccountId,
        'debit'            => 0,
        'credit'           => 50000,
        'description'      => 'Revenue credit line',
    ]);

    // Asset: debit (cash received)
    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $journalEntryId,
        'account_id'       => $assetAccountId,
        'debit'            => 50000,
        'credit'           => 0,
        'description'      => 'Asset debit line',
    ]);

    // Expense: debit increases expense balance
    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $journalEntryId,
        'account_id'       => $expenseAccountId,
        'debit'            => 5000,
        'credit'           => 0,
        'description'      => 'Expense debit line',
    ]);

    // ── Replicate profitLoss() logic EXACTLY as written in Reports.php ────────
    //
    // Account_model::getByType('Revenue') normalises 'Revenue' -> 'income' and
    // queries WHERE account_type IN ('income', 'Revenue').
    // Account_model::getByType('Expenses') normalises 'Expenses' -> 'expense'
    // and queries WHERE account_type IN ('expense', 'Expenses').

    // Revenue items
    $revenueAccounts = $db->fetchAll(
        "SELECT * FROM `{$prefix}accounts`
         WHERE (account_type = ? OR account_type = ?) AND status = 'active'
         ORDER BY account_code",
        ['income', 'Revenue']
    );

    $revenue      = [];
    $totalRevenue = 0;

    foreach ($revenueAccounts as $account) {
        $balance = $db->fetchOne(
            "SELECT COALESCE(SUM(credit - debit), 0) as balance
             FROM `{$prefix}journal_entry_lines` jel
             JOIN `{$prefix}journal_entries` je ON jel.journal_entry_id = je.id
             WHERE jel.account_id = ?
             AND je.entry_date BETWEEN ? AND ?
             AND je.status = 'posted'",
            [$account['id'], $startDate, $endDate]
        );

        if ($balance['balance'] > 0) {
            // *** BUG: controller uses 'account' and 'amount' ***
            $revenue[] = [
                'account' => $account['account_name'],
                'amount'  => $balance['balance'],
            ];
            $totalRevenue += $balance['balance'];
        }
    }

    // Expense items
    $expenseAccounts = $db->fetchAll(
        "SELECT * FROM `{$prefix}accounts`
         WHERE (account_type = ? OR account_type = ?) AND status = 'active'
         ORDER BY account_code",
        ['expense', 'Expenses']
    );

    $expenses      = [];
    $totalExpenses = 0;

    foreach ($expenseAccounts as $account) {
        // Skip COGS (code starts with 5)
        if (substr($account['account_code'], 0, 1) == '5') continue;

        $balance = $db->fetchOne(
            "SELECT COALESCE(SUM(debit - credit), 0) as balance
             FROM `{$prefix}journal_entry_lines` jel
             JOIN `{$prefix}journal_entries` je ON jel.journal_entry_id = je.id
             WHERE jel.account_id = ?
             AND je.entry_date BETWEEN ? AND ?
             AND je.status = 'posted'",
            [$account['id'], $startDate, $endDate]
        );

        if ($balance['balance'] > 0) {
            // *** BUG: controller uses 'account' and 'amount' ***
            $expenses[] = [
                'account' => $account['account_name'],
                'amount'  => $balance['balance'],
            ];
            $totalExpenses += $balance['balance'];
        }
    }

    // ── Replicate trialBalance() logic EXACTLY as written in Reports.php ──────

    $allAccounts  = $db->fetchAll(
        "SELECT * FROM `{$prefix}accounts` WHERE status = 'active' ORDER BY account_code"
    );

    $balances     = [];
    $totalDebits  = 0;
    $totalCredits = 0;

    foreach ($allAccounts as $account) {
        // Inline balance calculation matching Balance_calculator::calculateBalance
        $entries = $db->fetchAll(
            "SELECT jel.debit, jel.credit
             FROM `{$prefix}journal_entry_lines` jel
             JOIN `{$prefix}journal_entries` je ON jel.journal_entry_id = je.id
             WHERE jel.account_id = ?
             AND je.status = 'posted'
             AND je.entry_date <= ?
             ORDER BY je.entry_date ASC",
            [$account['id'], $endDate]
        );

        $balance     = floatval($account['opening_balance'] ?? 0);
        $accountType = $account['account_type'];

        foreach ($entries as $entry) {
            $debit  = floatval($entry['debit']);
            $credit = floatval($entry['credit']);
            if (in_array(strtolower($accountType), ['assets', 'asset', 'expenses', 'expense'])) {
                $balance += $debit - $credit;
            } else {
                $balance += $credit - $debit;
            }
        }

        if ($balance != 0) {
            $debit  = 0;
            $credit = 0;
            if (in_array(strtolower($accountType), ['assets', 'asset', 'expenses', 'expense'])) {
                $debit = abs($balance);
            } else {
                $credit = abs($balance);
            }

            // *** BUG: controller uses 'code', 'account', 'debit', 'credit' — missing 'account_type' ***
            $balances[] = [
                'code'    => $account['account_code'],
                'account' => $account['account_name'],
                'debit'   => $debit,
                'credit'  => $credit,
            ];

            $totalDebits  += $debit;
            $totalCredits += $credit;
        }
    }

    // *** BUG: controller passes key 'balances', not 'trial_balance' ***
    $data = [
        'page_title'    => 'Trial Balance',
        'as_of_date'    => $endDate,
        'balances'      => $balances,   // BUG: should be 'trial_balance'
        'total_debits'  => $totalDebits,
        'total_credits' => $totalCredits,
        'in_balance'    => abs($totalDebits - $totalCredits) < 0.01,
    ];

    // ── Run assertions ────────────────────────────────────────────────────────

    echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  BUG CONDITION EXPLORATION TESTS — Reports.php                  ║\n";
    echo "║  EXPECTED: ALL tests FAIL (confirms bugs exist)                 ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

    echo "── Test 1a: profitLoss() revenue item keys ──\n";
    if (!empty($revenue)) {
        assert_has_keys(
            '1a: $revenue[0] has keys account_code, account_name, total',
            $revenue[0],
            ['account_code', 'account_name', 'total']
        );
    } else {
        echo "  SKIP  1a: No revenue items produced — seed data may not have matched\n";
        echo "        Revenue accounts found: " . count($revenueAccounts) . "\n";
    }

    echo "\n── Test 1b: profitLoss() expense item keys ──\n";
    if (!empty($expenses)) {
        assert_has_keys(
            '1b: $expenses[0] has keys account_code, account_name, total',
            $expenses[0],
            ['account_code', 'account_name', 'total']
        );
    } else {
        echo "  SKIP  1b: No expense items produced — seed data may not have matched\n";
        echo "        Expense accounts found: " . count($expenseAccounts) . "\n";
    }

    echo "\n── Test 1c: trialBalance() uses key trial_balance (not balances) ──\n";
    assert_key_set(
        "1c: \$data['trial_balance'] is set and non-empty",
        $data,
        'trial_balance'
    );

    echo "\n── Test 1d: trialBalance() row keys ──\n";
    // 1d inspects the actual row structure regardless of which key holds it
    $trialRows = $data['trial_balance'] ?? $data['balances'] ?? [];
    if (!empty($trialRows)) {
        assert_has_keys(
            '1d: trial_balance row has keys account_code, account_name, account_type, total_debit, total_credit',
            $trialRows[0],
            ['account_code', 'account_name', 'account_type', 'total_debit', 'total_credit']
        );
    } else {
        echo "  SKIP  1d: No trial balance rows found — check seed data\n";
    }

} finally {
    // Always roll back — test data never persists
    $db->rollBack();
}

// ── Summary ───────────────────────────────────────────────────────────────────

echo "\n══════════════════════════════════════════════════════════════════════\n";
echo "  Results: {$passed} passed, {$failed} failed\n";

if (!empty($counterexamples)) {
    echo "\n  Counterexamples found (confirm bugs exist in Reports.php):\n";
    foreach ($counterexamples as $ce) {
        echo "\n  ▸ {$ce['test']}\n";
        if (isset($ce['item'])) {
            echo "    Actual item  : " . json_encode($ce['item']) . "\n";
            echo "    Missing keys : " . implode(', ', $ce['missing']) . "\n";
        }
        if (isset($ce['data_keys'])) {
            echo "    \$data keys present : " . implode(', ', $ce['data_keys']) . "\n";
            echo "    Missing key        : {$ce['missing_key']}\n";
        }
    }
}

echo "\n  NOTE: Failures above are EXPECTED — they confirm the bugs in Reports.php.\n";
echo "        DO NOT fix the code yet. Document these counterexamples.\n";
echo "══════════════════════════════════════════════════════════════════════\n\n";

exit($failed > 0 ? 1 : 0);
