<?php
// Script to manually run migration 029 via browser
define('BASEPATH', __DIR__ . '/application/');
$config = require 'application/config/config.installed.php';
$dbConfig = $config['db'];

echo "<h1>Database Update Tool</h1>";

try {
    $dsn = "mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read the migration file
    $migrationFile = __DIR__ . '/database/migrations/029_update_space_bookings_table.sql';
    if (!file_exists($migrationFile)) {
        die("Migration file 029 not found at: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split into individual queries (basic split by semicolon)
    // Note: detailed migrations might need better parsing, but this works for simple ADD COLUMN
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h3>Applying Migration 029...</h3>";
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        echo "<div style='background:#f0f0f0; padding:10px; margin:5px; border:1px solid #ccc;'>";
        echo "<strong>Executing:</strong> <pre>" . htmlspecialchars(substr($query, 0, 100)) . "...</pre>";
        
        try {
            $pdo->exec($query);
            echo "<span style='color:green; font-weight:bold;'>SUCCESS</span>";
        } catch (Exception $e) {
            // Ignore "Duplicate column" errors if already ran partially
            if (strpos($e->getMessage(), "Duplicate column") !== false) {
                 echo "<span style='color:orange;'>Skipped (Column exists)</span>";
            } else {
                 echo "<span style='color:red; font-weight:bold;'>ERROR: " . $e->getMessage() . "</span>";
            }
        }
        echo "</div>";
    }
    
    echo "<h2>Update Complete</h2>";
    echo "<p>Please <a href='debug_schema_check.php'>Check Schema</a> to verify columns were added.</p>";
    
} catch (Exception $e) {
    die("Connection Error: " . $e->getMessage());
}
