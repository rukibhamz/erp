<?php
/**
 * Database Migration Script for New Features (Wholesale Pricing & Education Tax)
 */

function runNewFeatureMigrations($pdo, $prefix = 'erp_') {
    try {
        // --- WHOLESALE PRICING ---

        // Customer Types
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}customer_types` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `code` VARCHAR(50) NOT NULL UNIQUE,
            `discount_percentage` DECIMAL(5,2) DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Insert default customer types
        $pdo->exec("INSERT IGNORE INTO `{$prefix}customer_types` (name, code, discount_percentage) VALUES 
            ('Retail Customer', 'RETAIL', 0),
            ('Wholesale Customer', 'WHOLESALE', 0)");

        // Discount Tiers
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}discount_tiers` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `min_quantity` DECIMAL(10,2) NOT NULL,
            `discount_type` ENUM('percentage', 'fixed_price') DEFAULT 'percentage',
            `discount_value` DECIMAL(15,2) NOT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_item_qty` (`item_id`, `min_quantity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Price History
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}price_history` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `item_id` INT(11) NOT NULL,
            `old_retail_price` DECIMAL(15,2),
            `new_retail_price` DECIMAL(15,2),
            `old_wholesale_price` DECIMAL(15,2),
            `new_wholesale_price` DECIMAL(15,2),
            `changed_by` INT(11),
            `change_reason` VARCHAR(255),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_item` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // --- EDUCATION TAX (NIGERIA) ---

        // Education Tax Config
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}education_tax_config` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `tax_year` YEAR NOT NULL UNIQUE,
            `tax_rate` DECIMAL(5,2) DEFAULT 2.50,
            `deadline_date` DATE NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Education Tax Returns
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}education_tax_returns` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `tax_year` YEAR NOT NULL UNIQUE,
            `assessable_profit` DECIMAL(15,2) NOT NULL,
            `tax_amount` DECIMAL(15,2) NOT NULL,
            `paid_amount` DECIMAL(15,2) DEFAULT 0,
            `filing_date` DATE NOT NULL,
            `status` ENUM('draft', 'filed', 'paid', 'overdue') DEFAULT 'filed',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Education Tax Payments
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}education_tax_payments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `return_id` INT(11) NOT NULL,
            `tax_year` YEAR NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `payment_date` DATE NOT NULL,
            `payment_reference` VARCHAR(100),
            `payment_method` VARCHAR(50),
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_year` (`tax_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // --- EDUCATION TAX (NIGERIA) ---
        // ... (existing education tax tables) ...

        // Education Tax Summary View
        $pdo->exec("CREATE OR REPLACE VIEW `{$prefix}vw_education_tax_summary` AS
            SELECT 
                r.tax_year,
                r.assessable_profit,
                r.tax_amount as total_liability,
                r.paid_amount as total_paid,
                (r.tax_amount - r.paid_amount) as balance_due,
                r.status,
                c.deadline_date
            FROM `{$prefix}education_tax_returns` r
            LEFT JOIN `{$prefix}education_tax_config` c ON r.tax_year = c.tax_year");

        // --- INVENTORY REPORT VIEWS ---
        $pdo->exec("CREATE OR REPLACE VIEW `{$prefix}vw_inventory_valuation` AS
            SELECT 
                i.id as item_id,
                i.sku,
                i.item_name,
                i.category,
                SUM(sl.quantity) as total_quantity,
                i.average_cost,
                (SUM(sl.quantity) * i.average_cost) as total_value
            FROM `{$prefix}items` i
            JOIN `{$prefix}stock_levels` sl ON i.id = sl.item_id
            GROUP BY i.id");

        return true;
    } catch (Exception $e) {
        error_log("New features migration error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fix existing tables by adding missing columns
 */
function fixNewFeatureColumns($pdo, $prefix = 'erp_') {
    $columnExists = function($table, $column) use ($pdo, $prefix) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$prefix}{$table}` LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    };

    // Items table updates
    if (!$columnExists('items', 'wholesale_moq')) {
        $pdo->exec("ALTER TABLE `{$prefix}items` ADD COLUMN `wholesale_moq` DECIMAL(10,2) DEFAULT 0 AFTER `wholesale_price`");
    }
    if (!$columnExists('items', 'discount_moq')) {
        $pdo->exec("ALTER TABLE `{$prefix}items` ADD COLUMN `discount_moq` DECIMAL(10,2) DEFAULT 0 AFTER `wholesale_moq`");
    }
    if (!$columnExists('items', 'is_wholesale_enabled')) {
        $pdo->exec("ALTER TABLE `{$prefix}items` ADD COLUMN `is_wholesale_enabled` TINYINT(1) DEFAULT 0 AFTER `discount_moq`");
    }

    // Customers table updates
    if (!$columnExists('customers', 'customer_type_id')) {
        $pdo->exec("ALTER TABLE `{$prefix}customers` ADD COLUMN `customer_type_id` INT(11) DEFAULT NULL AFTER `id`");
        $pdo->exec("ALTER TABLE `{$prefix}customers` ADD INDEX `idx_customer_type` (`customer_type_id`)");
    }
    
    // --- BOOKING CONFIGURATION & FACILITIES ---
    
    // Facilities table updates for rates
    if (!$columnExists('facilities', 'half_day_rate')) {
        $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `half_day_rate` DECIMAL(15,2) DEFAULT 0.00 AFTER `daily_rate`");
    }
    if (!$columnExists('facilities', 'weekly_rate')) {
        $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `weekly_rate` DECIMAL(15,2) DEFAULT 0.00 AFTER `half_day_rate`");
    }
    if (!$columnExists('facilities', 'is_bookable')) {
        $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `is_bookable` TINYINT(1) DEFAULT 1 AFTER `status`");
    }
    if (!$columnExists('facilities', 'max_duration')) {
        $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `max_duration` INT(11) DEFAULT NULL COMMENT 'Max duration in hours' AFTER `minimum_duration`");
    }
    if (!$columnExists('facilities', 'resource_type')) {
        $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `resource_type` VARCHAR(50) DEFAULT 'other' AFTER `features`");
    }
    if (!$columnExists('facilities', 'category')) {
        $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `category` VARCHAR(100) DEFAULT NULL AFTER `resource_type`");
    }
    if (!$columnExists('facilities', 'simultaneous_limit')) {
        $pdo->exec("ALTER TABLE `{$prefix}facilities` ADD COLUMN `simultaneous_limit` INT(11) DEFAULT 1 AFTER `max_duration`");
    }
    
    // Bookable Config Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}bookable_config` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `space_id` int(11) NOT NULL,
        `is_bookable` tinyint(1) DEFAULT 1,
        `booking_types` text DEFAULT NULL,
        `minimum_duration` int(11) DEFAULT 1,
        `maximum_duration` int(11) DEFAULT NULL,
        `advance_booking_days` int(11) DEFAULT 365,
        `cancellation_policy_id` int(11) DEFAULT NULL,
        `pricing_rules` text DEFAULT NULL,
        `availability_rules` text DEFAULT NULL,
        `setup_time_buffer` int(11) DEFAULT 0,
        `cleanup_time_buffer` int(11) DEFAULT 0,
        `simultaneous_limit` int(11) DEFAULT 1,
        `last_synced_at` datetime DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `space_id` (`space_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Fix existing Bookable Config columns
    if (!$columnExists('bookable_config', 'pricing_rules')) {
        $pdo->exec("ALTER TABLE `{$prefix}bookable_config` ADD COLUMN `pricing_rules` TEXT DEFAULT NULL AFTER `cancellation_policy_id`");
    }
    if (!$columnExists('bookable_config', 'booking_types')) {
        $pdo->exec("ALTER TABLE `{$prefix}bookable_config` ADD COLUMN `booking_types` TEXT DEFAULT NULL AFTER `is_bookable`");
    }
}
