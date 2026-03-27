<?php
define('BASEPATH', true);
$c = require 'application/config/config.php';
$db = $c['db'];
$pdo = new PDO("mysql:host={$db['hostname']};dbname={$db['database']}", $db['username'], $db['password']);
$out = "";
foreach (['erp_bookings', 'erp_pos_terminals', 'erp_booking_payments'] as $table) {
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        if ($stmt) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $out .= $row['Create Table'] . "\n\n";
        }
    } catch (Exception $e) { $out .= "Table $table not found.\n"; }
}
file_put_contents('schema_dump.txt', $out);
echo "Dumped to schema_dump.txt\n";
