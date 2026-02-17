<?php
$config = [
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'erp',
    'dbprefix' => 'erp_',
];

try {
    $pdo = new PDO("mysql:host={$config['hostname']};dbname={$config['database']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Accounting System Diagnostic\n";
    echo "============================\n\n";

    // Check specific accounts
    $codes = ['1200', '4100', '4000', '2100', '1010', '1001', '1000'];
    foreach ($codes as $code) {
        $stmt = $pdo->prepare("SELECT * FROM erp_accounts WHERE account_code = ?");
        $stmt->execute([$code]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($account) {
            echo "[OK] Found account $code: " . $account['account_name'] . "\n";
        } else {
            echo "[MISSING] Account $code not found\n";
        }
    }

    echo "\nRecent Invoices:\n";
    $stmt = $pdo->query("SELECT * FROM erp_invoices ORDER BY id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Invoice #{$row['invoice_number']} - Total: {$row['total_amount']} - Status: {$row['status']} (Reference: {$row['reference']})\n";
    }

    echo "\nRecent Transactions:\n";
    $stmt = $pdo->query("SELECT t.*, a.account_name, a.account_code FROM erp_transactions t JOIN erp_accounts a ON t.account_id = a.id ORDER BY t.id DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['transaction_date']} - Account [{$row['account_code']}] {$row['account_name']}: DR {$row['debit']}, CR {$row['credit']} ({$row['description']})\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
