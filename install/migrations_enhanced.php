<?php
/**
 * Enhanced Database Migration Script for Production Accounting System
 * Adds comprehensive features: estimates, taxes, budgets, products, etc.
 */

function runEnhancedMigrations($pdo, $prefix = 'erp_') {
    $enhancedMigrations = [
        // Products/Items Catalog
        'products' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}products` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `product_code` varchar(50) NOT NULL,
                `product_name` varchar(255) NOT NULL,
                `description` text DEFAULT NULL,
                `type` enum('product','service') NOT NULL DEFAULT 'product',
                `category` varchar(100) DEFAULT NULL,
                `unit_price` decimal(15,2) DEFAULT 0.00,
                `cost_price` decimal(15,2) DEFAULT 0.00,
                `tax_id` int(11) DEFAULT NULL,
                `account_id` int(11) DEFAULT NULL,
                `inventory_tracked` tinyint(1) DEFAULT 0,
                `stock_quantity` decimal(10,2) DEFAULT 0.00,
                `reorder_level` decimal(10,2) DEFAULT 0.00,
                `unit_of_measure` varchar(20) DEFAULT 'unit',
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `product_code` (`product_code`),
                KEY `type` (`type`),
                KEY `category` (`category`),
                KEY `tax_id` (`tax_id`),
                KEY `account_id` (`account_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Tax Management
        'taxes' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}taxes` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `tax_name` varchar(100) NOT NULL,
                `tax_code` varchar(50) DEFAULT NULL,
                `tax_type` enum('percentage','fixed','compound') NOT NULL DEFAULT 'percentage',
                `rate` decimal(5,2) NOT NULL DEFAULT 0.00,
                `tax_inclusive` tinyint(1) DEFAULT 0,
                `description` text DEFAULT NULL,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `tax_code` (`tax_code`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'tax_groups' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}tax_groups` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `group_name` varchar(100) NOT NULL,
                `description` text DEFAULT NULL,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'tax_group_items' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}tax_group_items` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `tax_group_id` int(11) NOT NULL,
                `tax_id` int(11) NOT NULL,
                `sequence` int(11) DEFAULT 1,
                PRIMARY KEY (`id`),
                KEY `tax_group_id` (`tax_group_id`),
                KEY `tax_id` (`tax_id`),
                CONSTRAINT `{$prefix}tax_group_items_ibfk_1` FOREIGN KEY (`tax_group_id`) REFERENCES `{$prefix}tax_groups` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}tax_group_items_ibfk_2` FOREIGN KEY (`tax_id`) REFERENCES `{$prefix}taxes` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Estimates/Quotes
        'estimates' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}estimates` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `estimate_number` varchar(50) NOT NULL,
                `customer_id` int(11) NOT NULL,
                `estimate_date` date NOT NULL,
                `expiry_date` date NOT NULL,
                `reference` varchar(100) DEFAULT NULL,
                `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
                `tax_rate` decimal(5,2) DEFAULT 0.00,
                `tax_amount` decimal(15,2) DEFAULT 0.00,
                `discount_amount` decimal(15,2) DEFAULT 0.00,
                `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `currency` varchar(10) DEFAULT 'USD',
                `terms` text DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `status` enum('draft','sent','accepted','rejected','converted') NOT NULL DEFAULT 'draft',
                `converted_to_invoice_id` int(11) DEFAULT NULL,
                `template_id` int(11) DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `estimate_number` (`estimate_number`),
                KEY `customer_id` (`customer_id`),
                KEY `estimate_date` (`estimate_date`),
                KEY `expiry_date` (`expiry_date`),
                KEY `status` (`status`),
                KEY `converted_to_invoice_id` (`converted_to_invoice_id`),
                CONSTRAINT `{$prefix}estimates_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `{$prefix}customers` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}estimates_ibfk_2` FOREIGN KEY (`converted_to_invoice_id`) REFERENCES `{$prefix}invoices` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}estimates_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'estimate_items' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}estimate_items` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `estimate_id` int(11) NOT NULL,
                `product_id` int(11) DEFAULT NULL,
                `item_description` varchar(255) NOT NULL,
                `quantity` decimal(10,2) DEFAULT 1.00,
                `unit_price` decimal(15,2) NOT NULL,
                `tax_rate` decimal(5,2) DEFAULT 0.00,
                `tax_amount` decimal(15,2) DEFAULT 0.00,
                `discount_rate` decimal(5,2) DEFAULT 0.00,
                `discount_amount` decimal(15,2) DEFAULT 0.00,
                `line_total` decimal(15,2) NOT NULL,
                `account_id` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `estimate_id` (`estimate_id`),
                KEY `product_id` (`product_id`),
                KEY `account_id` (`account_id`),
                CONSTRAINT `{$prefix}estimate_items_ibfk_1` FOREIGN KEY (`estimate_id`) REFERENCES `{$prefix}estimates` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}estimate_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `{$prefix}products` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}estimate_items_ibfk_3` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Invoice/Bill Templates
        'templates' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}templates` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `template_name` varchar(255) NOT NULL,
                `template_type` enum('invoice','estimate','bill','payslip') NOT NULL,
                `template_html` text NOT NULL,
                `is_default` tinyint(1) DEFAULT 0,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `template_type` (`template_type`),
                KEY `is_default` (`is_default`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Enhanced Invoices and Invoice Items columns are handled in migrations_alter.php
        
        // Credit Notes
        'credit_notes' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}credit_notes` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `credit_note_number` varchar(50) NOT NULL,
                `invoice_id` int(11) DEFAULT NULL,
                `customer_id` int(11) NOT NULL,
                `credit_date` date NOT NULL,
                `reference` varchar(100) DEFAULT NULL,
                `reason` text DEFAULT NULL,
                `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
                `tax_amount` decimal(15,2) DEFAULT 0.00,
                `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `currency` varchar(10) DEFAULT 'USD',
                `status` enum('draft','issued','applied','void') NOT NULL DEFAULT 'draft',
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `credit_note_number` (`credit_note_number`),
                KEY `invoice_id` (`invoice_id`),
                KEY `customer_id` (`customer_id`),
                KEY `credit_date` (`credit_date`),
                KEY `status` (`status`),
                CONSTRAINT `{$prefix}credit_notes_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `{$prefix}invoices` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}credit_notes_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `{$prefix}customers` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}credit_notes_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'credit_note_items' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}credit_note_items` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `credit_note_id` int(11) NOT NULL,
                `product_id` int(11) DEFAULT NULL,
                `item_description` varchar(255) NOT NULL,
                `quantity` decimal(10,2) DEFAULT 1.00,
                `unit_price` decimal(15,2) NOT NULL,
                `line_total` decimal(15,2) NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `credit_note_id` (`credit_note_id`),
                KEY `product_id` (`product_id`),
                CONSTRAINT `{$prefix}credit_note_items_ibfk_1` FOREIGN KEY (`credit_note_id`) REFERENCES `{$prefix}credit_notes` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}credit_note_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `{$prefix}products` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Payment Allocations (for partial payments)
        'payment_allocations' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}payment_allocations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `payment_id` int(11) NOT NULL,
                `invoice_id` int(11) DEFAULT NULL,
                `bill_id` int(11) DEFAULT NULL,
                `amount` decimal(15,2) NOT NULL,
                `discount_taken` decimal(15,2) DEFAULT 0.00,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `payment_id` (`payment_id`),
                KEY `invoice_id` (`invoice_id`),
                KEY `bill_id` (`bill_id`),
                CONSTRAINT `{$prefix}payment_allocations_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `{$prefix}payments` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}payment_allocations_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `{$prefix}invoices` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}payment_allocations_ibfk_3` FOREIGN KEY (`bill_id`) REFERENCES `{$prefix}bills` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Enhanced Payments columns are handled in migrations_alter.php
        
        // Bank Transactions for Reconciliation
        'bank_transactions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}bank_transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `cash_account_id` int(11) NOT NULL,
                `transaction_date` date NOT NULL,
                `transaction_type` enum('deposit','withdrawal','transfer','fee','interest','other') NOT NULL,
                `amount` decimal(15,2) NOT NULL,
                `currency` varchar(10) DEFAULT 'USD',
                `payee` varchar(255) DEFAULT NULL,
                `category` varchar(100) DEFAULT NULL,
                `reference` varchar(100) DEFAULT NULL,
                `check_number` varchar(50) DEFAULT NULL,
                `cleared` tinyint(1) DEFAULT 0,
                `cleared_date` date DEFAULT NULL,
                `reconciliation_id` int(11) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `cash_account_id` (`cash_account_id`),
                KEY `transaction_date` (`transaction_date`),
                KEY `transaction_type` (`transaction_type`),
                KEY `cleared` (`cleared`),
                KEY `reconciliation_id` (`reconciliation_id`),
                CONSTRAINT `{$prefix}bank_transactions_ibfk_1` FOREIGN KEY (`cash_account_id`) REFERENCES `{$prefix}cash_accounts` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}bank_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Enhanced Bank Reconciliations columns are handled in migrations_alter.php
        
        // Multi-Currency Support
        'currencies' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}currencies` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `currency_code` varchar(10) NOT NULL,
                `currency_name` varchar(100) NOT NULL,
                `symbol` varchar(10) DEFAULT NULL,
                `exchange_rate` decimal(10,6) DEFAULT 1.000000,
                `is_base` tinyint(1) DEFAULT 0,
                `position` enum('before','after') DEFAULT 'before',
                `precision` int(11) DEFAULT 2,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `currency_code` (`currency_code`),
                KEY `is_base` (`is_base`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'currency_rates' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}currency_rates` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `from_currency` varchar(10) NOT NULL,
                `to_currency` varchar(10) NOT NULL,
                `rate` decimal(10,6) NOT NULL,
                `rate_date` date NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `from_currency` (`from_currency`),
                KEY `to_currency` (`to_currency`),
                KEY `rate_date` (`rate_date`),
                UNIQUE KEY `currency_date` (`from_currency`, `to_currency`, `rate_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Budgeting
        'budgets' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}budgets` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `budget_name` varchar(255) NOT NULL,
                `financial_year_id` int(11) NOT NULL,
                `account_id` int(11) NOT NULL,
                `january` decimal(15,2) DEFAULT 0.00,
                `february` decimal(15,2) DEFAULT 0.00,
                `march` decimal(15,2) DEFAULT 0.00,
                `april` decimal(15,2) DEFAULT 0.00,
                `may` decimal(15,2) DEFAULT 0.00,
                `june` decimal(15,2) DEFAULT 0.00,
                `july` decimal(15,2) DEFAULT 0.00,
                `august` decimal(15,2) DEFAULT 0.00,
                `september` decimal(15,2) DEFAULT 0.00,
                `october` decimal(15,2) DEFAULT 0.00,
                `november` decimal(15,2) DEFAULT 0.00,
                `december` decimal(15,2) DEFAULT 0.00,
                `total` decimal(15,2) DEFAULT 0.00,
                `status` enum('draft','active','closed') NOT NULL DEFAULT 'draft',
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `financial_year_id` (`financial_year_id`),
                KEY `account_id` (`account_id`),
                KEY `status` (`status`),
                CONSTRAINT `{$prefix}budgets_ibfk_1` FOREIGN KEY (`financial_year_id`) REFERENCES `{$prefix}financial_years` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}budgets_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}budgets_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Enhanced Journal Entries with journal_type and attachments
        'journal_entries_enhance' => "
            ALTER TABLE `{$prefix}journal_entries`
            ADD COLUMN IF NOT EXISTS `journal_type` enum('sales','purchases','cash','bank','general','adjustment') DEFAULT 'general' AFTER `entry_number`,
            ADD COLUMN IF NOT EXISTS `recurring` tinyint(1) DEFAULT 0 AFTER `status`,
            ADD COLUMN IF NOT EXISTS `recurring_frequency` enum('daily','weekly','monthly','quarterly','annually') DEFAULT NULL AFTER `recurring`,
            ADD COLUMN IF NOT EXISTS `recurring_next_date` date DEFAULT NULL AFTER `recurring_frequency`,
            ADD COLUMN IF NOT EXISTS `recurring_end_date` date DEFAULT NULL AFTER `recurring_next_date`,
            ADD COLUMN IF NOT EXISTS `reversed_entry_id` int(11) DEFAULT NULL AFTER `recurring_end_date`,
            ADD INDEX IF NOT EXISTS `journal_type` (`journal_type`),
            ADD INDEX IF NOT EXISTS `reversed_entry_id` (`reversed_entry_id`);
        ",
        
        'journal_entry_attachments' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}journal_entry_attachments` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `journal_entry_id` int(11) NOT NULL,
                `file_name` varchar(255) NOT NULL,
                `file_path` varchar(500) NOT NULL,
                `file_size` int(11) DEFAULT NULL,
                `mime_type` varchar(100) DEFAULT NULL,
                `uploaded_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `journal_entry_id` (`journal_entry_id`),
                KEY `uploaded_by` (`uploaded_by`),
                CONSTRAINT `{$prefix}journal_entry_attachments_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `{$prefix}journal_entries` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}journal_entry_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Period Locking
        'period_locks' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}period_locks` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `financial_year_id` int(11) NOT NULL,
                `period_month` int(11) NOT NULL,
                `period_year` int(11) NOT NULL,
                `locked` tinyint(1) DEFAULT 0,
                `locked_at` datetime DEFAULT NULL,
                `locked_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `financial_year_id` (`financial_year_id`),
                KEY `period_month` (`period_month`),
                KEY `period_year` (`period_year`),
                KEY `locked` (`locked`),
                UNIQUE KEY `period_lock` (`financial_year_id`, `period_month`, `period_year`),
                CONSTRAINT `{$prefix}period_locks_ibfk_1` FOREIGN KEY (`financial_year_id`) REFERENCES `{$prefix}financial_years` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}period_locks_ibfk_2` FOREIGN KEY (`locked_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Enhanced Employees and Payroll columns are handled in migrations_alter.php
        
        // Recurring Transactions
        'recurring_transactions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}recurring_transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `transaction_type` enum('invoice','bill','payment','journal') NOT NULL,
                `transaction_id` int(11) DEFAULT NULL,
                `frequency` enum('daily','weekly','monthly','quarterly','annually') NOT NULL,
                `start_date` date NOT NULL,
                `end_date` date DEFAULT NULL,
                `next_run_date` date NOT NULL,
                `status` enum('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `transaction_type` (`transaction_type`),
                KEY `next_run_date` (`next_run_date`),
                KEY `status` (`status`),
                CONSTRAINT `{$prefix}recurring_transactions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // Audit Trail
        'audit_trail' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}audit_trail` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `table_name` varchar(100) NOT NULL,
                `record_id` int(11) NOT NULL,
                `action` enum('create','update','delete','void','reverse') NOT NULL,
                `old_values` text DEFAULT NULL,
                `new_values` text DEFAULT NULL,
                `changed_fields` text DEFAULT NULL,
                `user_id` int(11) DEFAULT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `table_name` (`table_name`),
                KEY `record_id` (`record_id`),
                KEY `action` (`action`),
                KEY `user_id` (`user_id`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
    ];
    
    foreach ($enhancedMigrations as $migration => $sql) {
        try {
            // Check if ALTER TABLE, handle gracefully
            if (strpos($sql, 'ALTER TABLE') === 0) {
                // For ALTER TABLE, we need to handle column existence checks differently
                // MySQL doesn't support IF NOT EXISTS for columns, so we'll catch errors
                try {
                    $pdo->exec($sql);
                } catch (PDOException $e) {
                    // Ignore "Duplicate column name" errors
                    if (strpos($e->getMessage(), 'Duplicate column') === false && 
                        strpos($e->getMessage(), 'Duplicate key') === false &&
                        strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            } else {
                $pdo->exec($sql);
            }
        } catch (PDOException $e) {
            // Log but don't throw for existing tables/columns
            error_log("Migration warning for {$migration}: " . $e->getMessage());
        }
    }
    
    // Insert default data
    insertDefaultAccountingData($pdo, $prefix);
}

function insertDefaultAccountingData($pdo, $prefix) {
    // Insert default tax rates
    $defaultTaxes = [
        ['VAT', 'VAT', 'percentage', 15.00, 0, 'Value Added Tax'],
        ['Sales Tax', 'ST', 'percentage', 8.00, 0, 'Sales Tax'],
        ['Service Tax', 'SVCTAX', 'percentage', 10.00, 0, 'Service Tax'],
    ];
    
    foreach ($defaultTaxes as $tax) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}taxes` 
                (tax_name, tax_code, tax_type, rate, tax_inclusive, description, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->execute($tax);
        } catch (PDOException $e) {
            // Ignore duplicates
        }
    }
    
    // Insert base currency (USD)
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}currencies` 
            (currency_code, currency_name, symbol, exchange_rate, is_base, position, precision, status, updated_at) 
            VALUES ('USD', 'US Dollar', '$', 1.000000, 1, 'before', 2, 'active', NOW())");
        $stmt->execute();
    } catch (PDOException $e) {
        // Ignore duplicates
    }
    
    // Insert default invoice template
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}templates` 
            (template_name, template_type, template_html, is_default, status, created_at) 
            VALUES ('Default Invoice', 'invoice', '<div class=\"invoice-template\"><h1>Invoice</h1></div>', 1, 'active', NOW())");
        $stmt->execute();
    } catch (PDOException $e) {
        // Ignore duplicates
    }
}

