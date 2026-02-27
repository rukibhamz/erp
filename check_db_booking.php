<?php
$conn = new mysqli('localhost', 'root', '', 'erp');
if ($conn->connect_error) { die("Connection failed"); }

$res = $conn->query("SELECT * FROM erp_bookings WHERE id = 14");
if ($row = $res->fetch_assoc()) {
    print_r($row);
} else {
    echo "Booking 14 not found in database manually\n";
}
?>
