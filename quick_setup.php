<?php
/**
 * ERP Quick Database Setup
 * Creates all missing tables with a single focused migration
 */

define('BASEPATH', __DIR__ . '/application/');

header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERP Quick Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
        pre { background: #222; color: #0f0; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 ERP Quick Database Setup</h1>
    
    <?php
    // Load config
    $configFile = __DIR__ . '/application/config/config.installed.php';
    if (!file_exists($configFile)) {
        $configFile = __DIR__ . '/application/config/config.php';
    }
    
    $config = require $configFile;
    $db = $config['db'] ?? [];
    $prefix = $db['dbprefix'] ?? 'erp_';
    
    // Connect with mysqli for multi_query support
    $mysqli = new mysqli($db['hostname'], $db['username'], $db['password'], $db['database']);
    
    if ($mysqli->connect_error) {
        echo '<div class="error">Connection failed: ' . htmlspecialchars($mysqli->connect_error) . '</div>';
        exit;
    }
    
    echo '<div class="success">✓ Connected to database: ' . htmlspecialchars($db['database']) . '</div>';
    
    // Check current tables
    $result = $mysqli->query("SHOW TABLES LIKE '{$prefix}%'");
    $existingTables = [];
    while ($row = $result->fetch_array()) {
        $existingTables[] = $row[0];
    }
    
    $requiredTables = [
        'stock_transactions', 'stock_adjustments', 'stock_takes',
        'suppliers', 'purchase_orders', 'goods_receipts',
        'booking_addons', 'tenants', 'rent_invoices',
        'meters', 'meter_readings', 'utility_providers', 'tariffs', 'utility_payments',
        'payroll_runs', 'payslips', 'paye_deductions',
        'tax_payments', 'wht_certificates', 'fixed_assets'
    ];
    
    $missing = [];
    foreach ($requiredTables as $table) {
        if (!in_array($prefix . $table, $existingTables)) {
            $missing[] = $table;
        }
    }
    
    echo '<div class="info">Tables: ' . count($existingTables) . ' existing, ' . count($missing) . ' missing</div>';
    
    if (isset($_POST['run'])) {
        echo '<h2>Running Migration...</h2>';
        
        // Read the migration file
        $sqlFile = __DIR__ . '/database/migrations/020_complete_system_tables.sql';
        if (!file_exists($sqlFile)) {
            echo '<div class="error">Migration file not found!</div>';
        } else {
            $sql = file_get_contents($sqlFile);
            
            // Execute with multi_query
            $mysqli->multi_query($sql);
            
            $success = 0;
            $errors = [];
            
            do {
                if ($result = $mysqli->store_result()) {
                    $result->free();
                }
                $success++;
                
                if ($mysqli->errno) {
                    $errors[] = $mysqli->error;
                }
            } while ($mysqli->next_result());
            
            if (!empty($errors)) {
                $uniqueErrors = array_unique($errors);
                foreach ($uniqueErrors as $e) {
                    if (strpos($e, 'already exists') === false && !empty(trim($e))) {
                        echo '<div class="error">⚠ ' . htmlspecialchars($e) . '</div>';
                    }
                }
            }
            
            echo '<div class="success">✓ Migration completed! ' . $success . ' statements processed.</div>';
            
            // Recheck tables
            $mysqli->close();
            $mysqli = new mysqli($db['hostname'], $db['username'], $db['password'], $db['database']);
            $result = $mysqli->query("SHOW TABLES LIKE '{$prefix}%'");
            $newTables = [];
            while ($row = $result->fetch_array()) {
                $newTables[] = $row[0];
            }
            
            $newMissing = [];
            foreach ($requiredTables as $table) {
                if (!in_array($prefix . $table, $newTables)) {
                    $newMissing[] = $table;
                }
            }
            
            echo '<div class="info">After migration: ' . count($newTables) . ' tables, ' . count($newMissing) . ' still missing</div>';
            
            if (empty($newMissing)) {
                echo '<div class="success"><strong>✓ All required tables now exist!</strong></div>';
            } else {
                echo '<div class="error">Still missing: ' . implode(', ', $newMissing) . '</div>';
            }
        }
    }
    ?>
    
    <?php if (!empty($missing)): ?>
        <h2>Missing Tables (<?= count($missing) ?>)</h2>
        <pre><?= implode("\n", array_map(function($t) use ($prefix) { return $prefix . $t; }, $missing)) ?></pre>
        
        <form method="post">
            <button type="submit" name="run" value="1">🚀 Create Missing Tables</button>
        </form>
    <?php else: ?>
        <div class="success"><strong>✓ All required tables exist!</strong></div>
    <?php endif; ?>
    
    <h2>Existing Tables (<?= count($existingTables) ?>)</h2>
    <pre><?= implode("\n", $existingTables) ?></pre>
    
    <p><a href="/">← Back to ERP</a></p>
</div>
</body>
</html>
