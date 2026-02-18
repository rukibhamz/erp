<?php
/**
 * Diagnostic: Check why bookings don't appear in booking module.
 * Run via browser: https://yourdomain.com/diagnose_bookings.php
 */
header('Content-Type: text/plain; charset=utf-8');

$configFile = __DIR__ . '/application/config/config.installed.php';
if (!defined('BASEPATH')) define('BASEPATH', true);
$config = require $configFile;
$db = $config['db'];

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset=" . ($db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $prefix = $db['dbprefix'] ?? 'erp_';

    echo "=== BOOKINGS TABLE ===" . PHP_EOL;
    
    // Check if bookings table exists
    $tables = $pdo->query("SHOW TABLES LIKE '{$prefix}bookings'")->fetchAll();
    if (empty($tables)) {
        echo "ERROR: {$prefix}bookings table does NOT exist!" . PHP_EOL;
        exit;
    }
    echo "Table {$prefix}bookings exists." . PHP_EOL . PHP_EOL;

    // Count all bookings
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM {$prefix}bookings")->fetch(PDO::FETCH_ASSOC);
    echo "Total bookings in DB: " . $count['cnt'] . PHP_EOL . PHP_EOL;

    // Show all bookings
    $bookings = $pdo->query("SELECT id, booking_number, booking_date, start_time, end_time, 
        customer_name, status, payment_status, total_amount, space_id, facility_id, 
        booking_source, created_at 
        FROM {$prefix}bookings ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($bookings)) {
        echo "NO BOOKINGS FOUND!" . PHP_EOL;
    } else {
        foreach ($bookings as $b) {
            echo "  #{$b['id']}: {$b['booking_number']} | date={$b['booking_date']} | {$b['start_time']}-{$b['end_time']} | " .
                 "status={$b['status']} | pay={$b['payment_status']} | total={$b['total_amount']} | " .
                 "space_id={$b['space_id']} | facility_id={$b['facility_id']} | " .
                 "source={$b['booking_source']} | created={$b['created_at']}" . PHP_EOL;
        }
    }

    // Check what the listing query would return for current month
    echo PHP_EOL . "=== LISTING QUERY (current month) ===" . PHP_EOL;
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    echo "Date range: $startDate to $endDate" . PHP_EOL;
    
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
    
    echo "Bookings matching listing query: " . count($results) . PHP_EOL;
    foreach ($results as $r) {
        echo "  #{$r['id']}: {$r['booking_number']} | date={$r['booking_date']} | status={$r['status']} | facility={$r['facility_name']}" . PHP_EOL;
    }
    
    // Check spaces and facilities
    echo PHP_EOL . "=== SPACES ===" . PHP_EOL;
    $spaces = $pdo->query("SELECT id, space_name, space_number FROM {$prefix}spaces LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($spaces)) {
        echo "NO SPACES FOUND!" . PHP_EOL;
    } else {
        foreach ($spaces as $s) {
            echo "  #{$s['id']}: {$s['space_name']} ({$s['space_number']})" . PHP_EOL;
        }
    }
    
    echo PHP_EOL . "=== FACILITIES ===" . PHP_EOL;
    $facilities = $pdo->query("SELECT id, facility_name, facility_code FROM {$prefix}facilities LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($facilities)) {
        echo "NO FACILITIES FOUND!" . PHP_EOL;
    } else {
        foreach ($facilities as $f) {
            echo "  #{$f['id']}: {$f['facility_name']} ({$f['facility_code']})" . PHP_EOL;
        }
    }
    
    // Check customer_portal_users columns
    echo PHP_EOL . "=== CUSTOMER PORTAL USERS ===" . PHP_EOL;
    $cpuCols = $pdo->query("SHOW COLUMNS FROM {$prefix}customer_portal_users")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cpuCols, 'Field');
    $requiredCols = ['password_reset_token', 'password_reset_expires'];
    foreach ($requiredCols as $col) {
        echo "  $col: " . (in_array($col, $colNames) ? 'EXISTS' : 'MISSING') . PHP_EOL;
    }
    
    // Check transactions
    echo PHP_EOL . "=== TRANSACTIONS ===" . PHP_EOL;
    $txns = $pdo->query("SELECT COUNT(*) as cnt FROM {$prefix}transactions")->fetch(PDO::FETCH_ASSOC);
    echo "Total transactions: " . $txns['cnt'] . PHP_EOL;
    
    $recentTxns = $pdo->query("SELECT id, transaction_number, account_id, description, debit_amount, credit_amount, 
        reference, status, created_at FROM {$prefix}transactions ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recentTxns as $t) {
        echo "  #{$t['id']}: {$t['transaction_number']} | acct={$t['account_id']} | {$t['description']} | " .
             "DR={$t['debit_amount']} CR={$t['credit_amount']} | ref={$t['reference']} | status={$t['status']}" . PHP_EOL;
    }
    
    // Check for errors in PHP error log
    echo PHP_EOL . "=== RECENT DEBUG LOGS ===" . PHP_EOL;
    $logFile = __DIR__ . '/logs/debug_wizard_log.txt';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $lastLines = array_slice($lines, -20);
        echo implode('', $lastLines);
    } else {
        echo "No debug wizard log found." . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage() . PHP_EOL;
}
