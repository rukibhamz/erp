<?php
$url = 'http://localhost/newerp/booking-wizard/index?booking_id=16&action=pay';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);
$info = curl_getinfo($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Redirect URL: " . $info['redirect_url'] . "\n";

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
echo "\nHeaders:\n" . $header;
?>
