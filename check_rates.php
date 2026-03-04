<?php
$pdo = new PDO('mysql:host=localhost;dbname=erp;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SELECT id, name, hourly_rate, daily_rate FROM erp_facilities LIMIT 10');
echo print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true);
