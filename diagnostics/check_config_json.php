<?php
define('BASEPATH', true);
$c = require 'application/config/config.php';
$p = $c['db']['dbprefix'];
$db = $c['db'];
$pdo = new PDO("mysql:host={$db['hostname']};dbname={$db['database']}", $db['username'], $db['password']);
$res = $pdo->query("SHOW COLUMNS FROM `{$p}bookable_config`")->fetchAll(PDO::FETCH_COLUMN);
echo "JSON_START" . json_encode($res) . "JSON_END\n";
