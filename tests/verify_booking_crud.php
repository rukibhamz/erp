<?php
/**
 * VERIFY BOOKING CRUD OPERATIONS
 * 
 * Tests the complete lifecycle of a booking: Create, Read, Update, Delete.
 * Usage: php tests/verify_booking_crud.php
 */

define('BASEPATH', __DIR__ . '/../application/');

// Settings
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Dependencies
require_once __DIR__ . '/../application/config/config.php';
require_once __DIR__ . '/../application/core/Database.php';
require_once __DIR__ . '/../application/core/Base_Model.php';
require_once __DIR__ . '/../application/models/Booking_model.php';
require_once __DIR__ . '/../application/models/Facility_model.php';
require_once __DIR__ . '/../application/models/Space_model.php';

// Initialize DB
try {
    $db = Database::getInstance();
    $db->beginTransaction(); // rollback at end
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}

$results = ['passed' => 0, 'failed' => 0];

function assert_true($condition, $message) {
    global $results;
    if ($condition) {
        $results['passed']++;
        echo " [PASS] $message\n";
    } else {
        $results['failed']++;
        echo " [FAIL] $message\n";
    }
}

echo "\n--- STARTING BOOKING CRUD VERIFICATION ---\n";

try {
    // 1. SETUP PREREQUISITES
    echo " > Setting up dummy facility, space, and customer...\n";
    
    // Create Facility
    $facilityData = [
        'facility_name' => 'CRUD Test Facility',
        'facility_code' => 'TEST-FAC-' . uniqid(),
        'status' => 'active'
    ];
    $facilityId = $db->insert('facilities', $facilityData);
    assert_true($facilityId > 0, "Created Facility ID: $facilityId");
    
    // Create Space
    $spaceData = [
        'facility_id' => $facilityId,
        'space_name' => 'CRUD Test Space',
        'status' => 'active',
        'capacity' => 10,
        'is_bookable' => 1
    ];
    $spaceId = $db->insert('spaces', $spaceData);
    assert_true($spaceId > 0, "Created Space ID: $spaceId");
    
    // Create Customer (User)
    $customerData = [
        'username' => 'testuser_' . uniqid(),
        'email' => 'test_' . uniqid() . '@example.com',
        'role' => 'customer',
        'status' => 'active',
        'password' => '$2y$10$dummyhashforverificationpurposesonly'
    ];
    $customerId = $db->insert('users', $customerData);
    assert_true($customerId > 0, "Created Customer ID: $customerId");
    
    // 2. CREATE BOOKING
    echo "\n > Testing CREATE operation...\n";
    $bookingModel = new Booking_model();
    
    $bookingNumber = 'BKG-TEST-' . time();
    $createData = [
        'booking_number' => $bookingNumber,
        'created_by' => $customerId,
        'facility_id' => $facilityId,
        'customer_email' => $customerData['email'],
        'customer_name' => 'Test User',
        'booking_date' => date('Y-m-d', strtotime('+5 days')),
        'start_time' => '10:00:00',
        'end_time' => '12:00:00',
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'total_amount' => 500.00
    ];
    
    // Assuming create() method exists on Booking_model (via Base_Model)
    $bookingId = $bookingModel->create($createData);
    
    assert_true($bookingId > 0, "Booking created with ID: $bookingId");
    
    // 3. READ BOOKING
    echo "\n > Testing READ operation...\n";
    $fetched = $bookingModel->getById($bookingId);
    
    assert_true(!empty($fetched), "Fetched booking data successfully");
    assert_true($fetched['booking_number'] === $bookingNumber, "Booking number matches");
    assert_true($fetched['status'] === 'pending', "Status is pending");
    assert_true($fetched['created_by'] == $customerId, "Created By ID matches");
    
    // 4. UPDATE BOOKING
    echo "\n > Testing UPDATE operation...\n";
    $updateData = [
        'status' => 'confirmed',
        'payment_status' => 'partial'
    ];
    
    $updated = $bookingModel->update($bookingId, $updateData);
    assert_true($updated, "Update method returned success");
    
    // Verify update
    $refetched = $bookingModel->getById($bookingId);
    assert_true($refetched['status'] === 'confirmed', "Status updated to confirmed");
    assert_true($refetched['payment_status'] === 'partial', "Payment status updated to partial");
    
    // 5. DELETE BOOKING
    echo "\n > Testing DELETE operation...\n";
    $deleted = $bookingModel->delete($bookingId);
    assert_true($deleted, "Delete method returned success");
    
    // Verify deletion
    $check = $bookingModel->getById($bookingId);
    assert_true(empty($check), "Booking no longer exists in DB");
    
} catch (Exception $e) {
    echo " [ERROR] Exception: " . $e->getMessage() . "\n";
    echo " Trace: " . $e->getTraceAsString() . "\n";
    $results['failed']++;
}

echo "\n--- SUMMARY ---\n";
echo "Passed: {$results['passed']}\n";
echo "Failed: {$results['failed']}\n";

$db->rollBack();
echo "\n(Database transaction rolled back - no persistent changes)\n";

if ($results['failed'] > 0) exit(1);
exit(0);
