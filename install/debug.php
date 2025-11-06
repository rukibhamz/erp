<?php
// Debug helper for installation
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Installation Debug Information</h1>";

echo "<h2>PHP Configuration</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Display Errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
echo "Error Reporting: " . error_reporting() . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . " seconds\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "</pre>";

echo "<h2>Required Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'mysqli', 'curl', 'zip', 'gd', 'mbstring', 'json'];
echo "<ul>";
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo "<li>" . ($loaded ? "✅" : "❌") . " $ext: " . ($loaded ? "Loaded" : "NOT LOADED") . "</li>";
}
echo "</ul>";

echo "<h2>File Permissions</h2>";
$dirs = [
    dirname(__DIR__) . '/application/config',
    dirname(__DIR__) . '/logs',
    dirname(__DIR__) . '/uploads'
];
echo "<ul>";
foreach ($dirs as $dir) {
    $exists = is_dir($dir);
    $writable = $exists && is_writable($dir);
    echo "<li>" . basename($dir) . ": " . ($exists ? "✅ Exists" : "❌ Missing") . " " . ($writable ? "✅ Writable" : "❌ Not Writable") . "</li>";
}
echo "</ul>";

echo "<h2>Test Database Connection</h2>";
if (isset($_GET['test_db'])) {
    $host = $_GET['host'] ?? 'localhost';
    $user = $_GET['user'] ?? 'root';
    $pass = $_GET['pass'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color:green'>✅ Database connection successful!</p>";
        
        // Test creating a table
        $pdo->exec("CREATE DATABASE IF NOT EXISTS test_erp_db");
        $pdo->exec("USE test_erp_db");
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY)");
        $pdo->exec("DROP TABLE test_table");
        $pdo->exec("DROP DATABASE test_erp_db");
        echo "<p style='color:green'>✅ Table creation test successful!</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>Add ?test_db=1&host=localhost&user=root&pass=yourpassword to test database connection</p>";
}

echo "<h2>Recent Error Log</h2>";
$log_file = dirname(__DIR__) . '/logs/install_error.log';
if (file_exists($log_file)) {
    $lines = file($log_file);
    $recent = array_slice($lines, -20); // Last 20 lines
    echo "<pre style='background:#f5f5f5;padding:10px;max-height:400px;overflow:auto;'>";
    echo htmlspecialchars(implode('', $recent));
    echo "</pre>";
} else {
    echo "<p>No error log found at: $log_file</p>";
}

echo "<h2>Session Information</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<p><a href='index.php'>Back to Installer</a></p>";
?>

