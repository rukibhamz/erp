<?php
// trigger_migration.php
// Manually triggers AutoMigration by loading CodeIgniter logic roughly.
// Actually, it's easier to just connect to DB and run the SQL file content directly if CLI doesn't work.
// But since we want to test if AutoMigration picks it up, we should try to simulate a request.

// However, since we cannot use curl easily against localhost in some environments, let's try to include the index.php logic but just for migration.
// But database config is missing... no it exists in config.installed.php!

// Let's create a script that reads config.installed.php and runs the new migration file directly using PDO.
// This bypasses the CI framework and AutoMigration logic but ensures the table exists.

define('BASEPATH', __DIR__ . '/application/');
require_once 'application/config/config.installed.php';
// config.installed.php returns an array assigned to $config in index.php, but here it returns it.
$config = require 'application/config/config.installed.php';

$dbConfig = $config['db'];
$host = $dbConfig['hostname'];
$dbname = $dbConfig['database'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$prefix = $dbConfig['dbprefix'] ?? 'erp_';

echo "Connecting to $dbname at $host...\n";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$dbConfig[charset]";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read the migration file
    $sqlFile = 'database/migrations/024_fix_missing_primary_tables.sql';
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        die("Could not read migration file $sqlFile\n");
    }
    
    echo "Running migration $sqlFile...\n";
    
    // Execute multiple statements
    // PDO might not support multiple statements in one exec call depending on driver options (emulate prepares).
    // It's safer to split by semicolon if possible, but triggers/procedures make that hard.
    // Here we have simple CREATE TABLEs.
    
    $pdo->exec($sql);
    
    echo "Migration completed successfully!\n";
    
    // Verification
    $tables = ['erp_bookings', 'erp_facilities', 'erp_customers', 'erp_invoices', 'erp_accounts', 'erp_cash_accounts'];
    echo "Verifying tables...\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table exists\n";
        } else {
            echo "✗ $table MISSING\n";
        }
    }
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "Error: PDO MySQL driver not found. Cannot run migration via CLI PHP.\n";
        echo "Please ensure php_pdo_mysql extension is enabled in php.ini used by CLI.\n";
    } else {
        echo "Database Error: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
