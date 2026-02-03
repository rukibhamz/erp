<?php
/**
 * Payment Flow Diagnostic Script
 * Run this to trace the payment confirmation flow step by step
 * 
 * Usage: php debug_payment_flow.php [transaction_ref]
 * Or via browser: http://localhost/erp/debug_payment_flow.php?ref=YOUR_REF
 */

// Define constants manually (no init.php required)
define('ROOTPATH', __DIR__ . '/');
define('BASEPATH', __DIR__ . '/system/');
define('APPPATH', __DIR__ . '/application/');

echo "<pre style='font-family: monospace; background: #1a1a2e; color: #16ff16; padding: 20px;'>\n";
echo "===========================================\n";
echo "PAYMENT FLOW DIAGNOSTIC\n";
echo "===========================================\n\n";

// Get transaction reference from CLI or GET
$transactionRef = $argv[1] ?? $_GET['ref'] ?? null;

// Database connection
try {
    $configFile = ROOTPATH . 'config/config.installed.php';
    if (!file_exists($configFile)) {
        $configFile = ROOTPATH . 'config/config.php';
    }
    if (!file_exists($configFile)) {
        throw new Exception("Config file not found. Tried: config/config.installed.php and config/config.php");
    }
    
    $config = require $configFile;
    $dbConfig = $config['database'];
    
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Database connection: OK\n\n";
} catch (Exception $e) {
    echo "✗ Database connection: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

$prefix = $dbConfig['table_prefix'] ?? 'erp_';

// Step 1: Check table structure
echo "STEP 1: CHECKING TABLE STRUCTURE\n";
echo "-------------------------------------------\n";

$requiredColumns = [
    'status', 'payment_status', 'paid_amount', 'balance_amount', 
    'payment_verified_at', 'confirmed_at', 'invoice_id'
];

$stmt = $pdo->query("DESCRIBE {$prefix}space_bookings");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$missingColumns = [];
foreach ($requiredColumns as $col) {
    if (in_array($col, $columns)) {
        echo "  ✓ Column '{$col}': EXISTS\n";
    } else {
        echo "  ✗ Column '{$col}': MISSING!\n";
        $missingColumns[] = $col;
    }
}

if (!empty($missingColumns)) {
    echo "\n!! CRITICAL: Missing columns detected!\n";
    echo "   Run migration: 030_add_payment_verification_columns.sql\n\n";
}

// Step 2: Check payment_transactions table
echo "\nSTEP 2: CHECKING PAYMENT_TRANSACTIONS TABLE\n";
echo "-------------------------------------------\n";

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$prefix}payment_transactions WHERE status = 'success'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "  Total successful transactions: {$result['cnt']}\n";

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$prefix}payment_transactions WHERE status = 'pending'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "  Total pending transactions: {$result['cnt']}\n";

// Step 3: Check specific transaction if provided
if ($transactionRef) {
    echo "\nSTEP 3: ANALYZING TRANSACTION: {$transactionRef}\n";
    echo "-------------------------------------------\n";
    
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}payment_transactions WHERE transaction_ref = ?");
    $stmt->execute([$transactionRef]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo "  Transaction ID: {$transaction['id']}\n";
        echo "  Status: {$transaction['status']}\n";
        echo "  Amount: {$transaction['amount']}\n";
        echo "  Payment Type: {$transaction['payment_type']}\n";
        echo "  Reference ID (Booking ID): {$transaction['reference_id']}\n";
        echo "  Gateway: {$transaction['gateway_code']}\n";
        echo "  Created: {$transaction['created_at']}\n";
        
        // Check associated booking
        if ($transaction['payment_type'] === 'booking_payment' && $transaction['reference_id']) {
            echo "\nSTEP 4: CHECKING LINKED BOOKING\n";
            echo "-------------------------------------------\n";
            
            $stmt = $pdo->prepare("SELECT * FROM {$prefix}space_bookings WHERE id = ?");
            $stmt->execute([$transaction['reference_id']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($booking) {
                echo "  Booking ID: {$booking['id']}\n";
                echo "  Booking Number: {$booking['booking_number']}\n";
                echo "  Status: {$booking['status']}\n";
                echo "  Payment Status: {$booking['payment_status']}\n";
                echo "  Total Amount: {$booking['total_amount']}\n";
                echo "  Paid Amount: {$booking['paid_amount']}\n";
                echo "  Balance: {$booking['balance_amount']}\n";
                echo "  Payment Verified At: " . ($booking['payment_verified_at'] ?? 'NULL') . "\n";
                echo "  Confirmed At: " . ($booking['confirmed_at'] ?? 'NULL') . "\n";
                echo "  Invoice ID: " . ($booking['invoice_id'] ?? 'NULL') . "\n";
                
                // Check if invoice exists
                if (!empty($booking['invoice_id'])) {
                    echo "\nSTEP 5: CHECKING LINKED INVOICE\n";
                    echo "-------------------------------------------\n";
                    
                    $stmt = $pdo->prepare("SELECT * FROM {$prefix}invoices WHERE id = ?");
                    $stmt->execute([$booking['invoice_id']]);
                    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($invoice) {
                        echo "  Invoice ID: {$invoice['id']}\n";
                        echo "  Invoice Number: {$invoice['invoice_number']}\n";
                        echo "  Status: {$invoice['status']}\n";
                        echo "  Total: {$invoice['total_amount']}\n";
                        echo "  Paid: " . ($invoice['paid_amount'] ?? $invoice['amount_paid'] ?? '0') . "\n";
                    } else {
                        echo "  ✗ Invoice #{$booking['invoice_id']} NOT FOUND!\n";
                    }
                }
                
                // Diagnose issues
                echo "\nDIAGNOSIS\n";
                echo "-------------------------------------------\n";
                
                if ($transaction['status'] === 'success') {
                    if ($booking['status'] !== 'confirmed') {
                        echo "  ✗ ISSUE: Transaction is SUCCESS but booking NOT CONFIRMED\n";
                        echo "    LIKELY CAUSE: processPaymentSuccess() didn't update correctly\n";
                        echo "    CHECK: Missing database columns or update method failure\n";
                    } else {
                        echo "  ✓ Transaction SUCCESS and booking CONFIRMED - All OK\n";
                    }
                } else {
                    echo "  ! Transaction status is '{$transaction['status']}' - payment not verified yet\n";
                }
            } else {
                echo "  ✗ Booking #{$transaction['reference_id']} NOT FOUND!\n";
            }
        }
    } else {
        echo "  ✗ Transaction not found with ref: {$transactionRef}\n";
    }
} else {
    echo "\nSTEP 3: RECENT TRANSACTIONS (no ref provided)\n";
    echo "-------------------------------------------\n";
    
    $stmt = $pdo->query("SELECT transaction_ref, status, payment_type, reference_id, amount, created_at 
                         FROM {$prefix}payment_transactions 
                         ORDER BY created_at DESC LIMIT 10");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Most recent 10 transactions:\n";
    foreach ($transactions as $t) {
        $icon = $t['status'] === 'success' ? '✓' : ($t['status'] === 'pending' ? '○' : '✗');
        echo "    {$icon} {$t['transaction_ref']} | {$t['status']} | {$t['amount']} | Booking:{$t['reference_id']}\n";
    }
    
    echo "\n  To analyze a specific transaction, add: ?ref=TRANSACTION_REF\n";
}

// Step: Check booking_payments table
echo "\nCHECKING BOOKING_PAYMENTS TABLE\n";
echo "-------------------------------------------\n";

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$prefix}booking_payments");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "  Total booking payments: {$result['cnt']}\n";

$stmt = $pdo->query("SELECT * FROM {$prefix}booking_payments ORDER BY id DESC LIMIT 5");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($payments as $p) {
    echo "    Payment #{$p['id']}: Booking {$p['booking_id']} | {$p['amount']} | {$p['status']}\n";
}

// Check debug log
echo "\nCHECKING DEBUG LOG\n";
echo "-------------------------------------------\n";
$logFile = __DIR__ . '/debug_log.txt';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -20);
    foreach ($recentLines as $line) {
        if (!empty(trim($line))) {
            echo "  " . htmlspecialchars($line) . "\n";
        }
    }
} else {
    echo "  No debug log found at: $logFile\n";
}

echo "\n===========================================\n";
echo "END DIAGNOSTIC\n";
echo "===========================================\n";
echo "</pre>\n";
