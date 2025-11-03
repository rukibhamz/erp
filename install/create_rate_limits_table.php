<?php
/**
 * Quick script to create the rate_limits table
 * Run this if the table is missing
 * Usage: php create_rate_limits_table.php
 */

// Define BASEPATH if not defined
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/application/');
}

// Load config - it will define BASEPATH if needed
$configFile = __DIR__ . '/../application/config/config.installed.php';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/../application/config/config.php';
}

if (!file_exists($configFile)) {
    die("ERROR: Configuration file not found.\nPlease run the installer first.\n");
}

// Load config file
$config = require $configFile;

if (!isset($config['installed']) || $config['installed'] !== true) {
    die("ERROR: Application not installed.\nPlease run the installer first.\n");
}

$dbConfig = $config['db'] ?? null;
if (!$dbConfig || empty($dbConfig['hostname']) || empty($dbConfig['database'])) {
    die("ERROR: Database configuration not found or incomplete.\n");
}

$prefix = $dbConfig['dbprefix'] ?? 'erp_';
$hostname = $dbConfig['hostname'];
$database = $dbConfig['database'];
$username = $dbConfig['username'];
$password = $dbConfig['password'] ?? '';
$charset = $dbConfig['charset'] ?? 'utf8mb4';

try {
    // Connect to database
    $dsn = "mysql:host={$hostname};dbname={$database};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to database successfully.\n";
    echo "Creating security tables...\n\n";
    
    // Create rate_limits table
    $sql = "CREATE TABLE IF NOT EXISTS `{$prefix}rate_limits` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `identifier` VARCHAR(255) NOT NULL COMMENT 'Username, email, or IP',
        `ip_address` VARCHAR(45) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `identifier` (`identifier`),
        KEY `ip_address` (`ip_address`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Created {$prefix}rate_limits table\n";
    
    // Create ip_restrictions table if missing
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$prefix}ip_restrictions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `ip_address` VARCHAR(45) NOT NULL,
        `type` ENUM('whitelist', 'blacklist') NOT NULL,
        `description` VARCHAR(255) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `ip_type` (`ip_address`, `type`),
        KEY `type` (`type`),
        KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql2);
    echo "✓ Created {$prefix}ip_restrictions table\n";
    
    // Create security_log table if missing
    $sql3 = "CREATE TABLE IF NOT EXISTS `{$prefix}security_log` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11) DEFAULT NULL,
        `ip_address` VARCHAR(45) NOT NULL,
        `action` VARCHAR(100) NOT NULL COMMENT 'login_failed, login_success, permission_denied, etc',
        `details` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `ip_address` (`ip_address`),
        KEY `action` (`action`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql3);
    echo "✓ Created {$prefix}security_log table\n\n";
    
    echo "SUCCESS! All security tables have been created.\n";
    
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}
