<?php
// diagnostics/dump_accounts.php

// Load database config
$configFile = __DIR__ . '/../application/config/config.installed.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/../application/config/config.php';
}

if (!file_exists($configFile)) {
    die("Config file not found.");
}

$config = require $configFile;
$dbConfig = $config['db'];

$dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    $prefix = $dbConfig['dbprefix'] ?? 'erp_';
    
    echo "<h1>Account Types Check</h1>";
    
    echo "<h2>Distinct Account Types</h2>";
    $stmt = $pdo->query("SELECT DISTINCT account_type FROM `{$prefix}accounts`");
    $types = $stmt->fetchAll();
    echo "<ul>";
    foreach ($types as $row) {
        echo "<li>'" . htmlspecialchars($row['account_type']) . "'</li>";
    }
    echo "</ul>";
    
    echo "<h2>Status Check</h2>";
    $stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM `{$prefix}accounts` GROUP BY status");
    $statuses = $stmt->fetchAll();
    echo "<ul>";
    foreach ($statuses as $row) {
        echo "<li>" . htmlspecialchars($row['status']) . ": " . $row['cnt'] . "</li>";
    }
    echo "</ul>";

    echo "<h2>Test getByType Logic</h2>";
    $tests = ['asset', 'revenue', 'liability'];
    
    foreach ($tests as $originalType) {
        $normalizedType = strtolower($originalType);
        if ($normalizedType === 'revenue') $normalizedType = 'income';
        else if ($normalizedType === 'assets') $normalizedType = 'asset';
        else if ($normalizedType === 'liabilities') $normalizedType = 'liability';
        else if ($normalizedType === 'expenses') $normalizedType = 'expense';
        
        $sql = "SELECT account_id, account_code, account_name, account_type FROM `{$prefix}accounts` 
                WHERE (account_type = ? OR account_type = ?) AND status = 'active' LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$normalizedType, $originalType]);
        $results = $stmt->fetchAll();
        
        echo "<h3>Type: $originalType (Normalized: $normalizedType)</h3>";
        if (empty($results)) {
            echo "<p style='color:red'>Found 0 accounts!</p>";
        } else {
            echo "<ul>";
            foreach ($results as $row) {
                echo "<li>" . htmlspecialchars($row['account_name']) . " (" . htmlspecialchars($row['account_type']) . ")</li>";
            }
            echo "</ul>";
        }
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
