<?php
// Comprehensive debug script for Booking Wizard
// This checks if recent bookings exist and if there are any in-flight issues
define('BASEPATH', __DIR__ . '/application/');
$config = require 'application/config/config.installed.php';
$dbConfig = $config['db'];

try {
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Booking Wizard Full Debug</h1>";
    
    // 1. Check all bookings
    echo "<h2>1. All Bookings in erp_space_bookings</h2>";
    $stmt = $pdo->query("SELECT id, booking_number, customer_name, customer_email, status, created_at FROM erp_space_bookings ORDER BY id DESC LIMIT 10");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>ID</th><th>Number</th><th>Customer</th><th>Email</th><th>Status</th><th>Created</th></tr>";
    foreach ($bookings as $b) {
        echo "<tr><td>{$b['id']}</td><td>{$b['booking_number']}</td><td>{$b['customer_name']}</td><td>{$b['customer_email']}</td><td>{$b['status']}</td><td>{$b['created_at']}</td></tr>";
    }
    echo "</table>";
    echo "<p><strong>Total: " . count($bookings) . " bookings shown</strong></p>";
    
    // 2. Check auto-increment to see if bookings were created then deleted/rolled back
    echo "<h2>2. Auto-Increment Check (Gaps = Rollbacks)</h2>";
    $stmt = $pdo->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbConfig['database']}' AND TABLE_NAME = 'erp_space_bookings'");
    $autoInc = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT MAX(id) FROM erp_space_bookings");
    $maxId = $stmt->fetchColumn() ?: 0;
    echo "Next Auto-Increment: $autoInc <br>";
    echo "Current Max ID: $maxId <br>";
    if ($autoInc > $maxId + 1) {
        echo "<span style='color:red'><strong>GAP DETECTED!</strong> IDs " . ($maxId + 1) . " to " . ($autoInc - 1) . " were rolled back or deleted.</span>";
    } else {
        echo "<span style='color:green'>No gaps detected.</span>";
    }
    
    // 3. Check Customers
    echo "<h2>3. Recent Customers (erp_customers)</h2>";
    $stmt = $pdo->query("SELECT id, customer_code, company_name, email, created_at FROM erp_customers ORDER BY id DESC LIMIT 5");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($customers)) {
        echo "<p>No customers found.</p>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Code</th><th>Name</th><th>Email</th><th>Created</th></tr>";
        foreach ($customers as $c) {
            echo "<tr><td>{$c['id']}</td><td>{$c['customer_code']}</td><td>{$c['company_name']}</td><td>{$c['email']}</td><td>{$c['created_at']}</td></tr>";
        }
        echo "</table>";
    }
    
    // 4. Check Invoices
    echo "<h2>4. Recent Invoices (erp_invoices)</h2>";
    $stmt = $pdo->query("SELECT id, invoice_number, customer_id, total_amount, status, created_at FROM erp_invoices ORDER BY id DESC LIMIT 5");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($invoices)) {
        echo "<p>No invoices found.</p>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Number</th><th>Customer</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
        foreach ($invoices as $i) {
            echo "<tr><td>{$i['id']}</td><td>{$i['invoice_number']}</td><td>{$i['customer_id']}</td><td>{$i['total_amount']}</td><td>{$i['status']}</td><td>{$i['created_at']}</td></tr>";
        }
        echo "</table>";
    }
    
    // 5. Check debug log files
    echo "<h2>5. Debug Log Files</h2>";
    $logs = ['debug_wizard_log.txt', 'debug_invoice.txt', 'debug_model_error.txt'];
    foreach ($logs as $log) {
        $path = __DIR__ . '/' . $log;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $lines = explode("\n", $content);
            $lastLines = array_slice($lines, -10);
            echo "<h3>$log (last 10 lines)</h3>";
            echo "<pre>" . htmlspecialchars(implode("\n", $lastLines)) . "</pre>";
        } else {
            echo "<p>$log: Not found</p>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
