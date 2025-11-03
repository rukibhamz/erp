<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function runPosMigrations($pdo, $prefix = 'erp_') {
    try {
        // POS Terminals/Stations
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}pos_terminals` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `terminal_code` VARCHAR(50) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `location` VARCHAR(255) DEFAULT NULL,
            `cash_account_id` INT(11) DEFAULT NULL,
            `default_customer_id` INT(11) DEFAULT NULL COMMENT 'Walk-in customer',
            `printer_settings` JSON DEFAULT NULL,
            `receipt_template` VARCHAR(50) DEFAULT 'standard',
            `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `terminal_code` (`terminal_code`),
            KEY `cash_account_id` (`cash_account_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // POS Sales Transactions
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}pos_sales` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sale_number` VARCHAR(50) NOT NULL,
            `terminal_id` INT(11) NOT NULL,
            `cashier_id` INT(11) NOT NULL,
            `customer_id` INT(11) DEFAULT NULL,
            `sale_date` DATETIME NOT NULL,
            `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `discount_amount` DECIMAL(15,2) DEFAULT 0,
            `discount_type` ENUM('percentage', 'fixed') DEFAULT 'fixed',
            `tax_amount` DECIMAL(15,2) DEFAULT 0,
            `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `payment_method` ENUM('cash', 'card', 'transfer', 'mobile_money', 'credit', 'mixed') DEFAULT 'cash',
            `amount_paid` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `change_amount` DECIMAL(15,2) DEFAULT 0,
            `status` ENUM('completed', 'pending', 'cancelled', 'refunded') DEFAULT 'completed',
            `notes` TEXT DEFAULT NULL,
            `invoice_id` INT(11) DEFAULT NULL COMMENT 'Link to accounting invoice',
            `transaction_id` INT(11) DEFAULT NULL COMMENT 'Link to accounting transaction',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `sale_number` (`sale_number`),
            KEY `terminal_id` (`terminal_id`),
            KEY `cashier_id` (`cashier_id`),
            KEY `customer_id` (`customer_id`),
            KEY `sale_date` (`sale_date`),
            KEY `status` (`status`),
            KEY `invoice_id` (`invoice_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // POS Sale Items
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}pos_sale_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sale_id` INT(11) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `item_name` VARCHAR(255) NOT NULL,
            `item_code` VARCHAR(100) DEFAULT NULL,
            `quantity` DECIMAL(10,3) NOT NULL DEFAULT 1,
            `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `discount_amount` DECIMAL(15,2) DEFAULT 0,
            `tax_rate` DECIMAL(5,2) DEFAULT 0,
            `tax_amount` DECIMAL(15,2) DEFAULT 0,
            `line_total` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `sale_id` (`sale_id`),
            KEY `item_id` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // POS Payments (for mixed payment methods)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}pos_payments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sale_id` INT(11) NOT NULL,
            `payment_method` ENUM('cash', 'card', 'transfer', 'mobile_money', 'credit') NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `reference` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `sale_id` (`sale_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // POS Refunds
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}pos_refunds` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `refund_number` VARCHAR(50) NOT NULL,
            `sale_id` INT(11) NOT NULL,
            `terminal_id` INT(11) NOT NULL,
            `cashier_id` INT(11) NOT NULL,
            `refund_date` DATETIME NOT NULL,
            `refund_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `reason` TEXT DEFAULT NULL,
            `status` ENUM('completed', 'pending', 'cancelled') DEFAULT 'completed',
            `credit_note_id` INT(11) DEFAULT NULL,
            `transaction_id` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `refund_number` (`refund_number`),
            KEY `sale_id` (`sale_id`),
            KEY `terminal_id` (`terminal_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // POS Refund Items
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}pos_refund_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `refund_id` INT(11) NOT NULL,
            `sale_item_id` INT(11) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `quantity` DECIMAL(10,3) NOT NULL DEFAULT 1,
            `refund_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `refund_id` (`refund_id`),
            KEY `sale_item_id` (`sale_item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // POS Cash Drawer Sessions
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}pos_sessions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `terminal_id` INT(11) NOT NULL,
            `cashier_id` INT(11) NOT NULL,
            `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `opening_time` DATETIME NOT NULL,
            `closing_balance` DECIMAL(15,2) DEFAULT NULL,
            `closing_time` DATETIME DEFAULT NULL,
            `expected_cash` DECIMAL(15,2) DEFAULT NULL,
            `actual_cash` DECIMAL(15,2) DEFAULT NULL,
            `cash_difference` DECIMAL(15,2) DEFAULT NULL,
            `total_sales` DECIMAL(15,2) DEFAULT 0,
            `total_cash` DECIMAL(15,2) DEFAULT 0,
            `total_card` DECIMAL(15,2) DEFAULT 0,
            `total_refunds` DECIMAL(15,2) DEFAULT 0,
            `status` ENUM('open', 'closed') DEFAULT 'open',
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `terminal_id` (`terminal_id`),
            KEY `cashier_id` (`cashier_id`),
            KEY `status` (`status`),
            KEY `opening_time` (`opening_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        return true;
    } catch (PDOException $e) {
        error_log("POS migrations error: " . $e->getMessage());
        throw $e;
    }
}



