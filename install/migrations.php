<?php
/**
 * Database Migration Script
 * Creates all required database tables
 */

function runMigrations($pdo, $prefix = 'erp_') {
    $migrations = [
        'users' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `email` varchar(100) NOT NULL,
                `password` varchar(255) NOT NULL,
                `first_name` varchar(100) DEFAULT NULL,
                `last_name` varchar(100) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `avatar` varchar(255) DEFAULT NULL,
                `role` enum('super_admin','admin','manager','staff','user') NOT NULL DEFAULT 'user',
                `status` enum('active','inactive','suspended','locked') NOT NULL DEFAULT 'active',
                `failed_login_attempts` int(11) DEFAULT 0,
                `locked_until` datetime DEFAULT NULL,
                `remember_token` varchar(100) DEFAULT NULL,
                `password_reset_token` varchar(100) DEFAULT NULL,
                `password_reset_expires` datetime DEFAULT NULL,
                `two_factor_secret` varchar(255) DEFAULT NULL,
                `two_factor_enabled` tinyint(1) DEFAULT 0,
                `last_login` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`),
                UNIQUE KEY `email` (`email`),
                KEY `status` (`status`),
                KEY `role` (`role`),
                KEY `password_reset_token` (`password_reset_token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'permissions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module` varchar(50) NOT NULL,
                `permission` varchar(50) NOT NULL,
                `description` varchar(255) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `module_permission` (`module`, `permission`),
                KEY `module` (`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'user_permissions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}user_permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `permission_id` int(11) NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_permission` (`user_id`, `permission_id`),
                KEY `user_id` (`user_id`),
                KEY `permission_id` (`permission_id`),
                CONSTRAINT `{$prefix}user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `{$prefix}permissions` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'sessions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}sessions` (
                `id` varchar(128) NOT NULL,
                `user_id` int(11) DEFAULT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `last_activity` int(11) NOT NULL,
                `data` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `last_activity` (`last_activity`),
                CONSTRAINT `{$prefix}sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'companies' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}companies` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `address` text DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `zip_code` varchar(20) DEFAULT NULL,
                `country` varchar(100) DEFAULT NULL,
                `phone` varchar(50) DEFAULT NULL,
                `email` varchar(100) DEFAULT NULL,
                `website` varchar(255) DEFAULT NULL,
                `tax_id` varchar(100) DEFAULT NULL,
                `currency` varchar(10) DEFAULT 'USD',
                `logo` varchar(255) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'modules_settings' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}modules_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module_name` varchar(100) NOT NULL,
                `settings_json` text DEFAULT NULL,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `module_name` (`module_name`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'settings' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(100) NOT NULL,
                `setting_value` text DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'activity_log' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}activity_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) DEFAULT NULL,
                `action` varchar(100) NOT NULL,
                `module` varchar(50) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `module` (`module`),
                KEY `created_at` (`created_at`),
                CONSTRAINT `{$prefix}activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        // ========== ACCOUNTING MODULE TABLES ==========
        
        'accounts' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}accounts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `account_code` varchar(50) NOT NULL,
                `account_name` varchar(255) NOT NULL,
                `account_type` enum('Assets','Liabilities','Equity','Revenue','Expenses') NOT NULL,
                `parent_id` int(11) DEFAULT NULL,
                `opening_balance` decimal(15,2) DEFAULT 0.00,
                `balance` decimal(15,2) DEFAULT 0.00,
                `currency` varchar(10) DEFAULT 'USD',
                `description` text DEFAULT NULL,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `account_code` (`account_code`),
                KEY `account_type` (`account_type`),
                KEY `parent_id` (`parent_id`),
                KEY `status` (`status`),
                KEY `created_by` (`created_by`),
                CONSTRAINT `{$prefix}accounts_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}accounts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'journal_entries' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}journal_entries` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `entry_number` varchar(50) NOT NULL,
                `entry_date` date NOT NULL,
                `reference` varchar(100) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `amount` decimal(15,2) NOT NULL,
                `status` enum('draft','pending','approved','rejected','posted') NOT NULL DEFAULT 'draft',
                `approved_by` int(11) DEFAULT NULL,
                `approved_at` datetime DEFAULT NULL,
                `posted_by` int(11) DEFAULT NULL,
                `posted_at` datetime DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `entry_number` (`entry_number`),
                KEY `entry_date` (`entry_date`),
                KEY `status` (`status`),
                KEY `created_by` (`created_by`),
                KEY `approved_by` (`approved_by`),
                KEY `posted_by` (`posted_by`),
                CONSTRAINT `{$prefix}journal_entries_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}journal_entries_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}journal_entries_ibfk_3` FOREIGN KEY (`posted_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'journal_entry_lines' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}journal_entry_lines` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `journal_entry_id` int(11) NOT NULL,
                `account_id` int(11) NOT NULL,
                `description` text DEFAULT NULL,
                `debit` decimal(15,2) DEFAULT 0.00,
                `credit` decimal(15,2) DEFAULT 0.00,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `journal_entry_id` (`journal_entry_id`),
                KEY `account_id` (`account_id`),
                CONSTRAINT `{$prefix}journal_entry_lines_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `{$prefix}journal_entries` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}journal_entry_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'transactions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `transaction_number` varchar(50) NOT NULL,
                `transaction_date` date NOT NULL,
                `transaction_type` enum('receipt','payment','transfer','journal','invoice','bill','payroll') NOT NULL,
                `reference_id` int(11) DEFAULT NULL,
                `reference_type` varchar(50) DEFAULT NULL,
                `account_id` int(11) NOT NULL,
                `description` text DEFAULT NULL,
                `debit` decimal(15,2) DEFAULT 0.00,
                `credit` decimal(15,2) DEFAULT 0.00,
                `balance` decimal(15,2) DEFAULT 0.00,
                `currency` varchar(10) DEFAULT 'USD',
                `status` enum('draft','pending','posted','reversed','cancelled') NOT NULL DEFAULT 'posted',
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `transaction_number` (`transaction_number`),
                KEY `transaction_date` (`transaction_date`),
                KEY `transaction_type` (`transaction_type`),
                KEY `account_id` (`account_id`),
                KEY `status` (`status`),
                KEY `created_by` (`created_by`),
                CONSTRAINT `{$prefix}transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'cash_accounts' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}cash_accounts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `account_name` varchar(255) NOT NULL,
                `account_type` enum('petty_cash','bank_account','cash_register') NOT NULL,
                `account_id` int(11) NOT NULL,
                `bank_name` varchar(255) DEFAULT NULL,
                `account_number` varchar(100) DEFAULT NULL,
                `routing_number` varchar(50) DEFAULT NULL,
                `swift_code` varchar(50) DEFAULT NULL,
                `opening_balance` decimal(15,2) DEFAULT 0.00,
                `current_balance` decimal(15,2) DEFAULT 0.00,
                `currency` varchar(10) DEFAULT 'USD',
                `status` enum('active','inactive','closed') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `account_id` (`account_id`),
                CONSTRAINT `{$prefix}cash_accounts_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'bank_reconciliations' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}bank_reconciliations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `cash_account_id` int(11) NOT NULL,
                `reconciliation_date` date NOT NULL,
                `opening_balance` decimal(15,2) NOT NULL,
                `closing_balance` decimal(15,2) NOT NULL,
                `bank_statement_balance` decimal(15,2) NOT NULL,
                `adjustments` decimal(15,2) DEFAULT 0.00,
                `status` enum('draft','completed') NOT NULL DEFAULT 'draft',
                `notes` text DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `cash_account_id` (`cash_account_id`),
                KEY `reconciliation_date` (`reconciliation_date`),
                KEY `created_by` (`created_by`),
                CONSTRAINT `{$prefix}bank_reconciliations_ibfk_1` FOREIGN KEY (`cash_account_id`) REFERENCES `{$prefix}cash_accounts` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}bank_reconciliations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'customers' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}customers` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_code` varchar(50) NOT NULL,
                `company_name` varchar(255) NOT NULL,
                `contact_name` varchar(255) DEFAULT NULL,
                `email` varchar(100) DEFAULT NULL,
                `phone` varchar(50) DEFAULT NULL,
                `address` text DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `zip_code` varchar(20) DEFAULT NULL,
                `country` varchar(100) DEFAULT NULL,
                `tax_id` varchar(100) DEFAULT NULL,
                `credit_limit` decimal(15,2) DEFAULT 0.00,
                `payment_terms` varchar(100) DEFAULT NULL,
                `currency` varchar(10) DEFAULT 'USD',
                `account_id` int(11) DEFAULT NULL,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `customer_code` (`customer_code`),
                KEY `account_id` (`account_id`),
                KEY `status` (`status`),
                CONSTRAINT `{$prefix}customers_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'invoices' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}invoices` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `invoice_number` varchar(50) NOT NULL,
                `customer_id` int(11) NOT NULL,
                `invoice_date` date NOT NULL,
                `due_date` date NOT NULL,
                `reference` varchar(100) DEFAULT NULL,
                `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
                `tax_rate` decimal(5,2) DEFAULT 0.00,
                `tax_amount` decimal(15,2) DEFAULT 0.00,
                `discount_amount` decimal(15,2) DEFAULT 0.00,
                `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `paid_amount` decimal(15,2) DEFAULT 0.00,
                `balance_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `currency` varchar(10) DEFAULT 'USD',
                `terms` text DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `status` enum('draft','sent','partially_paid','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `invoice_number` (`invoice_number`),
                KEY `customer_id` (`customer_id`),
                KEY `invoice_date` (`invoice_date`),
                KEY `due_date` (`due_date`),
                KEY `status` (`status`),
                KEY `created_by` (`created_by`),
                CONSTRAINT `{$prefix}invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `{$prefix}customers` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}invoices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'invoice_items' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}invoice_items` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `invoice_id` int(11) NOT NULL,
                `item_description` varchar(255) NOT NULL,
                `quantity` decimal(10,2) DEFAULT 1.00,
                `unit_price` decimal(15,2) NOT NULL,
                `tax_rate` decimal(5,2) DEFAULT 0.00,
                `line_total` decimal(15,2) NOT NULL,
                `account_id` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `invoice_id` (`invoice_id`),
                KEY `account_id` (`account_id`),
                CONSTRAINT `{$prefix}invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `{$prefix}invoices` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}invoice_items_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'vendors' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}vendors` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `vendor_code` varchar(50) NOT NULL,
                `company_name` varchar(255) NOT NULL,
                `contact_name` varchar(255) DEFAULT NULL,
                `email` varchar(100) DEFAULT NULL,
                `phone` varchar(50) DEFAULT NULL,
                `address` text DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `zip_code` varchar(20) DEFAULT NULL,
                `country` varchar(100) DEFAULT NULL,
                `tax_id` varchar(100) DEFAULT NULL,
                `credit_limit` decimal(15,2) DEFAULT 0.00,
                `payment_terms` varchar(100) DEFAULT NULL,
                `currency` varchar(10) DEFAULT 'USD',
                `account_id` int(11) DEFAULT NULL,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `vendor_code` (`vendor_code`),
                KEY `account_id` (`account_id`),
                KEY `status` (`status`),
                CONSTRAINT `{$prefix}vendors_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'payments' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}payments` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `payment_number` varchar(50) NOT NULL,
                `payment_date` date NOT NULL,
                `payment_type` enum('receipt','payment') NOT NULL,
                `reference_type` enum('invoice','bill','manual') DEFAULT 'manual',
                `reference_id` int(11) DEFAULT NULL,
                `customer_id` int(11) DEFAULT NULL,
                `vendor_id` int(11) DEFAULT NULL,
                `account_id` int(11) NOT NULL,
                `amount` decimal(15,2) NOT NULL,
                `currency` varchar(10) DEFAULT 'USD',
                `payment_method` enum('cash','check','bank_transfer','credit_card','other') NOT NULL,
                `check_number` varchar(50) DEFAULT NULL,
                `reference` varchar(100) DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `status` enum('draft','posted','reversed','cancelled') NOT NULL DEFAULT 'posted',
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `payment_number` (`payment_number`),
                KEY `payment_date` (`payment_date`),
                KEY `payment_type` (`payment_type`),
                KEY `account_id` (`account_id`),
                KEY `customer_id` (`customer_id`),
                KEY `vendor_id` (`vendor_id`),
                KEY `status` (`status`),
                KEY `created_by` (`created_by`),
                CONSTRAINT `{$prefix}payments_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}payments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `{$prefix}customers` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}payments_ibfk_4` FOREIGN KEY (`vendor_id`) REFERENCES `{$prefix}vendors` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}payments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'bills' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}bills` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `bill_number` varchar(50) NOT NULL,
                `vendor_id` int(11) NOT NULL,
                `bill_date` date NOT NULL,
                `due_date` date NOT NULL,
                `reference` varchar(100) DEFAULT NULL,
                `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
                `tax_rate` decimal(5,2) DEFAULT 0.00,
                `tax_amount` decimal(15,2) DEFAULT 0.00,
                `discount_amount` decimal(15,2) DEFAULT 0.00,
                `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `paid_amount` decimal(15,2) DEFAULT 0.00,
                `balance_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
                `currency` varchar(10) DEFAULT 'USD',
                `terms` text DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `status` enum('draft','received','partially_paid','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `bill_number` (`bill_number`),
                KEY `vendor_id` (`vendor_id`),
                KEY `bill_date` (`bill_date`),
                KEY `due_date` (`due_date`),
                KEY `status` (`status`),
                KEY `created_by` (`created_by`),
                CONSTRAINT `{$prefix}bills_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `{$prefix}vendors` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}bills_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'bill_items' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}bill_items` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `bill_id` int(11) NOT NULL,
                `item_description` varchar(255) NOT NULL,
                `quantity` decimal(10,2) DEFAULT 1.00,
                `unit_price` decimal(15,2) NOT NULL,
                `tax_rate` decimal(5,2) DEFAULT 0.00,
                `line_total` decimal(15,2) NOT NULL,
                `account_id` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `bill_id` (`bill_id`),
                KEY `account_id` (`account_id`),
                CONSTRAINT `{$prefix}bill_items_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `{$prefix}bills` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}bill_items_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'employees' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}employees` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `employee_code` varchar(50) NOT NULL,
                `user_id` int(11) DEFAULT NULL,
                `first_name` varchar(100) NOT NULL,
                `last_name` varchar(100) NOT NULL,
                `email` varchar(100) DEFAULT NULL,
                `phone` varchar(50) DEFAULT NULL,
                `address` text DEFAULT NULL,
                `date_of_birth` date DEFAULT NULL,
                `date_of_hire` date NOT NULL,
                `employment_type` enum('full_time','part_time','contract','intern') NOT NULL DEFAULT 'full_time',
                `department` varchar(100) DEFAULT NULL,
                `position` varchar(100) DEFAULT NULL,
                `salary_type` enum('monthly','hourly','daily','yearly') NOT NULL DEFAULT 'monthly',
                `basic_salary` decimal(15,2) DEFAULT 0.00,
                `account_id` int(11) DEFAULT NULL,
                `status` enum('active','inactive','terminated') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `employee_code` (`employee_code`),
                KEY `user_id` (`user_id`),
                KEY `account_id` (`account_id`),
                KEY `status` (`status`),
                CONSTRAINT `{$prefix}employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{$prefix}employees_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'payroll' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}payroll` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `payroll_number` varchar(50) NOT NULL,
                `employee_id` int(11) NOT NULL,
                `pay_period_start` date NOT NULL,
                `pay_period_end` date NOT NULL,
                `payment_date` date NOT NULL,
                `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
                `allowances` decimal(15,2) DEFAULT 0.00,
                `deductions` decimal(15,2) DEFAULT 0.00,
                `tax_amount` decimal(15,2) DEFAULT 0.00,
                `net_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
                `currency` varchar(10) DEFAULT 'USD',
                `payment_method` enum('cash','check','bank_transfer') NOT NULL DEFAULT 'bank_transfer',
                `bank_account` varchar(100) DEFAULT NULL,
                `status` enum('draft','processed','paid','cancelled') NOT NULL DEFAULT 'draft',
                `payslip_generated` tinyint(1) DEFAULT 0,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `payroll_number` (`payroll_number`),
                KEY `employee_id` (`employee_id`),
                KEY `payment_date` (`payment_date`),
                KEY `status` (`status`),
                KEY `created_by` (`created_by`),
                CONSTRAINT `{$prefix}payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `{$prefix}employees` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `{$prefix}payroll_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'payroll_items' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}payroll_items` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `payroll_id` int(11) NOT NULL,
                `item_type` enum('allowance','deduction') NOT NULL,
                `item_name` varchar(255) NOT NULL,
                `amount` decimal(15,2) NOT NULL,
                `account_id` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `payroll_id` (`payroll_id`),
                KEY `account_id` (`account_id`),
                CONSTRAINT `{$prefix}payroll_items_ibfk_1` FOREIGN KEY (`payroll_id`) REFERENCES `{$prefix}payroll` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}payroll_items_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `{$prefix}accounts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'financial_years' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}financial_years` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `year_name` varchar(50) NOT NULL,
                `start_date` date NOT NULL,
                `end_date` date NOT NULL,
                `status` enum('open','closed') NOT NULL DEFAULT 'open',
                `closed_at` datetime DEFAULT NULL,
                `closed_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `start_date` (`start_date`),
                KEY `end_date` (`end_date`),
                KEY `status` (`status`),
                KEY `closed_by` (`closed_by`),
                CONSTRAINT `{$prefix}financial_years_ibfk_1` FOREIGN KEY (`closed_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
    ];
    
    // Disable foreign key checks temporarily to avoid constraint issues during creation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("SET SESSION wait_timeout = 600");
    $pdo->exec("SET SESSION interactive_timeout = 600");
    
    // Process tables in batches to avoid overwhelming MySQL
    $batchSize = 10;
    $tables = array_keys($migrations);
    $totalTables = count($tables);
    
    for ($i = 0; $i < $totalTables; $i += $batchSize) {
        $batch = array_slice($tables, $i, $batchSize);
        
        foreach ($batch as $table) {
            try {
                $pdo->exec($migrations[$table]);
            } catch (PDOException $e) {
                // Re-enable foreign key checks before throwing error
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                throw new Exception("Failed to create table {$table}: " . $e->getMessage());
            }
        }
        
        // Small delay between batches to prevent MySQL overload
        if ($i + $batchSize < $totalTables) {
            usleep(100000); // 0.1 second delay
        }
    }
    
    // Re-enable foreign key checks after all tables are created
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Insert default permissions
    insertDefaultPermissions($pdo, $prefix);
    
    // Run enhanced migrations for production features
    try {
        if (file_exists(__DIR__ . '/migrations_enhanced.php')) {
            require __DIR__ . '/migrations_enhanced.php';
            runEnhancedMigrations($pdo, $prefix);
        }
    } catch (Exception $e) {
        // Log but don't fail installation if enhanced migrations fail
        error_log("Enhanced migrations warning: " . $e->getMessage());
    }
}

function insertDefaultPermissions($pdo, $prefix) {
    $modules = ['users', 'companies', 'settings', 'reports', 'modules'];
    $actions = ['create', 'read', 'update', 'delete'];
    
    // Use batch insert instead of individual inserts for better performance
    $values = [];
    $params = [];
    
    foreach ($modules as $module) {
        foreach ($actions as $action) {
            $values[] = "(?, ?, ?, NOW())";
            $description = ucfirst($action) . ' ' . ucfirst($module);
            $params[] = $module;
            $params[] = $action;
            $params[] = $description;
        }
    }
    
    if (!empty($values)) {
        try {
            $sql = "INSERT IGNORE INTO `{$prefix}permissions` (module, permission, description, created_at) VALUES " . implode(', ', $values);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            // Fallback to individual inserts if batch fails
            foreach ($modules as $module) {
                foreach ($actions as $action) {
                    try {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}permissions` (module, permission, description, created_at) VALUES (?, ?, ?, NOW())");
                        $description = ucfirst($action) . ' ' . ucfirst($module);
                        $stmt->execute([$module, $action, $description]);
                    } catch (PDOException $e) {
                        // Ignore duplicate errors
                    }
                }
            }
        }
    }
}

