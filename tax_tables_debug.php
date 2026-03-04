<?php
// A simple script to validate the "Dual Database Table" theory
try {
    define('BASEPATH', __DIR__);
    // Read the database credentials dynamically from the config file
    $configFile = __DIR__ . '/application/config/config.installed.php';
    if (!file_exists($configFile)) {
        throw new Exception("Config file not found: " . $configFile);
    }
    
    $config = require $configFile;
    $dbConfig = $config['db'];
    
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $prefix = $dbConfig['dbprefix'];

    // 1. Query the table used by Booking_wizard (Tax_model)
    $stmt1 = $pdo->query("SELECT * FROM {$prefix}taxes WHERE tax_code = 'VAT' OR tax_name LIKE '%VAT%' OR tax_name LIKE '%Value Added%'");
    $taxesTable = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // 2. Query the table used by Tax Configuration UI (Tax_type_model)
    $stmt2 = $pdo->query("SELECT * FROM {$prefix}tax_types WHERE code = 'VAT' OR name LIKE '%VAT%' OR name LIKE '%Value Added%'");
    $taxTypesTable = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $diagnostics = [
        'theory' => 'The system has two separate database tables for taxes that are out of sync.',
        'evidence_table_1' => [
            'name' => "{$prefix}taxes",
            'used_by' => 'Booking_wizard.php (via Tax_model)',
            'records' => $taxesTable
        ],
        'evidence_table_2' => [
            'name' => "{$prefix}tax_types",
            'used_by' => 'Tax_config.php UI (via Tax_type_model)',
            'records' => $taxTypesTable
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($diagnostics, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
