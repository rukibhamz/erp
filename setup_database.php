<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; overflow-y: auto; }
        .table-list { columns: 3; -webkit-columns: 3; -moz-columns: 3; }
        .table-list li { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 ERP Database Setup</h1>
        
        <?php
        // Load configuration
        $configFile = __DIR__ . '/application/config/config.installed.php';
        if (!file_exists($configFile)) {
            $configFile = __DIR__ . '/application/config/config.php';
        }
        
        if (!file_exists($configFile)) {
            echo '<div class="error">Configuration file not found!</div>';
            exit;
        }
        
        $config = require $configFile;
        $dbConfig = $config['db'] ?? [];
        
        if (empty($dbConfig['hostname']) || empty($dbConfig['database'])) {
            echo '<div class="error">Database configuration is incomplete!</div>';
            exit;
        }
        
        $prefix = $dbConfig['dbprefix'] ?? 'erp_';
        
        // Connect to database
        try {
            $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            echo '<div class="success">✓ Database connection successful</div>';
        } catch (PDOException $e) {
            echo '<div class="error">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            exit;
        }
        
        // Check if migration requested
        if (isset($_POST['run_migration'])) {
            echo '<h2>Running Migration...</h2>';
            
            $sqlFile = __DIR__ . '/database/migrations/020_complete_system_tables.sql';
            if (!file_exists($sqlFile)) {
                echo '<div class="error">Migration file not found!</div>';
            } else {
                $sql = file_get_contents($sqlFile);
                
                // Split by semicolon but handle multi-line statements
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                $success = 0;
                $errors = 0;
                $skipped = 0;
                
                foreach ($statements as $stmt) {
                    if (empty($stmt) || strpos($stmt, '--') === 0) {
                        continue;
                    }
                    
                    try {
                        $pdo->exec($stmt);
                        $success++;
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        // Ignore "already exists" errors
                        if (strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate') !== false) {
                            $skipped++;
                        } else {
                            $errors++;
                            echo '<div class="warning">Statement error: ' . htmlspecialchars(substr($stmt, 0, 100)) . '... - ' . htmlspecialchars($msg) . '</div>';
                        }
                    }
                }
                
                echo '<div class="success">Migration complete! ' . $success . ' statements executed, ' . $skipped . ' skipped (already exist), ' . $errors . ' errors.</div>';
            }
        }
        
        // Get list of tables
        $tables = $pdo->query("SHOW TABLES LIKE '{$prefix}%'")->fetchAll(PDO::FETCH_COLUMN);
        
        // Required tables
        $requiredTables = [
            'stock_transactions', 'stock_adjustments', 'stock_takes', 'stock_take_items',
            'suppliers', 'purchase_orders', 'purchase_order_items', 'goods_receipts', 'goods_receipt_items',
            'booking_addons', 'tenants', 'rent_invoices',
            'meters', 'meter_readings', 'utility_providers', 'tariffs', 'utility_payments',
            'payroll_runs', 'payslips', 'paye_deductions',
            'tax_payments', 'wht_certificates', 'fixed_assets'
        ];
        
        $missing = [];
        foreach ($requiredTables as $table) {
            if (!in_array($prefix . $table, $tables)) {
                $missing[] = $table;
            }
        }
        
        // Check for locations view
        try {
            $pdo->query("SELECT 1 FROM {$prefix}locations LIMIT 1");
        } catch (PDOException $e) {
            $missing[] = 'locations (view)';
        }
        ?>
        
        <h2>Database Status</h2>
        <div class="info">
            <strong>Database:</strong> <?= htmlspecialchars($dbConfig['database']) ?><br>
            <strong>Table Prefix:</strong> <?= htmlspecialchars($prefix) ?><br>
            <strong>Total Tables:</strong> <?= count($tables) ?>
        </div>
        
        <?php if (!empty($missing)): ?>
            <div class="warning">
                <strong>⚠ Missing Tables (<?= count($missing) ?>):</strong>
                <ul class="table-list">
                    <?php foreach ($missing as $t): ?>
                        <li><?= htmlspecialchars($t) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <form method="post">
                <button type="submit" name="run_migration" value="1">
                    🚀 Run Migration (Create Missing Tables)
                </button>
            </form>
        <?php else: ?>
            <div class="success">
                <strong>✓ All required tables exist!</strong>
            </div>
        <?php endif; ?>
        
        <h2>Existing Tables (<?= count($tables) ?>)</h2>
        <pre><?php 
        sort($tables);
        foreach ($tables as $t) {
            echo htmlspecialchars($t) . "\n";
        }
        ?></pre>
        
        <p style="margin-top: 30px; color: #666;">
            <a href="/">← Back to ERP</a>
        </p>
    </div>
</body>
</html>
