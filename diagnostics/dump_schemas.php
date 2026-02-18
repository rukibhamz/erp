<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=erp', 'root', '');
    $tables = ['erp_bookings', 'erp_transactions', 'erp_booking_payments', 'erp_payment_transactions', 'erp_invoices', 'erp_invoice_items'];
    $out = "";
    foreach ($tables as $table) {
        $res = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC);
        $out .= "--- $table ---\n" . ($res['Create Table'] ?? 'NOT FOUND') . "\n\n";
    }
    file_put_contents('schemas.txt', $out);
    echo "Schemas written to schemas.txt\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
