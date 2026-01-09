<?php
/**
 * ERP Database Setup & Automigration Tool
 * 
 * Access via browser: http://localhost/erp/automigration.php
 * 
 * This script:
 * 1. Checks database connection
 * 2. Runs all pending SQL migrations from /database/migrations/
 * 3. Creates the erp_migrations tracking table if needed
 */

// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Content-Type: text/html; charset=UTF-8');

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Database Automigration</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f0f2f5; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #1a1a2e; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        h2 { color: #16213e; margin-top: 30px; border-bottom: 2px solid #e94560; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #cce5ff; color: #004085; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #007bff; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107; }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        pre { background: #1a1a2e; color: #a8dadc; padding: 20px; border-radius: 8px; overflow-x: auto; max-height: 300px; overflow-y: auto; font-size: 13px; }
        .table-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; margin: 15px 0; }
        .table-item { background: #f8f9fa; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 12px; }
        .table-item.missing { background: #fff3cd; }
        .table-item.exists { background: #d4edda; }
        .stats { display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; min-width: 150px; text-align: center; }
        .stat-card h3 { margin: 0; font-size: 32px; }
        .stat-card p { margin: 5px 0 0; opacity: 0.9; }
        a { color: #667eea; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 ERP Database Automigration</h1>
        <p style="color: #666; margin-top: 0;">Automatically create and update all database tables</p>
        
        <?php
        // Load configuration
        $configFile = __DIR__ . '/application/config/config.installed.php';
        if (!file_exists($configFile)) {
            $configFile = __DIR__ . '/application/config/config.php';
        }
        
        if (!file_exists($configFile)) {
            echo '<div class="error"><strong>Error:</strong> Configuration file not found! Please ensure config.php or config.installed.php exists in /application/config/</div>';
            echo '</div></body></html>';
            exit;
        }
        
        $config = require $configFile;
        $dbConfig = $config['db'] ?? [];
        
        if (empty($dbConfig['hostname']) || empty($dbConfig['database'])) {
            echo '<div class="error"><strong>Error:</strong> Database configuration is incomplete! Check your config file.</div>';
            echo '</div></body></html>';
            exit;
        }
        
        $prefix = $dbConfig['dbprefix'] ?? 'erp_';
        
        // Connect to database
        try {
            $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset=" . ($dbConfig['charset'] ?? 'utf8mb4');
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            echo '<div class="success">✓ Connected to database: <strong>' . htmlspecialchars($dbConfig['database']) . '</strong></div>';
        } catch (PDOException $e) {
            echo '<div class="error"><strong>Database connection failed:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '</div></body></html>';
            exit;
        }
        
        // Create migrations table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}migrations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT(11) NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Get executed migrations
        $executed = $pdo->query("SELECT migration FROM `{$prefix}migrations`")->fetchAll(PDO::FETCH_COLUMN);
        
        // Get migration files
        $migrationDir = __DIR__ . '/database/migrations';
        $files = glob($migrationDir . '/*.sql');
        sort($files);
        
        // Get current batch
        $batchResult = $pdo->query("SELECT MAX(batch) as max_batch FROM `{$prefix}migrations`")->fetch();
        $batch = (int)($batchResult['max_batch'] ?? 0) + 1;
        
        // Check if migration requested
        if (isset($_POST['run_migration'])) {
            echo '<h2>🚀 Running Migrations...</h2>';
            
            $success = 0;
            $errors = 0;
            $skipped = 0;
            
            foreach ($files as $file) {
                $name = basename($file);
                
                if (in_array($name, $executed)) {
                    echo '<div class="info">⏭ Skipped (already executed): ' . htmlspecialchars($name) . '</div>';
                    $skipped++;
                    continue;
                }
                
                $sql = file_get_contents($file);
                
                // Remove comments and split by semicolon
                $sql = preg_replace('/^--.*$/m', '', $sql);
                $statements = array_filter(array_map('trim', preg_split('/;(?=\s*(?:CREATE|DROP|ALTER|INSERT|UPDATE|DELETE|SET|TRUNCATE))/i', $sql)));
                
                $fileSuccess = true;
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if (empty($stmt)) continue;
                    
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        // Ignore "already exists" errors
                        if (strpos($msg, 'already exists') === false && strpos($msg, 'Duplicate') === false) {
                            echo '<div class="warning">⚠ ' . htmlspecialchars(substr($stmt, 0, 60)) . '... - ' . htmlspecialchars($msg) . '</div>';
                            $fileSuccess = false;
                        }
                    }
                }
                
                if ($fileSuccess) {
                    // Record migration
                    $pdo->prepare("INSERT INTO `{$prefix}migrations` (migration, batch, executed_at) VALUES (?, ?, NOW())")
                        ->execute([$name, $batch]);
                    echo '<div class="success">✓ Executed: ' . htmlspecialchars($name) . '</div>';
                    $success++;
                } else {
                    $errors++;
                }
            }
            
            echo '<div class="info" style="margin-top: 20px;"><strong>Summary:</strong> ' . $success . ' migrations executed, ' . $skipped . ' skipped, ' . $errors . ' with errors.</div>';
            
            // Refresh executed list
            $executed = $pdo->query("SELECT migration FROM `{$prefix}migrations`")->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Get pending migrations
        $pending = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $executed)) {
                $pending[] = $name;
            }
        }
        
        // Get list of existing tables
        $tables = $pdo->query("SHOW TABLES LIKE '{$prefix}%'")->fetchAll(PDO::FETCH_COLUMN);
        
        // Required tables
        $requiredTables = [
            'stock_transactions', 'stock_adjustments', 'stock_takes',
            'suppliers', 'purchase_orders', 'goods_receipts',
            'booking_addons', 'tenants', 'rent_invoices',
            'meters', 'meter_readings', 'utility_providers', 'tariffs', 'utility_payments',
            'payroll_runs', 'payslips', 'paye_deductions',
            'tax_payments', 'wht_certificates', 'fixed_assets'
        ];
        
        $missing = [];
        $existing = [];
        foreach ($requiredTables as $table) {
            if (in_array($prefix . $table, $tables)) {
                $existing[] = $table;
            } else {
                $missing[] = $table;
            }
        }
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?= count($tables) ?></h3>
                <p>Total Tables</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h3><?= count($executed) ?></h3>
                <p>Migrations Run</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%);">
                <h3><?= count($pending) ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card" style="background: <?= count($missing) > 0 ? 'linear-gradient(135deg, #e94560 0%, #ff6b6b 100%)' : 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)' ?>;">
                <h3><?= count($missing) ?></h3>
                <p>Missing Tables</p>
            </div>
        </div>
        
        <?php if (!empty($pending)): ?>
            <h2>⏳ Pending Migrations (<?= count($pending) ?>)</h2>
            <div class="table-grid">
                <?php foreach ($pending as $p): ?>
                    <div class="table-item missing"><?= htmlspecialchars($p) ?></div>
                <?php endforeach; ?>
            </div>
            
            <form method="post" style="margin-top: 20px;">
                <button type="submit" name="run_migration" value="1">
                    🚀 Run All Pending Migrations
                </button>
            </form>
        <?php else: ?>
            <div class="success" style="margin-top: 20px;">
                <strong>✓ All migrations have been executed!</strong>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($missing)): ?>
            <h2>⚠️ Missing Required Tables</h2>
            <div class="table-grid">
                <?php foreach ($missing as $m): ?>
                    <div class="table-item missing"><?= htmlspecialchars($prefix . $m) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <h2>📊 Executed Migrations</h2>
        <?php if (!empty($executed)): ?>
            <div class="table-grid">
                <?php foreach ($executed as $e): ?>
                    <div class="table-item exists">✓ <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="info">No migrations have been executed yet.</div>
        <?php endif; ?>
        
        <h2>📁 All Tables (<?= count($tables) ?>)</h2>
        <pre><?php 
        sort($tables);
        echo implode("\n", array_map('htmlspecialchars', $tables));
        ?></pre>
        
        <p style="margin-top: 30px; color: #666;">
            <a href="/">← Back to ERP</a> | 
            <a href="system_migrate">System Migration (Admin)</a>
        </p>
    </div>
</body>
</html>
