-- ============================================================================
-- MIGRATION: 017_default_pos_terminal
-- ============================================================================
-- Purpose: Create default POS terminal and configuration on install
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================

-- Ensure POS terminals table exists
CREATE TABLE IF NOT EXISTS `erp_pos_terminals` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `terminal_code` VARCHAR(50) NOT NULL UNIQUE,
    `terminal_name` VARCHAR(100) NOT NULL,
    `location_id` INT(11) DEFAULT NULL,
    `terminal_type` ENUM('physical','virtual','mobile') DEFAULT 'virtual',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `mac_address` VARCHAR(17) DEFAULT NULL,
    `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
    `last_activity` DATETIME DEFAULT NULL,
    `settings` JSON DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_terminal_code` (`terminal_code`),
    KEY `idx_status` (`status`),
    KEY `idx_location_id` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure POS shifts table exists
CREATE TABLE IF NOT EXISTS `erp_pos_shifts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `shift_number` VARCHAR(50) NOT NULL UNIQUE,
    `terminal_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME DEFAULT NULL,
    `opening_cash` DECIMAL(15,2) DEFAULT 0.00,
    `closing_cash` DECIMAL(15,2) DEFAULT NULL,
    `expected_cash` DECIMAL(15,2) DEFAULT NULL,
    `cash_difference` DECIMAL(15,2) DEFAULT NULL,
    `total_sales` DECIMAL(15,2) DEFAULT 0.00,
    `total_refunds` DECIMAL(15,2) DEFAULT 0.00,
    `total_transactions` INT(11) DEFAULT 0,
    `status` ENUM('open','closed','pending_close') DEFAULT 'open',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_shift_number` (`shift_number`),
    KEY `idx_terminal_id` (`terminal_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure POS payment methods table exists
CREATE TABLE IF NOT EXISTS `erp_pos_payment_methods` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('cash','card','transfer','mobile','other') NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `requires_reference` TINYINT(1) DEFAULT 0,
    `account_id` INT(11) DEFAULT NULL COMMENT 'Linked GL account',
    `sort_order` INT(11) DEFAULT 0,
    `icon` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_code` (`code`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERT DEFAULT DATA
-- ============================================================================

-- Create default POS terminal (virtual/main terminal)
INSERT INTO `erp_pos_terminals` 
(`terminal_code`, `terminal_name`, `terminal_type`, `status`, `settings`, `created_at`)
VALUES 
('TERM-001', 'Main POS Terminal', 'virtual', 'active', 
 '{"receipt_header": "Thank you for your purchase!", "receipt_footer": "Please come again", "auto_print": false, "default_customer": null}',
 NOW())
ON DUPLICATE KEY UPDATE terminal_name = 'Main POS Terminal', status = 'active';

-- Create default payment methods
INSERT INTO `erp_pos_payment_methods` (`code`, `name`, `type`, `is_active`, `requires_reference`, `sort_order`, `icon`, `created_at`)
VALUES 
('CASH', 'Cash', 'cash', 1, 0, 1, 'bi-cash', NOW()),
('CARD', 'Card (POS)', 'card', 1, 1, 2, 'bi-credit-card', NOW()),
('TRANSFER', 'Bank Transfer', 'transfer', 1, 1, 3, 'bi-bank', NOW()),
('USSD', 'USSD Transfer', 'mobile', 1, 1, 4, 'bi-phone', NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = 1;

-- ============================================================================
-- VERIFICATION
-- ============================================================================
SELECT 'POS Terminal' as migration, 
       (SELECT COUNT(*) FROM `erp_pos_terminals` WHERE status = 'active') as active_terminals,
       (SELECT COUNT(*) FROM `erp_pos_payment_methods` WHERE is_active = 1) as payment_methods;
