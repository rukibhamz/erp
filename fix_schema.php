<?php
/**
 * Comprehensive schema fix for the booking flow.
 * Run via browser: https://yourdomain.com/fix_schema.php
 * Reads DB credentials from the application config.
 */
header('Content-Type: text/plain; charset=utf-8');

// Load DB config from app config
$configFile = __DIR__ . '/application/config/config.installed.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/application/config/config.php';
}
if (!defined('BASEPATH')) define('BASEPATH', true);
$config = require $configFile;
$db = $config['db'];

echo "Connecting to {$db['hostname']} / {$db['database']} as {$db['username']}..." . PHP_EOL;

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset=" . ($db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $prefix = $db['dbprefix'] ?? 'erp_';
    $dbName = $db['database'];

    echo "Connected successfully." . PHP_EOL . PHP_EOL;

    // Helper: check if column exists
    function columnExists($pdo, $dbName, $table, $column) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?");
        $stmt->execute([$dbName, $table, $column]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($r['cnt']) > 0;
    }

    // Define all required columns per table
    $fixes = [
        $prefix . 'customers' => [
            ['notes', "TEXT DEFAULT NULL"],
            ['customer_code', "VARCHAR(50) DEFAULT NULL"],
            ['customer_type_id', "INT(11) DEFAULT NULL"],
            ['contact_name', "VARCHAR(255) DEFAULT NULL"],
            ['payment_terms', "VARCHAR(50) DEFAULT 'Net 30'"],
            ['credit_limit', "DECIMAL(15,2) DEFAULT 0.00"],
            ['current_balance', "DECIMAL(15,2) DEFAULT 0.00"],
            ['created_by', "INT(11) DEFAULT NULL"],
        ],
        $prefix . 'invoices' => [
            ['payment_date', "DATE DEFAULT NULL"],
            ['payment_method', "VARCHAR(100) DEFAULT NULL"],
            ['tax_rate', "DECIMAL(15,2) DEFAULT 0.00"],
        ],
        $prefix . 'bookings' => [
            ['subtotal', "DECIMAL(15,2) DEFAULT 0.00"],
            ['tax_rate', "DECIMAL(15,2) DEFAULT 0.00"],
            ['invoice_id', "INT(11) DEFAULT NULL"],
            ['payment_plan', "VARCHAR(50) DEFAULT 'full'"],
            ['promo_code', "VARCHAR(50) DEFAULT NULL"],
            ['booking_source', "VARCHAR(20) DEFAULT 'admin'"],
            ['is_recurring', "TINYINT(1) DEFAULT 0"],
            ['recurring_pattern', "VARCHAR(20) DEFAULT NULL"],
            ['recurring_end_date', "DATE DEFAULT NULL"],
            ['customer_portal_user_id', "INT(11) DEFAULT NULL"],
        ],
    ];

    $added = 0;
    $existed = 0;
    $failed = 0;

    foreach ($fixes as $table => $columns) {
        echo "=== $table ===" . PHP_EOL;
        
        // Check if table exists first
        $tableCheck = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck->rowCount() === 0) {
            echo "  TABLE DOES NOT EXIST - skipping" . PHP_EOL;
            continue;
        }
        
        foreach ($columns as [$col, $def]) {
            if (!columnExists($pdo, $dbName, $table, $col)) {
                try {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
                    echo "  ADDED: $col" . PHP_EOL;
                    $added++;
                } catch (Exception $e) {
                    echo "  FAILED: $col - " . $e->getMessage() . PHP_EOL;
                    $failed++;
                }
            } else {
                echo "  EXISTS: $col" . PHP_EOL;
                $existed++;
            }
        }
    }

    echo PHP_EOL . "========================================" . PHP_EOL;
    echo "DONE: $added added, $existed already existed, $failed failed" . PHP_EOL;
    echo "========================================" . PHP_EOL;

    if ($added > 0) {
        echo PHP_EOL . "Schema updated successfully! You can now delete this file." . PHP_EOL;
    } else if ($failed === 0) {
        echo PHP_EOL . "All columns already exist. No changes needed." . PHP_EOL;
    }

} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage() . PHP_EOL;
    echo PHP_EOL . "Check your database credentials in application/config/config.installed.php" . PHP_EOL;
}
