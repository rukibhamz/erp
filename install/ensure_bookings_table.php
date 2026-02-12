<?php
define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
require_once BASEPATH . 'core/Database.php';
require_once __DIR__ . '/migrations_booking.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$prefix = $db->getPrefix();

echo "Verifying bookings table...\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$prefix}bookings`");
    echo "Bookings table exists.\n";
} catch (PDOException $e) {
    echo "Bookings table missing or error: " . $e->getMessage() . "\n";
    echo "Attempting to create bookings table...\n";
    
    if (runBookingMigrations($pdo, $prefix)) {
        echo "Booking migrations run successfully.\n";
    } else {
        echo "Booking migrations failed.\n";
    }
}
