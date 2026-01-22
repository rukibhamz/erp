-- ============================================================================
-- BOOKING PAYMENTS & GATEWAY INTEGRATION TABLES
-- ============================================================================
-- This file ensures proper tables exist for the booking payments system.
-- It works for both new installations and updates to existing systems.

-- 1. Booking Payments Table
-- Records individual payments linked to specific bookings
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

-- 2. Payment Gateway Transactions Table
-- Records raw transaction data from payment gateways (Paystack, Flutterwave, etc.)
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

-- 3. Update Transactions Table for Accounting
-- Ensure debit/credit columns exist for proper double-entry accounting
SET @dbname = DATABASE();
SET @tablename = "erp_transactions";
SET @columnname = "debit";
set @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " DECIMAL(15,2) DEFAULT 0 AFTER account_id")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "credit";
set @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " DECIMAL(15,2) DEFAULT 0 AFTER debit")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = "status";
set @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(20) DEFAULT 'posted' AFTER transaction_date")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
