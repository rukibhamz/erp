<?php
// diagnostics/fix_pos_terminals_schema.php

// Define BASEPATH to bypass the check in config.installed.php
define('BASEPATH', true);

// Load database config
$configFile = __DIR__ . '/../application/config/config.installed.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/../application/config/config.php';
}

if (!file_exists($configFile)) {
    die("Config file not found.\n");
}

$config = require $configFile;
$dbConfig = $config['db'];

// Use 127.0.0.1 to avoid localhost IPv6 issues
$dsn = "mysql:host=127.0.0.1;dbname={$dbConfig['database']};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    $prefix = $dbConfig['dbprefix'] ?? 'erp_';
    $table = $prefix . 'pos_terminals';
    
    echo "Checking table: $table\n";
    
    // Get existing columns
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missing = [];
    $required = [
        'cash_account_id' => "INT(11) DEFAULT NULL",
        'sales_account_id' => "INT(11) DEFAULT NULL",
        'tax_account_id' => "INT(11) DEFAULT NULL"
    ];
    
    foreach ($required as $col => $def) {
        if (!in_array($col, $columns)) {
            $missing[$col] = $def;
        }
    }
    
    if (empty($missing)) {
        echo "All required columns already exist.\n";
    } else {
        echo "Missing columns: " . implode(', ', array_keys($missing)) . "\n";
        foreach ($missing as $col => $def) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$col` $def";
            echo "Executing: $sql\n";
            $pdo->exec($sql);
            echo "Added column: $col\n";
        }
        echo "Schema update complete.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
