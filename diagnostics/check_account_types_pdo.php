<?php
// Diagnostics/check_account_types_pdo.php

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

$dsn = "mysql:host=127.0.0.1;dbname={$dbConfig['database']};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    $prefix = $dbConfig['dbprefix'] ?? 'erp_';
    
    echo "=== DISTINCT ACCOUNT TYPES IN DB ===\n";
    $stmt = $pdo->query("SELECT DISTINCT account_type FROM `{$prefix}accounts`");
    $types = $stmt->fetchAll();
    foreach ($types as $row) {
        echo "- '" . $row['account_type'] . "'\n";
    }

    echo "\n=== TESTING QUERY LOGIC ===\n";
    $tests = ['asset', 'assets', 'revenue', 'income', 'liability', 'liabilities', 'expense', 'expenses'];

    foreach ($tests as $originalType) {
        // Logic from Account_model::getByType
        $normalizedType = strtolower($originalType);
        if ($normalizedType === 'revenue') $normalizedType = 'income';
        else if ($normalizedType === 'assets') $normalizedType = 'asset';
        else if ($normalizedType === 'liabilities') $normalizedType = 'liability';
        else if ($normalizedType === 'expenses') $normalizedType = 'expense';
        
        $sql = "SELECT COUNT(*) as cnt FROM `{$prefix}accounts` 
                WHERE (account_type = ? OR account_type = ?) AND status = 'active'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$normalizedType, $originalType]);
        $result = $stmt->fetch();
        
        echo "Query for '$originalType' (Normalized: '$normalizedType'): Found " . $result['cnt'] . " accounts.\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
