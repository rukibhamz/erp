<?php
/**
 * ERP Database Setup & Automigration Tool
 * 
 * Access via browser: http://localhost/erp/automigration.php
 */

// Define BASEPATH to satisfy config file security check
define('BASEPATH', __DIR__ . '/application/');

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
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f0f2f5; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h1 { color: #1a1a2e; margin-bottom: 10px; }
        h2 { color: #16213e; margin-top: 30px; border-bottom: 2px solid #e94560; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #cce5ff; color: #004085; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #007bff; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107; }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; }
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
            echo '<div class="error"><strong>Error:</strong> Configuration file not found!</div>';
            echo '</div></body></html>';
            exit;
        }
        
        $config = require $configFile;
        $dbConfig = $config['db'] ?? [];
        
        if (empty($dbConfig['hostname']) || empty($dbConfig['database'])) {
            echo '<div class="error"><strong>Error:</strong> Database configuration is incomplete!</div>';
            echo '</div></body></html>';
            exit;
        }
        
        $prefix = $dbConfig['dbprefix'] ?? 'erp_';
        
        // Helper function to get a fresh PDO connection
        function getConnection($dbConfig) {
            $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset=" . ($dbConfig['charset'] ?? 'utf8mb4');
            return new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ]);
        }
        
        // Connect to database
        try {
            $pdo = getConnection($dbConfig);
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
        $stmt = $pdo->query("SELECT migration FROM `{$prefix}migrations`");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt->closeCursor();
        $stmt = null;
        
        // Get migration files
        $migrationDir = __DIR__ . '/database/migrations';
        $files = glob($migrationDir . '/*.sql');
        sort($files);
        
        // Get current batch
        $stmt = $pdo->query("SELECT MAX(batch) as max_batch FROM `{$prefix}migrations`");
        $batchResult = $stmt->fetch();
        $stmt->closeCursor();
        $stmt = null;
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
                    $skipped++;
                    continue;
                }
                
                // Get a fresh connection for each file to avoid buffering issues
                $pdo = null;
                $pdo = getConnection($dbConfig);
                
                $sql = file_get_contents($file);
                
                // Remove comments
                $sql = preg_replace('/^--.*$/m', '', $sql);
                
                // Split by semicolon
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                $fileErrors = 0;
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if (empty($stmt)) continue;
                    
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        // Ignore "already exists" and duplicate errors
                        if (strpos($msg, 'already exists') === false && 
                            strpos($msg, 'Duplicate') === false &&
                            strpos($msg, '1060') === false &&
                            strpos($msg, '1061') === false) {
                            $fileErrors++;
                            if ($fileErrors <= 3) { // Only show first 3 errors per file
                                echo '<div class="warning">⚠ ' . htmlspecialchars($name) . ': ' . htmlspecialchars(substr($msg, 0, 100)) . '</div>';
                            }
                        }
                    }
                }
                
                // Record migration
                try {
                    $insertStmt = $pdo->prepare("INSERT INTO `{$prefix}migrations` (migration, batch, executed_at) VALUES (?, ?, NOW())");
                    $insertStmt->execute([$name, $batch]);
                    $insertStmt->closeCursor();
                    echo '<div class="success">✓ Executed: ' . htmlspecialchars($name) . ($fileErrors > 0 ? " ({$fileErrors} warnings)" : '') . '</div>';
                    $success++;
                } catch (PDOException $e) {
                    // Already recorded or error
                    if (strpos($e->getMessage(), 'Duplicate') !== false) {
                        $skipped++;
                    }
                }
            }
            
            echo '<div class="info"><strong>Summary:</strong> ' . $success . ' migrations executed, ' . $skipped . ' already done.</div>';
            
            // Get fresh connection and refresh executed list
            $pdo = getConnection($dbConfig);
            $stmt = $pdo->query("SELECT migration FROM `{$prefix}migrations`");
            $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $stmt->closeCursor();
        }
        
        // Get pending migrations
        $pending = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (!in_array($name, $executed)) {
                $pending[] = $name;
            }
        }
        
        // Get fresh connection for remaining queries
        $pdo = getConnection($dbConfig);
        
        // Get list of existing tables
        $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt->closeCursor();
        
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
        foreach ($requiredTables as $table) {
            if (!in_array($prefix . $table, $tables)) {
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
            <h2>⚠️ Missing Required Tables (<?= count($missing) ?>)</h2>
            <div class="table-grid">
                <?php foreach ($missing as $m): ?>
                    <div class="table-item missing"><?= htmlspecialchars($prefix . $m) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <h2>📊 Executed Migrations (<?= count($executed) ?>)</h2>
        <?php if (!empty($executed)): ?>
            <div class="table-grid">
                <?php foreach ($executed as $e): ?>
                    <div class="table-item exists">✓ <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="info">No migrations have been executed yet.</div>
        <?php endif; ?>
        
        <p style="margin-top: 30px; color: #666;">
            <a href="/">← Back to ERP</a>
        </p>
    </div>
</body>
</html>
