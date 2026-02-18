<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=erp', 'root', '');
// Clean test data from simulation runs
$pdo->exec('DELETE FROM erp_bookings WHERE id >= 13');
$pdo->exec('DELETE FROM erp_transactions WHERE id > 1');
$pdo->exec('DELETE FROM erp_invoices');
$pdo->exec('DELETE FROM erp_customers WHERE notes = "Customer created from booking system"');

// Clear old logs
@file_put_contents('logs/debug_wizard_log.txt', '');
@file_put_contents('logs/invoice_creation.log', '');
@file_put_contents('logs/customer_creation.log', '');
@file_put_contents('simulation_trace.log', '');

echo "Test data cleaned and logs cleared." . PHP_EOL;
