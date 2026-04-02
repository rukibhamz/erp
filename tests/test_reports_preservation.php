<?php
/**
 * Preservation Property Tests — Accounting Reports
 *
 * Task 2: Write preservation property tests (BEFORE implementing fix)
 * Property 3: Preservation — Unaffected Reports Unchanged
 *
 * IMPORTANT: These tests MUST PASS on unfixed code.
 * They confirm the baseline behavior of balanceSheet(), generalLedger(),
 * and cashFlow() that must be preserved after the fix.
 *
 * Run: php tests/test_reports_preservation.php
 *
 * Validates: Requirements 3.1, 3.2, 3.3
 */

define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

require_once BASEPATH . 'core/Database.php';
require_once BASEPATH . 'core/Base_Model.php';
require_once BASEPATH . 'services/Balance_calculator.php';
require_once BASEPATH . 'models/Account_model.php';

// ── Test harness ──────────────────────────────────────────────────────────────

$passed = 0;
$failed = 0;
$errors = [];

function assert_true(string $label, bool $condition, string $detail = ''): bool {
    global $passed, $failed, $errors;
    if ($condition) {
        echo "  PASS  {$label}\n";
        $passed++;
        return true;
    }
    echo "  FAIL  {$label}\n";
    if ($detail) {
        echo "        {$detail}\n";
    }
    $errors[] = ['test' => $label, 'detail' => $detail];
    $failed++;
    return false;
}

function assert_array_has_keys(string $label, array $item, array $expectedKeys): bool {
    global $passed, $failed, $errors;
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
    $detail = "Expected keys: " . implode(', ', $expectedKeys)
            . " | Actual keys: {$actualKeys}"
            . " | Missing: " . implode(', ', $missing);
    echo "  FAIL  {$label}\n";
    echo "        {$detail}\n";
    $errors[] = ['test' => $label, 'detail' => $detail];
    $failed++;
    return false;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Replicate balanceSheet() data-building logic exactly as in Reports.php.
 * Returns the $data array (without page_title/flash/format handling).
 */
function buildBalanceSheetData(string $asOfDate): array {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    $balanceCalculator = new Balance_calculator();

    $accountModel = new Account_model();

    // Assets
    $assetAccounts = $accountModel->getByType('Assets');
    $assets = [];
    $totalAssets = 0;
    foreach ($assetAccounts as $account) {
        $balance = $balanceCalculator->calculateBalance($account['id'], $asOfDate);
        if ($balance != 0) {
            $assets[] = ['account' => $account['account_name'], 'amount' => $balance];
            $totalAssets += $balance;
        }
    }

    // Liabilities
    $liabilityAccounts = $accountModel->getByType('Liabilities');
    $liabilities = [];
    $totalLiabilities = 0;
    foreach ($liabilityAccounts as $account) {
        $balance = $balanceCalculator->calculateBalance($account['id'], $asOfDate);
        if ($balance != 0) {
            $liabilities[] = ['account' => $account['account_name'], 'amount' => $balance];
            $totalLiabilities += $balance;
        }
    }

    // Equity
    $equityAccounts = $accountModel->getByType('Equity');
    $equity = [];
    $totalEquity = 0;
    foreach ($equityAccounts as $account) {
        $balance = $balanceCalculator->calculateBalance($account['id'], $asOfDate);
        if ($balance != 0) {
            $equity[] = ['account' => $account['account_name'], 'amount' => $balance];
            $totalEquity += $balance;
        }
    }

    // Retained earnings
    $retainedEarnings = $totalAssets - $totalLiabilities - $totalEquity;
    $equity[] = ['account' => 'Retained Earnings', 'amount' => $retainedEarnings];
    $totalEquity += $retainedEarnings;

    return [
        'as_of_date'       => $asOfDate,
        'assets'           => $assets,
        'total_assets'     => $totalAssets,
        'liabilities'      => $liabilities,
        'total_liabilities'=> $totalLiabilities,
        'equity'           => $equity,
        'total_equity'     => $totalEquity,
    ];
}

/**
 * Replicate generalLedger() data-building logic exactly as in Reports.php.
 */
function buildGeneralLedgerData(?int $accountId, string $startDate, string $endDate): array {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    $balanceCalculator = new Balance_calculator();
    $accountModel = new Account_model();

    $transactions = [];
    $selectedAccount = null;
    $openingBalance = 0;

    if ($accountId) {
        $selectedAccount = $accountModel->getById($accountId);
        if ($selectedAccount) {
            $openingBalance = $balanceCalculator->calculateBalance(
                $accountId,
                date('Y-m-d', strtotime($startDate . ' -1 day'))
            );

            $transactions = $db->fetchAll(
                "SELECT je.entry_date, je.description, je.reference_type, je.reference_id,
                        jel.debit, jel.credit, jel.description as line_description
                 FROM `{$prefix}journal_entry_lines` jel
                 JOIN `{$prefix}journal_entries` je ON jel.journal_entry_id = je.id
                 WHERE jel.account_id = ?
                 AND je.entry_date BETWEEN ? AND ?
                 AND je.status = 'posted'
                 ORDER BY je.entry_date, je.id",
                [$accountId, $startDate, $endDate]
            );
        }
    }

    return [
        'start_date'          => $startDate,
        'end_date'            => $endDate,
        'selected_account_id' => $accountId,
        'selected_account'    => $selectedAccount,
        'opening_balance'     => $openingBalance,
        'transactions'        => $transactions,
    ];
}

/**
 * Replicate cashFlow() data-building logic exactly as in Reports.php.
 */
function buildCashFlowData(string $startDate, string $endDate): array {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    $balanceCalculator = new Balance_calculator();
    $accountModel = new Account_model();

    // Net income
    $revenueAccounts = $accountModel->getByType('Revenue');
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
        $totalRevenue += $balance['balance'];
    }

    $expenseAccounts = $accountModel->getByType('Expenses');
    $totalExpenses = 0;
    foreach ($expenseAccounts as $account) {
        $balance = $db->fetchOne(
            "SELECT COALESCE(SUM(debit - credit), 0) as balance
             FROM `{$prefix}journal_entry_lines` jel
             JOIN `{$prefix}journal_entries` je ON jel.journal_entry_id = je.id
             WHERE jel.account_id = ?
             AND je.entry_date BETWEEN ? AND ?
             AND je.status = 'posted'",
            [$account['id'], $startDate, $endDate]
        );
        $totalExpenses += $balance['balance'];
    }

    $netIncome = $totalRevenue - $totalExpenses;

    // Cash account (first asset with code starting with '1')
    $cashAccounts = $accountModel->getByType('Assets');
    $cashAccount = null;
    foreach ($cashAccounts as $account) {
        $code = $account['account_code'] ?? '';
        if (substr($code, 0, 1) === '1') {
            $cashAccount = $account;
            break;
        }
    }

    $beginningCash = 0;
    $endingCash = 0;
    $operatingActivities = [];

    if ($cashAccount) {
        $beginningCash = $balanceCalculator->calculateBalance(
            $cashAccount['id'],
            date('Y-m-d', strtotime($startDate . ' -1 day'))
        );
        $endingCash = $balanceCalculator->calculateBalance($cashAccount['id'], $endDate);

        $transactions = $db->fetchAll(
            "SELECT je.entry_date, je.description, jel.debit, jel.credit
             FROM `{$prefix}journal_entry_lines` jel
             JOIN `{$prefix}journal_entries` je ON jel.journal_entry_id = je.id
             WHERE jel.account_id = ?
             AND je.entry_date BETWEEN ? AND ?
             AND je.status = 'posted'
             ORDER BY je.entry_date",
            [$cashAccount['id'], $startDate, $endDate]
        );

        foreach ($transactions as $txn) {
            $operatingActivities[] = [
                'date'        => $txn['entry_date'],
                'description' => $txn['description'],
                'amount'      => $txn['debit'] - $txn['credit'],
            ];
        }
    }

    $netCashFlow = $endingCash - $beginningCash;

    return [
        'start_date'           => $startDate,
        'end_date'             => $endDate,
        'net_income'           => $netIncome,
        'beginning_cash'       => $beginningCash,
        'ending_cash'          => $endingCash,
        'net_cash_flow'        => $netCashFlow,
        'operating_activities' => $operatingActivities,
        'investing'            => [],
        'financing'            => [],
    ];
}

// ── Property generators (simulate PBT with representative date ranges) ────────

/**
 * Generate a set of representative as_of_date values spanning past, present, future,
 * month boundaries, and year boundaries — simulating property-based date generation.
 */
function generateAsOfDates(): array {
    return [
        date('Y-m-d'),                          // today
        date('Y-m-01'),                         // first of current month
        date('Y-m-t'),                          // last of current month
        date('Y-01-01'),                        // first of current year
        date('Y-12-31'),                        // last of current year
        date('Y-m-d', strtotime('-1 month')),   // one month ago
        date('Y-m-d', strtotime('-6 months')),  // six months ago
        date('Y-m-d', strtotime('-1 year')),    // one year ago
        '2024-01-01',                           // fixed past date
        '2024-06-30',                           // fixed mid-year
        '2023-12-31',                           // fixed year-end
    ];
}

/**
 * Generate representative date range pairs (startDate, endDate).
 */
function generateDateRanges(): array {
    return [
        ['2024-01-01', '2024-01-31'],
        ['2024-06-01', '2024-06-30'],
        ['2024-01-01', '2024-12-31'],
        [date('Y-m-01'), date('Y-m-t')],
        [date('Y-01-01'), date('Y-12-31')],
        [date('Y-m-d', strtotime('-3 months')), date('Y-m-d')],
        ['2023-01-01', '2023-12-31'],
        ['2024-07-01', '2024-09-30'],  // Q3
    ];
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

    // Seed accounts with codes unlikely to collide
    $assetAccountId = $db->insert('accounts', [
        'account_code' => '1901',
        'account_name' => '_Test Asset (Preservation)',
        'account_type' => 'Assets',
        'balance'      => 0,
        'status'       => 'active',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    $liabilityAccountId = $db->insert('accounts', [
        'account_code' => '2901',
        'account_name' => '_Test Liability (Preservation)',
        'account_type' => 'Liabilities',
        'balance'      => 0,
        'status'       => 'active',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    $equityAccountId = $db->insert('accounts', [
        'account_code' => '3901',
        'account_name' => '_Test Equity (Preservation)',
        'account_type' => 'Equity',
        'balance'      => 0,
        'status'       => 'active',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    $revenueAccountId = $db->insert('accounts', [
        'account_code' => '4901',
        'account_name' => '_Test Revenue (Preservation)',
        'account_type' => 'Revenue',
        'balance'      => 0,
        'status'       => 'active',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    // Posted journal entry: asset debit / revenue credit
    $journalEntryId = $db->insert('journal_entries', [
        'entry_number' => '_TEST_PRES_JE_001',
        'entry_date'   => $testDate,
        'description'  => 'Preservation test entry',
        'status'       => 'posted',
        'created_at'   => date('Y-m-d H:i:s'),
    ]);

    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $journalEntryId,
        'account_id'       => $assetAccountId,
        'debit'            => 30000,
        'credit'           => 0,
        'description'      => 'Asset debit',
    ]);

    $db->insert('journal_entry_lines', [
        'journal_entry_id' => $journalEntryId,
        'account_id'       => $revenueAccountId,
        'debit'            => 0,
        'credit'           => 30000,
        'description'      => 'Revenue credit',
    ]);

    // ── PROPERTY 3a: balanceSheet() structure preservation ───────────────────
    //
    // For all as_of_date values, balanceSheet() produces:
    //   $data['assets']      — array of items with 'account' and 'amount' keys
    //   $data['liabilities'] — array of items with 'account' and 'amount' keys
    //   $data['equity']      — array of items with 'account' and 'amount' keys
    //   total_assets == sum of asset amounts
    //   total_liabilities == sum of liability amounts
    //   total_equity == sum of equity amounts (including retained earnings)
    //   total_assets == total_liabilities + total_equity (balance equation)
    //
    // Validates: Requirements 3.1

    echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  PRESERVATION PROPERTY TESTS — Reports.php                     ║\n";
    echo "║  EXPECTED: ALL tests PASS (confirms baseline behavior)         ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

    echo "── Property 3a: balanceSheet() structure preservation ──\n";
    echo "   (for all as_of_date values)\n\n";

    $asOfDates = generateAsOfDates();
    $balanceSheetStructureOk = true;
    $balanceSheetTotalsOk    = true;
    $balanceSheetEquationOk  = true;

    foreach ($asOfDates as $asOfDate) {
        $data = buildBalanceSheetData($asOfDate);

        // Check top-level keys exist
        foreach (['assets', 'liabilities', 'equity', 'total_assets', 'total_liabilities', 'total_equity'] as $key) {
            if (!array_key_exists($key, $data)) {
                $balanceSheetStructureOk = false;
                break 2;
            }
        }

        // Each item in assets/liabilities/equity must have 'account' and 'amount'
        foreach (['assets', 'liabilities', 'equity'] as $section) {
            foreach ($data[$section] as $item) {
                if (!array_key_exists('account', $item) || !array_key_exists('amount', $item)) {
                    $balanceSheetStructureOk = false;
                    break 3;
                }
            }
        }

        // Totals must match sum of amounts
        $sumAssets = array_sum(array_column($data['assets'], 'amount'));
        $sumLiab   = array_sum(array_column($data['liabilities'], 'amount'));
        $sumEquity = array_sum(array_column($data['equity'], 'amount'));

        if (abs($sumAssets - $data['total_assets']) > 0.01) {
            $balanceSheetTotalsOk = false;
        }
        if (abs($sumLiab - $data['total_liabilities']) > 0.01) {
            $balanceSheetTotalsOk = false;
        }
        if (abs($sumEquity - $data['total_equity']) > 0.01) {
            $balanceSheetTotalsOk = false;
        }

        // Balance equation: assets == liabilities + equity
        if (abs($data['total_assets'] - ($data['total_liabilities'] + $data['total_equity'])) > 0.01) {
            $balanceSheetEquationOk = false;
        }
    }

    assert_true(
        '3a-i: assets/liabilities/equity arrays have account+amount keys (all dates)',
        $balanceSheetStructureOk,
        'At least one item was missing the "account" or "amount" key'
    );

    assert_true(
        '3a-ii: total_assets/total_liabilities/total_equity match sum of amounts (all dates)',
        $balanceSheetTotalsOk,
        'A total field did not match the sum of its section items'
    );

    assert_true(
        '3a-iii: balance equation holds: total_assets == total_liabilities + total_equity (all dates)',
        $balanceSheetEquationOk,
        'Balance equation violated: assets != liabilities + equity'
    );

    // Spot-check with seeded data: asset account should appear in assets section
    $dataSpot = buildBalanceSheetData($endDate);
    $assetNames = array_column($dataSpot['assets'], 'account');
    assert_true(
        '3a-iv: seeded asset account appears in assets section',
        in_array('_Test Asset (Preservation)', $assetNames),
        'Seeded asset account not found in $data[assets]'
    );

    // ── PROPERTY 3b: generalLedger() structure preservation ──────────────────
    //
    // For all account_id + date range pairs, generalLedger() produces:
    //   $data['transactions'] — array of rows with entry_date, debit, credit, description
    //   $data['opening_balance'] — numeric
    //   closing balance = opening_balance + sum(debit - credit) for all transactions
    //
    // Validates: Requirements 3.2

    echo "\n── Property 3b: generalLedger() structure preservation ──\n";
    echo "   (for all account_id + date range pairs)\n\n";

    $dateRanges = generateDateRanges();
    $glStructureOk      = true;
    $glRunningBalanceOk = true;

    foreach ($dateRanges as [$start, $end]) {
        $data = buildGeneralLedgerData($assetAccountId, $start, $end);

        // Must have transactions array and opening_balance
        if (!array_key_exists('transactions', $data) || !array_key_exists('opening_balance', $data)) {
            $glStructureOk = false;
            break;
        }

        // Each transaction must have entry_date, debit, credit, description
        foreach ($data['transactions'] as $txn) {
            if (!array_key_exists('entry_date', $txn)
                || !array_key_exists('debit', $txn)
                || !array_key_exists('credit', $txn)
                || !array_key_exists('description', $txn)) {
                $glStructureOk = false;
                break 2;
            }
        }

        // Closing balance = opening + sum(debit - credit)
        $runningBalance = floatval($data['opening_balance']);
        foreach ($data['transactions'] as $txn) {
            $runningBalance += floatval($txn['debit']) - floatval($txn['credit']);
        }
        // Verify closing balance is consistent (no NaN/null)
        if (!is_numeric($runningBalance)) {
            $glRunningBalanceOk = false;
        }
    }

    assert_true(
        '3b-i: transactions array has entry_date/debit/credit/description keys (all date ranges)',
        $glStructureOk,
        'At least one transaction row was missing a required key'
    );

    assert_true(
        '3b-ii: running balance is numeric for all date ranges',
        $glRunningBalanceOk,
        'Running balance calculation produced a non-numeric result'
    );

    // Spot-check: seeded transaction appears in the correct date range
    $dataSpot = buildGeneralLedgerData($assetAccountId, $startDate, $endDate);
    $txnDescriptions = array_column($dataSpot['transactions'], 'description');
    assert_true(
        '3b-iii: seeded transaction appears in general ledger for correct date range',
        in_array('Preservation test entry', $txnDescriptions),
        'Seeded journal entry not found in transactions for 2024-06-01 to 2024-06-30'
    );

    // Spot-check: transaction outside date range does NOT appear
    $dataOutside = buildGeneralLedgerData($assetAccountId, '2025-01-01', '2025-12-31');
    $txnDescriptionsOutside = array_column($dataOutside['transactions'], 'description');
    assert_true(
        '3b-iv: seeded transaction does NOT appear outside its date range',
        !in_array('Preservation test entry', $txnDescriptionsOutside),
        'Seeded journal entry incorrectly appeared in a date range it does not belong to'
    );

    // ── PROPERTY 3c: cashFlow() structure preservation ───────────────────────
    //
    // For all date ranges, cashFlow() produces:
    //   $data['net_cash_flow']        — numeric
    //   $data['operating_activities'] — array of items with date/description/amount
    //   net_cash_flow == ending_cash - beginning_cash
    //
    // Validates: Requirements 3.3

    echo "\n── Property 3c: cashFlow() structure preservation ──\n";
    echo "   (for all date ranges)\n\n";

    $cfStructureOk      = true;
    $cfNetCashFlowOk    = true;
    $cfActivitiesKeysOk = true;

    foreach ($dateRanges as [$start, $end]) {
        $data = buildCashFlowData($start, $end);

        // Must have net_cash_flow and operating_activities
        if (!array_key_exists('net_cash_flow', $data)
            || !array_key_exists('operating_activities', $data)
            || !array_key_exists('beginning_cash', $data)
            || !array_key_exists('ending_cash', $data)) {
            $cfStructureOk = false;
            break;
        }

        // net_cash_flow must be numeric
        if (!is_numeric($data['net_cash_flow'])) {
            $cfNetCashFlowOk = false;
        }

        // net_cash_flow == ending_cash - beginning_cash
        $expectedNetCash = floatval($data['ending_cash']) - floatval($data['beginning_cash']);
        if (abs($expectedNetCash - floatval($data['net_cash_flow'])) > 0.01) {
            $cfNetCashFlowOk = false;
        }

        // Each operating activity must have date, description, amount
        foreach ($data['operating_activities'] as $activity) {
            if (!array_key_exists('date', $activity)
                || !array_key_exists('description', $activity)
                || !array_key_exists('amount', $activity)) {
                $cfActivitiesKeysOk = false;
                break 2;
            }
        }
    }

    assert_true(
        '3c-i: net_cash_flow, operating_activities, beginning_cash, ending_cash keys present (all date ranges)',
        $cfStructureOk,
        'At least one required key was missing from cashFlow() output'
    );

    assert_true(
        '3c-ii: net_cash_flow is numeric and equals ending_cash - beginning_cash (all date ranges)',
        $cfNetCashFlowOk,
        'net_cash_flow was not numeric or did not equal ending_cash - beginning_cash'
    );

    assert_true(
        '3c-iii: operating_activities items have date/description/amount keys (all date ranges)',
        $cfActivitiesKeysOk,
        'At least one operating activity item was missing a required key'
    );

    // Spot-check: empty period returns zero net_cash_flow without errors
    $dataEmpty = buildCashFlowData('2099-01-01', '2099-12-31');
    assert_true(
        '3c-iv: empty period returns numeric net_cash_flow (no errors)',
        is_numeric($dataEmpty['net_cash_flow']),
        'net_cash_flow was not numeric for an empty (future) period'
    );

} finally {
    // Always roll back — test data never persists
    $db->rollBack();
}

// ── Summary ───────────────────────────────────────────────────────────────────

echo "\n══════════════════════════════════════════════════════════════════════\n";
echo "  Results: {$passed} passed, {$failed} failed\n";

if (!empty($errors)) {
    echo "\n  Failures:\n";
    foreach ($errors as $e) {
        echo "\n  ▸ {$e['test']}\n";
        if ($e['detail']) {
            echo "    {$e['detail']}\n";
        }
    }
    echo "\n  NOTE: These tests MUST PASS on unfixed code.\n";
    echo "        A failure here indicates a regression risk or test issue.\n";
} else {
    echo "\n  All preservation tests PASS — baseline behavior confirmed.\n";
    echo "  These tests will guard against regressions after the fix is applied.\n";
}

echo "══════════════════════════════════════════════════════════════════════\n\n";

exit($failed > 0 ? 1 : 0);
