<?php
define('BASEPATH', true);
$c = require 'application/config/config.php';
$p = $c['db']['dbprefix'];
$db = $c['db'];
$pdo = new PDO("mysql:host={$db['hostname']};dbname={$db['database']}", $db['username'], $db['password']);
$stmt = $pdo->query("SELECT * FROM `{$p}spaces` LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$out = "SPACE RECORD:\n";
foreach($row as $key => $val) {
    $out .= "[$key] => $val\n";
}
file_put_contents("c:/xampp/htdocs/newerp/one_space.txt", $out);
echo "Written to one_space.txt\n";
