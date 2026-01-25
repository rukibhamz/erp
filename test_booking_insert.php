<?php
// Standalone script to test DB insert
define('BASEPATH', __DIR__ . '/application/');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');

// Load config - prefer config.installed.php if it exists
$configFile = BASEPATH . 'config/config.installed.php';
if (!file_exists($configFile)) {
    $configFile = BASEPATH . 'config/config.php';
}

if (!file_exists($configFile)) {
    die("Configuration file not found.\n");
}

$config = require $configFile;
$db = $config['db'];

try {
    $dsn = "mysql:host={$db['hostname']};dbname={$db['database']};charset={$db['charset']}";

    $pdo = new PDO($dsn, $db['username'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to DB.\n";
    
    // 1. Check columns
    $stmt = $pdo->query("SHOW COLUMNS FROM erp_bookings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(', ', $columns) . "\n\n";
    
    // 2. Prepare Insert Data (mimicking Booking_wizard)
    $data = [
        'booking_number' => 'TEST-' . time(),
        'facility_id' => 1, // Assume facility 1 exists, if not we might get FK error
        'customer_name' => 'Test User',
        'customer_email' => 'test@example.com',
        'customer_phone' => '1234567890',
        'customer_address' => 'Test Address',
        'booking_date' => date('Y-m-d'),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'duration_hours' => 1,
        'number_of_guests' => 1,
        'booking_type' => 'hourly',
        'base_amount' => 1000,
        'subtotal' => 1000,
        'discount_amount' => 0,
        'security_deposit' => 0,
        'total_amount' => 1000,
        'paid_amount' => 0,
        'balance_amount' => 1000,
        'currency' => 'NGN',
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'payment_plan' => 'full',
        'promo_code' => null,
        'booking_notes' => 'Test Note',
        'special_requests' => 'Test Request',
        'booking_source' => 'online',
        'is_recurring' => 0,
        'recurring_pattern' => null,
        'recurring_end_date' => null,
        'created_by' => null // Null for guest
    ];
    
    // Filter data to only include existing columns to avoid "Unknown column" error
    // This mimics AutoMigration behavior partially, but manual insert is stricter
    $insertData = [];
    foreach ($data as $key => $val) {
        if (in_array($key, $columns)) {
            $insertData[$key] = $val;
        } else {
            echo "Skipping column '$key' (not in DB)\n";
        }
    }
    
    // Also check for required columns that match current data keys (ignore id, created_at etc)
    // ...
    
    $fields = array_keys($insertData);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO erp_bookings (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    echo "SQL: $sql\n";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($insertData));
    
    echo "Insert Successful! ID: " . $pdo->lastInsertId() . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
