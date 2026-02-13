<?php
/**
 * Fix Pending Bookings Script
 * Updates bookings that have successful payment transactions but weren't confirmed
 */

// Define constants
define('ROOTPATH', __DIR__ . '/');
define('BASEPATH', __DIR__ . '/system/');
define('APPPATH', __DIR__ . '/application/');

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #16ff16; padding: 20px;'>\n";
echo "===========================================\n";
echo "FIX PENDING BOOKINGS\n";
echo "===========================================\n\n";

// Database connection
try {
    $configPaths = [
        ROOTPATH . 'application/config/config.installed.php',
        ROOTPATH . 'application/config/config.php',
    ];
    
    $configFile = null;
    foreach ($configPaths as $path) {
        if (file_exists($path)) {
            $configFile = $path;
            break;
        }
    }
    
    if (!$configFile) die("Config not found");
    
    $config = require $configFile;
    $dbConfig = $config['db'] ?? $config['database'];
    
    $host = $dbConfig['hostname'] ?? $dbConfig['host'] ?? 'localhost';
    $dbName = $dbConfig['database'] ?? '';
    $username = $dbConfig['username'] ?? '';
    $password = $dbConfig['password'] ?? '';
    $prefix = $dbConfig['dbprefix'] ?? 'erp_';
    
    $pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    die("✗ DB Error: " . $e->getMessage());
}

// Find all bookings with successful transactions but not confirmed
echo "FINDING MISMATCHED BOOKINGS...\n";
echo "-------------------------------------------\n";

$sql = "SELECT 
    t.transaction_ref,
    t.amount as tx_amount,
    t.status as tx_status,
    b.id as booking_id,
    b.booking_number,
    b.status as booking_status,
    b.payment_status,
    b.total_amount,
    b.paid_amount,
    b.customer_email
FROM {$prefix}payment_transactions t
JOIN {$prefix}space_bookings b ON t.reference_id = b.id
WHERE t.status = 'success' 
AND t.payment_type = 'booking_payment'
AND (b.status != 'confirmed' OR b.payment_status != 'paid')
ORDER BY t.created_at DESC";

$stmt = $pdo->query($sql);
$mismatched = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($mismatched)) {
    echo "✓ No mismatched bookings found - all synced!\n";
} else {
    echo "Found " . count($mismatched) . " bookings to fix:\n\n";
    
    foreach ($mismatched as $row) {
        echo "Booking #{$row['booking_number']} (ID: {$row['booking_id']})\n";
        echo "  Transaction: {$row['transaction_ref']} = {$row['tx_status']}\n";
        echo "  Booking Status: {$row['booking_status']} / {$row['payment_status']}\n";
        echo "  Amount: {$row['tx_amount']} / Total: {$row['total_amount']}\n";
        echo "  Customer: {$row['customer_email']}\n";
        
        // Check if we should fix
        if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
            $updateSql = "UPDATE {$prefix}space_bookings SET 
                status = 'confirmed',
                payment_status = 'paid',
                paid_amount = ?,
                balance_amount = 0,
                payment_verified_at = NOW(),
                confirmed_at = NOW()
            WHERE id = ?";
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$row['tx_amount'], $row['booking_id']]);
            
            echo "  ✓ FIXED!\n";
        } else {
            echo "  → Add ?fix=yes to URL to fix\n";
        }
        echo "\n";
    }
}

echo "\n===========================================\n";
echo "END\n";
echo "===========================================\n";
echo "</pre>\n";
