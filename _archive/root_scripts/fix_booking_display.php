<?php
/**
 * Fix Booking Display Issues Script
 * 
 * This script fixes:
 * 1. Bookings with facility_id but no space_id (adds space_id)
 * 2. Syncs facility_id and space_id for consistency
 * 
 * Access this from your browser: http://localhost/erp/fix_booking_display.php
 */

// Set content type for browser
header('Content-Type: text/html; charset=utf-8');
echo "<html><head><title>Fix Booking Display</title><style>body{font-family:monospace;background:#1a1a2e;color:#e0e0e0;padding:20px;} pre{background:#16213e;padding:15px;border-radius:5px;overflow-x:auto;} h2{color:#00d4ff;} .success{color:#00ff88;} .error{color:#ff4444;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #333;padding:8px;text-align:left;}</style></head><body>";
echo "<h1>🔧 Fix Booking Display Issues</h1><pre>";

define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', BASEPATH);

// Load config - the file returns an array
$configFile = BASEPATH . 'config/config.installed.php';
if (!file_exists($configFile)) {
    $configFile = BASEPATH . 'config/config.php';
}

if (file_exists($configFile)) {
    $config = include($configFile);
} else {
    die("Could not find config file\n");
}

if (!is_array($config) || !isset($config['db'])) {
    die("Config file did not return expected array format\n");
}

// Get database credentials
$dbConfig = $config['db'];
$hostname = $dbConfig['hostname'] ?? $dbConfig['host'] ?? 'localhost';
$username = $dbConfig['username'] ?? 'root';
$password = $dbConfig['password'] ?? '';
$database = $dbConfig['database'] ?? 'erp';
$prefix = $dbConfig['dbprefix'] ?? $dbConfig['table_prefix'] ?? 'erp_';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully\n\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// 1. Check bookings table structure
echo "=== Checking Table Structure ===\n";
$stmt = $pdo->query("DESCRIBE `{$prefix}space_bookings`");
$columns = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $columns[] = $row['Field'];
}

$hasSpaceId = in_array('space_id', $columns);
$hasFacilityId = in_array('facility_id', $columns);

echo "Has space_id column: " . ($hasSpaceId ? 'YES' : 'NO') . "\n";
echo "Has facility_id column: " . ($hasFacilityId ? 'YES' : 'NO') . "\n\n";

// 2. Add missing columns if needed
if (!$hasFacilityId) {
    echo "Adding facility_id column...\n";
    $pdo->exec("ALTER TABLE `{$prefix}space_bookings` ADD COLUMN `facility_id` INT(11) NULL");
    echo "Done.\n";
}

// 3. Find bookings that need fixing
echo "\n=== Finding Bookings to Fix ===\n";

if ($hasSpaceId && $hasFacilityId) {
    $stmt = $pdo->query("SELECT id, booking_number, space_id, facility_id FROM `{$prefix}space_bookings` WHERE (space_id IS NULL OR facility_id IS NULL)");
    $bookingsToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($bookingsToFix) . " bookings with mismatched space_id/facility_id\n";
    
    foreach ($bookingsToFix as $b) {
        $spaceId = $b['space_id'] ?? $b['facility_id']; // Use whichever has a value
        
        if ($spaceId) {
            $pdo->exec("UPDATE `{$prefix}space_bookings` SET space_id = $spaceId, facility_id = $spaceId WHERE id = " . $b['id']);
            echo "Fixed booking #{$b['booking_number']} - set both IDs to $spaceId\n";
        }
    }
}

// 4. Check for bookings with N/A facility name
echo "\n=== Checking Bookings with Missing Facility Name ===\n";
$sql = "SELECT b.id, b.booking_number, b.space_id, b.facility_id,
               s.space_name, f.facility_name
        FROM `{$prefix}space_bookings` b
        LEFT JOIN `{$prefix}spaces` s ON b.space_id = s.id
        LEFT JOIN `{$prefix}facilities` f ON b.facility_id = f.id
        ORDER BY b.id DESC LIMIT 20";
$stmt = $pdo->query($sql);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nRecent Bookings:\n";
echo str_pad("ID", 5) . " | " . str_pad("Booking#", 12) . " | " . str_pad("SpaceID", 8) . " | " . str_pad("FacilityID", 10) . " | Space Name | Facility Name\n";
echo str_repeat("-", 100) . "\n";

foreach ($bookings as $b) {
    echo str_pad($b['id'], 5) . " | " 
       . str_pad($b['booking_number'] ?? 'N/A', 12) . " | " 
       . str_pad($b['space_id'] ?? 'NULL', 8) . " | " 
       . str_pad($b['facility_id'] ?? 'NULL', 10) . " | "
       . substr($b['space_name'] ?? 'N/A', 0, 20) . " | "
       . substr($b['facility_name'] ?? 'N/A', 0, 20) . "\n";
}

// 5. Show spaces/facilities tables for debugging
echo "\n=== Spaces Table ===\n";
try {
    $stmt = $pdo->query("SELECT id, space_name FROM `{$prefix}spaces` LIMIT 10");
    $spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($spaces as $s) {
        echo "ID: {$s['id']} - {$s['space_name']}\n";
    }
} catch (Exception $e) {
    echo "Spaces table error: " . $e->getMessage() . "\n";
}

echo "\n=== Facilities Table ===\n";
try {
    $stmt = $pdo->query("SELECT id, facility_name FROM `{$prefix}facilities` LIMIT 10");
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($facilities as $f) {
        echo "ID: {$f['id']} - {$f['facility_name']}\n";
    }
} catch (Exception $e) {
    echo "Facilities table error: " . $e->getMessage() . "\n";
}

// 6. Check customers created by bookings
echo "\n=== Recent Customers ===\n";
$stmt = $pdo->query("SELECT id, customer_code, company_name, email FROM `{$prefix}customers` ORDER BY id DESC LIMIT 10");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($customers as $c) {
    echo "ID: {$c['id']} - {$c['customer_code']} - {$c['company_name']} ({$c['email']})\n";
}

// 7. Check invoices
echo "\n=== Recent Invoices ===\n";
$stmt = $pdo->query("SELECT id, invoice_number, customer_id, reference, status, total_amount FROM `{$prefix}invoices` ORDER BY id DESC LIMIT 10");
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($invoices as $i) {
    echo "ID: {$i['id']} - {$i['invoice_number']} - Customer: {$i['customer_id']} - Ref: {$i['reference']} - Status: {$i['status']} - Amount: {$i['total_amount']}\n";
}

// 8. Check transactions
echo "\n=== Recent Transactions ===\n";
$stmt = $pdo->query("SELECT t.id, t.account_id, a.account_code, a.account_name, t.debit, t.credit, t.description, t.reference_type
                     FROM `{$prefix}transactions` t
                     LEFT JOIN `{$prefix}accounts` a ON t.account_id = a.id
                     ORDER BY t.id DESC LIMIT 15");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($transactions as $t) {
    $amount = $t['debit'] > 0 ? "DR: {$t['debit']}" : "CR: {$t['credit']}";
    echo "ID: {$t['id']} - {$t['account_code']} ({$t['account_name']}) - $amount - {$t['reference_type']} - {$t['description']}\n";
}

echo "\n=== Done! ===\n";
echo "</pre></body></html>";

