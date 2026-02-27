<?php
$conn = new mysqli('localhost', 'root', '', 'erp');
if ($conn->connect_error) { die("Connection failed"); }

$res = $conn->query("SELECT id, booking_number FROM erp_bookings");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
