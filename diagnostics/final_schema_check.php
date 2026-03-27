<?php
define('BASEPATH', true);
$c = require 'application/config/config.php';
$p = $c['db']['dbprefix'];
$db = $c['db'];
$pdo = new PDO("mysql:host={$db['hostname']};dbname={$db['database']}", $db['username'], $db['password']);

$tables = ['facilities', 'spaces', 'locations'];
$out = "";
foreach($tables as $table) {
    $out .= "TABLE: $p$table\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM `$p$table` ");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out .= "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    $out .= "\n";
}
file_put_contents("c:/xampp/htdocs/newerp/final_schema_check.txt", $out);
echo "Written to final_schema_check.txt\n";
