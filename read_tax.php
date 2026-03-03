<?php
$json = file_get_contents('http://localhost/newerp/booking-wizard/test_tax');
$data = json_decode($json, true);
echo print_r($data, true);
