<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=erp', 'root', '');
    $out = "";
    foreach (['erp_bookings', 'erp_invoices', 'erp_transactions'] as $table) {
        $stmt = $pdo->query("DESC $table");
        $out .= "=== $table ===" . PHP_EOL;
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out .= $row['Field'] . PHP_EOL;
        }
        $out .= PHP_EOL;
    }
    file_put_contents('temp_cols.txt', $out);
    echo "Columns written to temp_cols.txt" . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
