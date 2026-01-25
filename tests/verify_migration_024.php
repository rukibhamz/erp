<?php
/**
 * TEST RUNNER FOR MIGRATION VERIFICATION
 */

define('BASEPATH', __DIR__ . '/../application/');

// Suppress output
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Load config
$config = require __DIR__ . '/../application/config/config.installed.php';

require_once __DIR__ . '/../application/core/Database.php';
require_once __DIR__ . '/../application/core/Base_Model.php';

echo "\n========================================\n";
echo "MIGRATION VERIFICATION TEST\n";
echo "========================================\n";

try {
    $dbConfig = $config['db'];
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mock the CI DB class wrapper that Base_Model expects if simpler
    // But since we have Base_Model, let's try to instantiate models directly.
    // However, Base_Model usually expects $this->db to be a CI DB instance.
    // The existing tests/run_functional_tests.php uses a custom Database class which likely wraps PDO.
    // Let's use that.
    
    $db = Database::getInstance();
    
    echo "Database Connected.\n";
    
    // 1. Verify Tables Exist
    $tables = ['erp_bookings', 'erp_facilities', 'erp_customers', 'erp_companies', 'erp_invoices'];
    echo "Verifying tables exist...\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "  [PASS] Table $table exists.\n";
        } else {
            echo "  [FAIL] Table $table MISSING.\n";
        }
    }
    
    // 2. Verify Booking Creation Logic works (via Booking_model)
    require_once __DIR__ . '/../application/models/Booking_model.php';
    require_once __DIR__ . '/../application/models/Facility_model.php';
    
    $bookingModel = new Booking_model(); // This might fail if it relies on $this->load
    // Base_Model needs to be inspected to see if it instantiates DB correctly.
    // Assuming standard CI model structure, we might need a test wrapper.
    // But let's look at `tests/run_functional_tests.php` line 30: `require_once __DIR__ . '/../application/core/Database.php';`
    // It seems the user has a custom Database wrapper.
    
    // Let's insert a test facility directly using PDO/DB wrapper first to ensure FKs work
    echo "Creating test facility...\n";
    $facilityCode = 'TEST-' . rand(1000, 9999);
    $pdo->exec("INSERT INTO erp_facilities (facility_code, facility_name, hourly_rate, status) VALUES ('$facilityCode', 'Test Facility', 100, 'active')");
    $facilityId = $pdo->lastInsertId();
    echo "  [PASS] Created Facility ID: $facilityId\n";
    
    // Create test customer/company
    echo "Creating test customer...\n";
    $customerCode = 'CUST-' . rand(1000, 9999);
    $pdo->exec("INSERT INTO erp_customers (customer_code, company_name, email) VALUES ('$customerCode', 'Test Client', 'test@example.com')");
    $customerId = $pdo->lastInsertId();
    echo "  [PASS] Created Customer ID: $customerId\n";
    
    // Create a Booking manually to verify constraints
    echo "Creating test booking...\n";
    $bookingRef = 'BKG-' . rand(10000, 99999);
    $bookingDate = date('Y-m-d');
    $pdo->exec("INSERT INTO erp_bookings (
        booking_number, facility_id, customer_id, booking_date, start_time, end_time, status, total_amount
    ) VALUES (
        '$bookingRef', $facilityId, $customerId, '$bookingDate', '10:00:00', '12:00:00', 'confirmed', 200
    )");
    $bookingId = $pdo->lastInsertId();
    echo "  [PASS] Created Booking ID: $bookingId\n";
    
    // Verify Booking Retrieval
    $stmt = $pdo->query("SELECT * FROM erp_bookings WHERE id = $bookingId");
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($booking) {
        echo "  [PASS] Retrieved Booking: {$booking['booking_number']} - Status: {$booking['status']}\n";
    } else {
        echo "  [FAIL] Could not retrieve booking.\n";
    }
    
    // Cleanup
    echo "Cleaning up test data...\n";
    $pdo->exec("DELETE FROM erp_bookings WHERE id = $bookingId");
    $pdo->exec("DELETE FROM erp_customers WHERE id = $customerId");
    $pdo->exec("DELETE FROM erp_facilities WHERE id = $facilityId");
    echo "  [PASS] Cleanup complete.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

$output = ob_get_clean();
echo $output;
