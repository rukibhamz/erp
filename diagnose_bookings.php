<?php
/**
 * Enhanced diagnostic for all three issues:
 * 1. Bookings not showing
 * 2. Password reset not working
 * 3. Transaction column names
 * Run via browser: https://yourdomain.com/diagnose_bookings.php
 */
header('Content-Type: text/plain; charset=utf-8');

$configFile = __DIR__ . '/application/config/config.installed.php';
if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/application/../');
$config = require $configFile;
$db = $config['db'];

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset=" . ($db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $prefix = $db['dbprefix'] ?? 'erp_';
    $dbName = $db['database'];

    // ========== BOOKINGS ==========
    echo "=== BOOKINGS ===" . PHP_EOL;
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM {$prefix}bookings")->fetch(PDO::FETCH_ASSOC);
    echo "Total: " . $count['cnt'] . PHP_EOL;

    $bookings = $pdo->query("SELECT id, booking_number, booking_date, start_time, end_time, 
        customer_name, status, payment_status, total_amount, space_id, facility_id, 
        booking_source, created_at 
        FROM {$prefix}bookings ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($bookings as $b) {
        echo "  #{$b['id']}: {$b['booking_number']} | date={$b['booking_date']} | {$b['start_time']}-{$b['end_time']} | " .
             "status={$b['status']} | pay={$b['payment_status']} | total={$b['total_amount']} | " .
             "space={$b['space_id']} fac={$b['facility_id']} | src={$b['booking_source']}" . PHP_EOL;
    }

    // ========== LISTING QUERY ==========
    echo PHP_EOL . "=== LISTING QUERY (this month) ===" . PHP_EOL;
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    echo "Range: $startDate to $endDate" . PHP_EOL;
    
    $listed = $pdo->prepare("SELECT b.id, b.booking_number, b.booking_date, b.status,
        COALESCE(s.space_name, f.facility_name, 'Unknown Space') as facility_name
        FROM {$prefix}bookings b
        LEFT JOIN {$prefix}spaces s ON b.space_id = s.id
        LEFT JOIN {$prefix}facilities f ON b.facility_id = f.id
        WHERE (
            (b.booking_date >= ? AND b.booking_date <= ?)
            OR (b.booking_date <= ? AND DATE_ADD(b.booking_date, INTERVAL TIME_TO_SEC(b.end_time) - TIME_TO_SEC(b.start_time) SECOND) >= ?)
        )
        AND b.status NOT IN ('cancelled', 'refunded', 'no_show')
        ORDER BY b.booking_date, b.start_time");
    $listed->execute([$startDate, $endDate, $startDate, $startDate]);
    $results = $listed->fetchAll(PDO::FETCH_ASSOC);
    echo "Results: " . count($results) . PHP_EOL;
    foreach ($results as $r) {
        echo "  #{$r['id']}: {$r['booking_number']} | {$r['booking_date']} | {$r['status']} | {$r['facility_name']}" . PHP_EOL;
    }
    
    // ========== TRANSACTION COLUMNS ==========
    echo PHP_EOL . "=== TRANSACTION TABLE COLUMNS ===" . PHP_EOL;
    $cols = $pdo->query("SHOW COLUMNS FROM {$prefix}transactions")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']} ({$col['Type']})" . PHP_EOL;
    }

    // Transaction data
    echo PHP_EOL . "=== TRANSACTIONS ===" . PHP_EOL;
    $txnCount = $pdo->query("SELECT COUNT(*) as cnt FROM {$prefix}transactions")->fetch(PDO::FETCH_ASSOC);
    echo "Total: " . $txnCount['cnt'] . PHP_EOL;
    // Dynamically detect correct column names
    $colNames = array_column($cols, 'Field');
    $debitCol = in_array('debit', $colNames) ? 'debit' : (in_array('debit_amount', $colNames) ? 'debit_amount' : 'debit');
    $creditCol = in_array('credit', $colNames) ? 'credit' : (in_array('credit_amount', $colNames) ? 'credit_amount' : 'credit');
    
    $recentTxns = $pdo->query("SELECT id, transaction_number, account_id, description, 
        `$debitCol` as debit_val, `$creditCol` as credit_val, 
        reference_type, reference_id, status, created_at FROM {$prefix}transactions ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recentTxns as $t) {
        echo "  #{$t['id']}: {$t['transaction_number']} | acct={$t['account_id']} | {$t['description']} | " .
             "DR={$t['debit_val']} CR={$t['credit_val']} | ref_type={$t['reference_type']} ref_id={$t['reference_id']} | {$t['status']}" . PHP_EOL;
    }

    // ========== EMAIL CONFIG ==========
    echo PHP_EOL . "=== EMAIL CONFIG ===" . PHP_EOL;
    $emailSettings = $config['email'] ?? [];
    if (empty($emailSettings)) {
        echo "NO EMAIL SETTINGS in config.installed.php!" . PHP_EOL;
        echo "Password reset emails will use PHP mail() as fallback." . PHP_EOL;
        echo "If PHP mail() is disabled on your host, emails won't be sent." . PHP_EOL;
    } else {
        echo "SMTP Host: " . ($emailSettings['smtp_host'] ?? 'not set') . PHP_EOL;
        echo "SMTP User: " . ($emailSettings['smtp_username'] ?? 'not set') . PHP_EOL;
        echo "SMTP Pass: " . (!empty($emailSettings['smtp_password']) ? '***set***' : 'not set') . PHP_EOL;
        echo "From Email: " . ($emailSettings['from_email'] ?? 'not set') . PHP_EOL;
    }
    
    // Check PHP mail() availability
    echo "PHP mail(): " . (function_exists('mail') ? 'available' : 'NOT AVAILABLE') . PHP_EOL;

    // ========== CUSTOMER PORTAL USERS ==========
    echo PHP_EOL . "=== CUSTOMER PORTAL USERS ===" . PHP_EOL;
    $cpuCount = $pdo->query("SELECT COUNT(*) as cnt FROM {$prefix}customer_portal_users")->fetch(PDO::FETCH_ASSOC);
    echo "Total: " . $cpuCount['cnt'] . PHP_EOL;
    // Detect columns in customer_portal_users
    $cpCols = $pdo->query("SHOW COLUMNS FROM {$prefix}customer_portal_users")->fetchAll(PDO::FETCH_ASSOC);
    $cpColNames = array_column($cpCols, 'Field');
    $hasIsGuest = in_array('is_guest', $cpColNames);
    $hasResetToken = in_array('password_reset_token', $cpColNames);
    $hasResetExpires = in_array('password_reset_expires', $cpColNames);
    
    $selectCols = "id, email, first_name, last_name, status, created_at";
    if ($hasIsGuest) $selectCols .= ", is_guest";
    if ($hasResetToken) $selectCols .= ", password_reset_token";
    if ($hasResetExpires) $selectCols .= ", password_reset_expires";
    
    $cpUsers = $pdo->query("SELECT $selectCols FROM {$prefix}customer_portal_users ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cpUsers as $u) {
        $line = "  #{$u['id']}: {$u['email']} | {$u['first_name']} {$u['last_name']} | status={$u['status']}";
        if ($hasIsGuest) $line .= " | guest=" . (($u['is_guest'] ?? 0) ? 'Y' : 'N');
        if ($hasResetToken) $line .= " | reset_token=" . (!empty($u['password_reset_token']) ? 'SET' : 'none');
        if ($hasResetExpires) $line .= " | token_expires=" . ($u['password_reset_expires'] ?? 'none');
        echo $line . PHP_EOL;
    }
    
    echo "Columns: " . implode(', ', $cpColNames) . PHP_EOL;
    if (!$hasIsGuest) echo "  NOTE: 'is_guest' column is MISSING" . PHP_EOL;
    if (!$hasResetToken) echo "  NOTE: 'password_reset_token' column is MISSING" . PHP_EOL;
    if (!$hasResetExpires) echo "  NOTE: 'password_reset_expires' column is MISSING" . PHP_EOL;
    
    // ========== REQUIRED MODEL FILES ==========
    echo PHP_EOL . "=== MODEL FILES CHECK ===" . PHP_EOL;
    $requiredModels = [
        'Booking_model', 'Facility_model', 'Booking_payment_model', 
        'Transaction_model', 'Cash_account_model', 'Account_model',
        'Activity_model', 'Booking_resource_model', 'Booking_addon_model',
        'Addon_model', 'Promo_code_model', 'Cancellation_policy_model',
        'Payment_schedule_model', 'Booking_modification_model',
        'Location_model', 'Space_model', 'Customer_portal_user_model'
    ];
    foreach ($requiredModels as $model) {
        $path = __DIR__ . '/application/models/' . $model . '.php';
        echo "  $model: " . (file_exists($path) ? 'EXISTS' : 'MISSING !!!') . PHP_EOL;
    }

    // Check Transaction_service
    $svcPath = __DIR__ . '/application/services/Transaction_service.php';
    echo "  Transaction_service: " . (file_exists($svcPath) ? 'EXISTS' : 'MISSING (non-critical)') . PHP_EOL;
    
    // ========== PHP ERROR LOG ==========
    echo PHP_EOL . "=== RECENT PHP ERRORS ===" . PHP_EOL;
    $phpLog = ini_get('error_log');
    if ($phpLog && file_exists($phpLog)) {
        $lines = file($phpLog);
        $lastLines = array_slice($lines, -15);
        echo implode('', $lastLines);
    } else {
        echo "PHP error_log: $phpLog" . PHP_EOL;
        // Check common locations
        $commonLogs = [
            __DIR__ . '/logs/error.log',
            __DIR__ . '/logs/debug_wizard_log.txt',
            '/home/' . get_current_user() . '/logs/error.log',
        ];
        foreach ($commonLogs as $log) {
            if (file_exists($log)) {
                echo "Found log: $log" . PHP_EOL;
                $lines = file($log);
                $lastLines = array_slice($lines, -10);
                echo implode('', $lastLines);
            }
        }
    }
    
} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage() . PHP_EOL;
}
