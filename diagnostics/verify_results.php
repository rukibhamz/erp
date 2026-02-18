<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=erp', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $out = "";

    // 1. Check bookings
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM erp_bookings");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    $out .= "=== Bookings ===" . PHP_EOL;
    $out .= "Total bookings: " . $r['cnt'] . PHP_EOL;
    
    $stmt = $pdo->query("SELECT id, booking_number, customer_name, total_amount, status, payment_status, invoice_id, booking_source, created_by FROM erp_bookings ORDER BY id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out .= "  Booking #{$row['id']}: {$row['booking_number']} | {$row['customer_name']} | total={$row['total_amount']} | status={$row['status']} | pay_status={$row['payment_status']} | invoice_id={$row['invoice_id']} | source={$row['booking_source']} | created_by={$row['created_by']}" . PHP_EOL;
    }
    
    // 2. Check invoices
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM erp_invoices");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    $out .= PHP_EOL . "=== Invoices ===" . PHP_EOL;
    $out .= "Total invoices: " . $r['cnt'] . PHP_EOL;
    
    $stmt = $pdo->query("SELECT id, invoice_number, customer_id, subtotal, total_amount, status, created_by FROM erp_invoices ORDER BY id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out .= "  Invoice #{$row['id']}: {$row['invoice_number']} | customer_id={$row['customer_id']} | subtotal={$row['subtotal']} | total={$row['total_amount']} | status={$row['status']} | created_by={$row['created_by']}" . PHP_EOL;
    }
    
    // 3. Check transactions
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM erp_transactions");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    $out .= PHP_EOL . "=== Transactions ===" . PHP_EOL;
    $out .= "Total transactions: " . $r['cnt'] . PHP_EOL;
    
    $stmt = $pdo->query("SELECT id, transaction_number, account_id, description, debit, credit, reference_type, reference_id, status, created_by FROM erp_transactions ORDER BY id DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out .= "  Txn #{$row['id']}: {$row['transaction_number']} | acct={$row['account_id']} | {$row['description']} | DR={$row['debit']} CR={$row['credit']} | ref={$row['reference_type']}:{$row['reference_id']} | status={$row['status']} | by={$row['created_by']}" . PHP_EOL;
    }
    
    // 4. Check accounts
    $out .= PHP_EOL . "=== Key Accounts ===" . PHP_EOL;
    $stmt = $pdo->query("SELECT id, account_name, account_code, account_type FROM erp_accounts WHERE account_code IN ('1010','1200','2100','4000') OR account_name LIKE '%Receivable%' OR account_name LIKE '%Revenue%' OR account_name LIKE '%Cash%' OR account_name LIKE '%VAT%' ORDER BY account_code");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out .= "  Account #{$row['id']}: {$row['account_name']} (code={$row['account_code']}, type={$row['account_type']})" . PHP_EOL;
    }

    // 5. Check erp_invoices missing columns
    $out .= PHP_EOL . "=== Invoice Schema Check ===" . PHP_EOL;
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='erp' AND TABLE_NAME='erp_invoices' AND COLUMN_NAME IN ('payment_date','payment_method')");
    $found = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $found[] = $row['COLUMN_NAME'];
    }
    $out .= "payment_date: " . (in_array('payment_date', $found) ? 'EXISTS' : 'MISSING') . PHP_EOL;
    $out .= "payment_method: " . (in_array('payment_method', $found) ? 'EXISTS' : 'MISSING') . PHP_EOL;

    file_put_contents('verify_results.txt', $out);
    echo $out;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
