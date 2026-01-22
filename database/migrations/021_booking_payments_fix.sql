-- Create booking_payments table if not exists
-- Run this migration to ensure the booking payments table exists

CREATE TABLE IF NOT EXISTS `erp_booking_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `payment_number` VARCHAR(50) NULL,
    `payment_date` DATE NOT NULL,
    `payment_type` VARCHAR(50) DEFAULT 'full',
    `payment_method` VARCHAR(50) DEFAULT 'cash',
    `amount` DECIMAL(15,2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'NGN',
    `status` VARCHAR(20) DEFAULT 'pending',
    `gateway_transaction_id` VARCHAR(255) NULL,
    `reference` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `created_by` INT(11) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_booking_id` (`booking_id`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_status` (`status`),
    KEY `idx_gateway_transaction_id` (`gateway_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create payment_transactions table if not exists
CREATE TABLE IF NOT EXISTS `erp_payment_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_ref` VARCHAR(100) NOT NULL UNIQUE,
    `gateway_code` VARCHAR(50) NOT NULL,
    `gateway_transaction_id` VARCHAR(255) NULL,
    `payment_type` VARCHAR(50) DEFAULT 'booking_payment',
    `reference_id` INT(11) NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'NGN',
    `customer_email` VARCHAR(255) NULL,
    `customer_name` VARCHAR(255) NULL,
    `customer_phone` VARCHAR(50) NULL,
    `status` ENUM('pending','success','failed','cancelled') DEFAULT 'pending',
    `gateway_response` JSON NULL,
    `callback_data` JSON NULL,
    `error_message` TEXT NULL,
    `processed_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_transaction_ref` (`transaction_ref`),
    KEY `idx_gateway_code` (`gateway_code`),
    KEY `idx_status` (`status`),
    KEY `idx_reference` (`payment_type`, `reference_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure transactions table has proper structure for accounting
-- This adds debit/credit columns if they don't exist
ALTER TABLE `erp_transactions` 
    ADD COLUMN IF NOT EXISTS `debit` DECIMAL(15,2) DEFAULT 0 AFTER `account_id`,
    ADD COLUMN IF NOT EXISTS `credit` DECIMAL(15,2) DEFAULT 0 AFTER `debit`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) DEFAULT 'posted' AFTER `transaction_date`;

-- Show created tables for verification
SHOW TABLES LIKE 'erp_booking_payments';
SHOW TABLES LIKE 'erp_payment_transactions';
