<?php
$pdo = new PDO('mysql:host=localhost;dbname=erp;charset=utf8mb4', 'root', '');

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    if (strpos($table, 'erp_') === false) continue;
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $checkCols = [];
    foreach ($columns as $col) {
        if (strpos(strtolower($col['Type']), 'char') !== false || strpos(strtolower($col['Type']), 'text') !== false || strpos(strtolower($col['Type']), 'decimal') !== false || strpos(strtolower($col['Type']), 'int') !== false) {
            $checkCols[] = "`{$col['Field']}` LIKE '%15%'";
        }
    }
    if ($checkCols) {
        $where = implode(' OR ', $checkCols);
        $res = $pdo->query("SELECT * FROM `$table` WHERE $where LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        if ($res) {
            echo "Found in $table:\n";
            print_r($res);
        }
    }
}
