<?php
define('BASEPATH', __DIR__ . '/application/');
define('ROOTPATH', __DIR__ . '/');
require_once 'application/core/Database.php';
require_once 'application/core/Base_Model.php';
require_once 'application/models/Booking_model.php';
require_once 'application/models/Transaction_model.php';
require_once 'application/models/Account_model.php';
require_once 'application/models/Invoice_model.php';
require_once 'application/models/Facility_model.php';

$config = require 'application/config/config.installed.php';

echo "Starting Diagnostic Booking Flow...\n";

try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    
    // 1. Test Database Writability
    echo "Testing database writability (temporary table)...\n";
    $db->query("CREATE TEMPORARY TABLE test_write (id INT AUTO_INCREMENT PRIMARY KEY, val VARCHAR(255))");
    $db->query("INSERT INTO test_write (val) VALUES ('test')");
    $check = $db->fetchOne("SELECT * FROM test_write");
    if ($check && $check['val'] === 'test') {
        echo "✅ Database write successful.\n";
    } else {
        echo "❌ Database write failed.\n";
    }

    // 2. Simulate Booking Creation with Transaction
    echo "Simulating Booking Creation with Transaction...\n";
    $pdo = $db->getConnection();
    $pdo->beginTransaction();
    echo "Transaction started.\n";

    $bookingModel = new Booking_model();
    $bookingData = [
        'booking_number' => 'DIAG-' . time(),
        'facility_id' => 2, // Validated facility ID
        'customer_name' => 'Diagnostic User',
        'customer_email' => 'diag@example.com',
        'total_amount' => 100.00,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];

    echo "Inserting booking...\n";
    $bookingId = $bookingModel->create($bookingData);
    if ($bookingId) {
        echo "✅ Booking created with ID: $bookingId\n";
    } else {
        echo "❌ Booking creation failed.\n";
        $pdo->rollBack();
        exit;
    }

    // 3. Simulate Transaction Creation
    echo "Inserting transaction entry...\n";
    $transactionModel = new Transaction_model();
    $txnData = [
        'transaction_number' => 'DIAG-TXN-' . time(),
        'account_id' => 1, // Assuming account 1 exists (usually Assets or something)
        'transaction_date' => date('Y-m-d'),
        'debit' => 100.00,
        'credit' => 0,
        'description' => 'Diagnostic Transaction',
        'reference_type' => 'booking',
        'reference_id' => $bookingId,
        'status' => 'posted',
        'created_at' => date('Y-m-d H:i:s')
    ];

    $txnId = $transactionModel->create($txnData);
    if ($txnId) {
        echo "✅ Transaction entry created.\n";
    } else {
        echo "❌ Transaction entry creation failed.\n";
    }

    // 4. Commit and Check
    echo "Committing transaction...\n";
    if ($pdo->commit()) {
        echo "✅ Transaction committed.\n";
    } else {
        echo "❌ Transaction commit failed.\n";
    }

    // 5. Final Verification
    $verifyBooking = $db->fetchOne("SELECT * FROM {$prefix}bookings WHERE id = ?", [$bookingId]);
    if ($verifyBooking) {
        echo "✅ FINAL VERIFICATION: Booking exists in DB after commit.\n";
    } else {
        echo "❌ FINAL VERIFICATION: Booking MISSING from DB after commit!\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Transaction rolled back due to error.\n";
    }
}
