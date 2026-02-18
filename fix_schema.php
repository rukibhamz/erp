<?php
/**
 * Comprehensive schema fix for the booking flow.
 * Adds all missing columns that the Booking_wizard controller expects.
 */
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=erp', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $out = "";

    // Helper: check if column exists
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='erp' AND TABLE_NAME=? AND COLUMN_NAME=?");
        $stmt->execute([$table, $column]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($r['cnt']) > 0;
    }

    // Define all required columns per table
    $fixes = [
        'erp_customers' => [
            ['notes', "TEXT DEFAULT NULL"],
            ['customer_code', "VARCHAR(50) DEFAULT NULL"],
            ['customer_type_id', "INT(11) DEFAULT NULL"],
            ['contact_name', "VARCHAR(255) DEFAULT NULL"],
            ['payment_terms', "VARCHAR(50) DEFAULT 'Net 30'"],
            ['credit_limit', "DECIMAL(15,2) DEFAULT 0.00"],
            ['current_balance', "DECIMAL(15,2) DEFAULT 0.00"],
            ['created_by', "INT(11) DEFAULT NULL"],
        ],
        'erp_invoices' => [
            ['payment_date', "DATE DEFAULT NULL"],
            ['payment_method', "VARCHAR(100) DEFAULT NULL"],
            ['tax_rate', "DECIMAL(15,2) DEFAULT 0.00"],
        ],
        'erp_bookings' => [
            ['subtotal', "DECIMAL(15,2) DEFAULT 0.00"],
            ['tax_rate', "DECIMAL(15,2) DEFAULT 0.00"],
            ['invoice_id', "INT(11) DEFAULT NULL"],
            ['payment_plan', "VARCHAR(50) DEFAULT 'full'"],
            ['promo_code', "VARCHAR(50) DEFAULT NULL"],
            ['booking_source', "VARCHAR(20) DEFAULT 'admin'"],
            ['is_recurring', "TINYINT(1) DEFAULT 0"],
            ['recurring_pattern', "VARCHAR(20) DEFAULT NULL"],
            ['recurring_end_date', "DATE DEFAULT NULL"],
            ['customer_portal_user_id', "INT(11) DEFAULT NULL"],
        ],
    ];

    foreach ($fixes as $table => $columns) {
        $out .= "=== $table ===" . PHP_EOL;
        foreach ($columns as [$col, $def]) {
            if (!columnExists($pdo, $table, $col)) {
                try {
                    $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");
                    $out .= "  ADDED: $col ($def)" . PHP_EOL;
                } catch (Exception $e) {
                    $out .= "  FAILED to add $col: " . $e->getMessage() . PHP_EOL;
                }
            } else {
                $out .= "  EXISTS: $col" . PHP_EOL;
            }
        }
    }

    // Verify final state
    $out .= PHP_EOL . "=== Final Verification ===" . PHP_EOL;
    foreach (array_keys($fixes) as $table) {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table");
        $cols = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols[] = $row['Field'];
        }
        $out .= "$table: " . implode(', ', $cols) . PHP_EOL;
    }

    file_put_contents('schema_fix_results.txt', $out);
    echo $out;
    echo PHP_EOL . "All schema fixes applied." . PHP_EOL;
} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage() . PHP_EOL;
}
