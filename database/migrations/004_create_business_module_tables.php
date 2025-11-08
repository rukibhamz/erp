<?php
/**
 * Create Missing Business Module Tables
 * Creates all business module tables referenced by Dashboard and other controllers
 */

require_once __DIR__ . '/../../application/config/database.php';

try {
    $dbConfig = $db['default'];
    $host = $dbConfig['hostname'];
    $dbname = $dbConfig['database'];
    $username = $dbConfig['username'];
    $password = $dbConfig['password'];
    $prefix = $dbConfig['dbprefix'] ?? 'erp_';
    
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "========================================\n";
    echo "CREATE MISSING BUSINESS MODULE TABLES\n";
    echo "========================================\n\n";
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 1. Spaces table
    echo "Creating erp_spaces table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}spaces` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `property_id` INT(11) NOT NULL,
        `space_number` VARCHAR(50) DEFAULT NULL,
        `space_name` VARCHAR(255) NOT NULL,
        `parent_space_id` INT(11) DEFAULT NULL,
        `category` ENUM('event_space','commercial','hospitality','storage','parking','residential','other') NOT NULL,
        `space_type` VARCHAR(100) DEFAULT NULL,
        `floor` VARCHAR(50) DEFAULT NULL,
        `area` DECIMAL(10,2) DEFAULT NULL,
        `capacity` INT(11) DEFAULT NULL,
        `configuration` VARCHAR(255) DEFAULT NULL,
        `amenities` TEXT DEFAULT NULL,
        `accessibility_features` TEXT DEFAULT NULL,
        `operational_status` ENUM('active','under_maintenance','under_renovation','temporarily_closed','decommissioned') DEFAULT 'active',
        `operational_mode` ENUM('available_for_booking','leased','owner_operated','reserved','vacant') DEFAULT 'vacant',
        `status` ENUM('active','inactive') DEFAULT 'active',
        `is_bookable` TINYINT(1) DEFAULT 0,
        `facility_id` INT(11) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_property_id` (`property_id`),
        KEY `idx_status` (`status`),
        KEY `idx_operational_mode` (`operational_mode`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ erp_spaces table created/verified\n\n";
    
    // 2. Stock levels table
    echo "Creating erp_stock_levels table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}stock_levels` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `item_id` INT(11) NOT NULL,
        `location_id` INT(11) DEFAULT NULL,
        `quantity` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `reserved_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `available_qty` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `unit_cost` DECIMAL(15,2) DEFAULT 0,
        `reorder_level` DECIMAL(10,2) DEFAULT 0,
        `reorder_point` DECIMAL(10,2) DEFAULT 0,
        `last_movement_date` DATETIME DEFAULT NULL,
        `status` ENUM('active','inactive') DEFAULT 'active',
        `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_item_location` (`item_id`, `location_id`),
        KEY `idx_item_id` (`item_id`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ erp_stock_levels table created/verified\n\n";
    
    // 3. Items table
    echo "Creating erp_items table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `sku` VARCHAR(100) NOT NULL UNIQUE,
        `item_name` VARCHAR(255) NOT NULL,
        `name` VARCHAR(255) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `item_type` ENUM('inventory', 'non_inventory', 'service', 'fixed_asset') NOT NULL DEFAULT 'inventory',
        `category` VARCHAR(100) DEFAULT NULL,
        `reorder_point` DECIMAL(10,2) DEFAULT 0,
        `reorder_level` DECIMAL(10,2) DEFAULT 0,
        `item_status` ENUM('active', 'discontinued', 'out_of_stock') DEFAULT 'active',
        `status` ENUM('active','inactive') DEFAULT 'active',
        `cost_price` DECIMAL(15,2) DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_sku` (`sku`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ erp_items table created/verified\n\n";
    
    // 4. Leases table
    echo "Creating erp_leases table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}leases` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `lease_number` VARCHAR(50) NOT NULL UNIQUE,
        `space_id` INT(11) NOT NULL,
        `tenant_id` INT(11) NOT NULL,
        `lease_type` ENUM('commercial','residential','mixed') NOT NULL,
        `start_date` DATE NOT NULL,
        `end_date` DATE DEFAULT NULL,
        `rent_amount` DECIMAL(15,2) NOT NULL,
        `status` ENUM('active','expired','terminated','pending_renewal') DEFAULT 'active',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_lease_number` (`lease_number`),
        KEY `idx_space_id` (`space_id`),
        KEY `idx_status` (`status`),
        KEY `idx_end_date` (`end_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ erp_leases table created/verified\n\n";
    
    // 5. Work orders table
    echo "Creating erp_work_orders table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}work_orders` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `work_order_number` VARCHAR(50) NOT NULL UNIQUE,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `property_id` INT(11) DEFAULT NULL,
        `space_id` INT(11) DEFAULT NULL,
        `work_type` ENUM('maintenance','repair','inspection','cleaning','upgrade','other') DEFAULT 'maintenance',
        `priority` ENUM('low','medium','high','urgent') DEFAULT 'medium',
        `status` ENUM('pending','assigned','in_progress','completed','cancelled','on_hold') DEFAULT 'pending',
        `assigned_to` INT(11) DEFAULT NULL,
        `due_date` DATE DEFAULT NULL,
        `completed_date` DATE DEFAULT NULL,
        `estimated_cost` DECIMAL(15,2) DEFAULT 0,
        `actual_cost` DECIMAL(15,2) DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_work_order_number` (`work_order_number`),
        KEY `idx_status` (`status`),
        KEY `idx_due_date` (`due_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ erp_work_orders table created/verified\n\n";
    
    // 6. Tax deadlines table
    echo "Creating erp_tax_deadlines table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tax_deadlines` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `tax_type` VARCHAR(50) NOT NULL,
        `deadline_date` DATE NOT NULL,
        `deadline_type` ENUM('filing', 'payment') NOT NULL,
        `period_covered` VARCHAR(20) DEFAULT NULL,
        `status` ENUM('upcoming', 'due_today', 'overdue', 'completed') DEFAULT 'upcoming',
        `completed` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_deadline_date` (`deadline_date`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ erp_tax_deadlines table created/verified\n\n";
    
    // 7. Utility bills table
    echo "Creating erp_utility_bills table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}utility_bills` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `bill_number` VARCHAR(50) NOT NULL UNIQUE,
        `utility_type` ENUM('electricity','water','gas','internet','phone','sewage','trash','other') NOT NULL,
        `property_id` INT(11) DEFAULT NULL,
        `space_id` INT(11) DEFAULT NULL,
        `bill_date` DATE NOT NULL,
        `due_date` DATE NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `paid_amount` DECIMAL(15,2) DEFAULT 0,
        `balance_amount` DECIMAL(15,2) DEFAULT 0,
        `status` ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_bill_number` (`bill_number`),
        KEY `idx_status` (`status`),
        KEY `idx_bill_date` (`bill_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ erp_utility_bills table created/verified\n\n";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Verification
    echo "========================================\n";
    echo "VERIFICATION\n";
    echo "========================================\n";
    
    $tables = ['spaces', 'stock_levels', 'items', 'leases', 'work_orders', 'tax_deadlines', 'utility_bills'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM information_schema.tables 
        WHERE table_schema = DATABASE() AND table_name = ?");
    
    foreach ($tables as $table) {
        $stmt->execute([$prefix . $table]);
        $result = $stmt->fetch();
        echo ($result['count'] > 0 ? "✓" : "✗") . " {$prefix}{$table}\n";
    }
    
    echo "\n========================================\n";
    echo "MIGRATION COMPLETE\n";
    echo "========================================\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

