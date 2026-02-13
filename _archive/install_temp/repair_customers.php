<?php
define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
require_once BASEPATH . 'core/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$prefix = $db->getPrefix();

echo "Checking customers table...\n";

try {
    // Try a simple query
    $result = $pdo->query("SELECT COUNT(*) FROM `{$prefix}customers`");
    $count = $result->fetchColumn();
    echo "customers table OK: {$count} records\n";
} catch (PDOException $e) {
    echo "customers table ERROR: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), "doesn't exist in engine") !== false) {
        echo "\nInnoDB corruption detected. Attempting repair...\n";
        
        // Try to recreate from the migration
        echo "Step 1: Getting CREATE TABLE from migrations...\n";
        $migrationsFile = __DIR__ . '/migrations.php';
        if (file_exists($migrationsFile)) {
            $content = file_get_contents($migrationsFile);
            
            // Extract the customers CREATE TABLE statement
            if (preg_match('/CREATE TABLE.*?`' . preg_quote($prefix) . 'customers`.*?;/s', $content, $match)) {
                echo "Found CREATE TABLE for customers\n";
            } else {
                echo "Could not find CREATE TABLE for customers in migrations\n";
            }
        }
        
        // Try ALTER TABLE to repair
        try {
            $pdo->exec("ALTER TABLE `{$prefix}customers` ENGINE=InnoDB");
            echo "Repair attempt via ALTER TABLE ENGINE=InnoDB\n";
        } catch (PDOException $e2) {
            echo "ALTER TABLE repair failed: " . $e2->getMessage() . "\n";
        }
        
        // If that fails, try DROP and recreate
        echo "\nStep 2: Dropping corrupted table and recreating...\n";
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            $pdo->exec("DROP TABLE IF EXISTS `{$prefix}customers`");
            echo "Dropped corrupted table\n";
            
            $sql = "CREATE TABLE `{$prefix}customers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `customer_code` VARCHAR(30) NULL,
                `company_name` VARCHAR(255) NOT NULL DEFAULT '',
                `contact_name` VARCHAR(255) DEFAULT '',
                `email` VARCHAR(255),
                `phone` VARCHAR(50),
                `address` TEXT,
                `city` VARCHAR(100),
                `state` VARCHAR(100),
                `zip_code` VARCHAR(20),
                `country` VARCHAR(100),
                `tax_id` VARCHAR(50),
                `credit_limit` DECIMAL(15,2) DEFAULT 0.00,
                `current_balance` DECIMAL(15,2) DEFAULT 0.00,
                `payment_terms` VARCHAR(50),
                `currency` VARCHAR(10) DEFAULT 'USD',
                `customer_type_id` INT NULL,
                `status` ENUM('active','inactive','suspended') DEFAULT 'active',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (`email`),
                INDEX idx_customer_code (`customer_code`),
                INDEX idx_status (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($sql);
            echo "Recreated customers table successfully!\n";
            
            // Verify
            $result = $pdo->query("SELECT COUNT(*) FROM `{$prefix}customers`");
            $count = $result->fetchColumn();
            echo "Verified: customers table working ({$count} records)\n";
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            
        } catch (PDOException $e3) {
            echo "Recreate failed: " . $e3->getMessage() . "\n";
        }
    }
}
