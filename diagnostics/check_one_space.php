<?php
define('BASEPATH', true);
$c = require 'application/config/config.php';
$p = $c['db']['dbprefix'];
$db = $c['db'];
$pdo = new PDO("mysql:host={$db['hostname']};dbname={$db['database']}", $db['username'], $db['password']);
$stmt = $pdo->query("SELECT * FROM `{$p}spaces` LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "SPACE RECORD:\n";
foreach($row as $key => $val) {
    echo "[$key] => $val\n";
}
echo "END_RECORD\n";
