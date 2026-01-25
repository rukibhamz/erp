-- ============================================================================
-- FIX MISSING PRIMARY TABLES
-- ============================================================================
-- Creates tables that were missing from previous migrations
-- Updated to include erp_companies and correct column names
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. ACCOUNTS TABLE (Chart of Accounts)
CREATE TABLE IF NOT EXISTS `erp_accounts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `account_code` VARCHAR(50) NOT NULL,
    `account_name` VARCHAR(255) NOT NULL,
    `account_type` ENUM('asset','liability','equity','income','expense') NOT NULL,
    `account_category` VARCHAR(100) DEFAULT NULL,
    `parent_account_id` INT(11) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `is_system_account` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_account_code` (`account_code`),
    KEY `idx_account_type` (`account_type`),
    KEY `idx_parent` (`parent_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. FACILITIES TABLE (Resources)
CREATE TABLE IF NOT EXISTS `erp_facilities` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `facility_code` VARCHAR(50) NOT NULL,
    `facility_name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `capacity` INT(11) DEFAULT 0,
    `hourly_rate` DECIMAL(15,2) DEFAULT 0,
    `daily_rate` DECIMAL(15,2) DEFAULT 0,
    `half_day_rate` DECIMAL(15,2) DEFAULT 0,
    `weekly_rate` DECIMAL(15,2) DEFAULT 0,
    `member_rate` DECIMAL(15,2) DEFAULT 0,
    `resource_type` VARCHAR(50) DEFAULT 'facility',
    `category` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
    `is_bookable` TINYINT(1) DEFAULT 1,
    `pricing_rules` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_facility_code` (`facility_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BOOKINGS TABLE
CREATE TABLE IF NOT EXISTS `erp_bookings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_number` VARCHAR(50) NOT NULL, -- Renamed from booking_reference to match model
    `facility_id` INT(11) NULL,
    `customer_id` INT(11) NULL,
    `customer_name` VARCHAR(255) NULL,
    `customer_email` VARCHAR(255) NULL,
    `customer_phone` VARCHAR(50) NULL,
    `customer_address` VARCHAR(500) NULL,
    `booking_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `duration_hours` DECIMAL(10,2) DEFAULT 0,
    `number_of_guests` INT(11) DEFAULT 0,
    `booking_type` ENUM('hourly','half_day','full_day','daily','multi_day','weekly') DEFAULT 'hourly',
    `status` ENUM('pending','confirmed','cancelled','completed','no_show','refunded') DEFAULT 'pending',
    `payment_status` ENUM('unpaid','partial','paid','refunded', 'overpaid') DEFAULT 'unpaid',
    `payment_plan` ENUM('full','deposit','installment','pay_later') DEFAULT 'full',
    `base_amount` DECIMAL(15,2) DEFAULT 0,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `discount_amount` DECIMAL(15,2) DEFAULT 0,
    `security_deposit` DECIMAL(15,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `total_amount` DECIMAL(15,2) DEFAULT 0,
    `paid_amount` DECIMAL(15,2) DEFAULT 0,
    `balance_amount` DECIMAL(15,2) DEFAULT 0,
    `currency` VARCHAR(10) DEFAULT 'NGN',
    `promo_code` VARCHAR(50) NULL,
    `booking_notes` TEXT NULL,
    `special_requests` TEXT NULL,
    `booking_source` ENUM('online','dashboard','phone','walkin') DEFAULT 'online',
    `invoice_id` INT(11) NULL,
    `is_recurring` TINYINT(1) DEFAULT 0,
    `recurring_pattern` ENUM('daily','weekly','monthly') NULL,
    `recurring_end_date` DATE NULL,
    `created_by` INT(11) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    `confirmed_at` DATETIME NULL,
    `cancelled_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_booking_num` (`booking_number`),
    KEY `idx_facility_id` (`facility_id`),
    KEY `idx_customer` (`customer_id`),
    KEY `idx_status` (`status`),
    KEY `idx_booking_date` (`booking_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. INVOICES TABLE
CREATE TABLE IF NOT EXISTS `erp_invoices` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL,
    `customer_id` INT(11) NOT NULL, -- references companies or customers
    `invoice_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `tax_total` DECIMAL(15,2) DEFAULT 0,
    `discount_total` DECIMAL(15,2) DEFAULT 0,
    `total_amount` DECIMAL(15,2) DEFAULT 0,
    `paid_amount` DECIMAL(15,2) DEFAULT 0,
    `balance_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft','sent','partially_paid','paid','overdue','cancelled') DEFAULT 'draft',
    `notes` TEXT DEFAULT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT(11) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_invoice_number` (`invoice_number`),
    KEY `idx_customer_id` (`customer_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. COMPANIES TABLE (Used by Entity_model for customers/entities)
CREATE TABLE IF NOT EXISTS `erp_companies` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `entity_code` VARCHAR(50) DEFAULT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `contact_name` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `entity_type` VARCHAR(50) DEFAULT 'customer',
    `company_type` VARCHAR(50) DEFAULT NULL,
    `customer_type_id` INT(11) DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. CUSTOMERS TABLE (Legacy/Concurrent usage)
CREATE TABLE IF NOT EXISTS `erp_customers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_code` VARCHAR(50) NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `customer_type_id` INT(11) DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_customer_code` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. CASH ACCOUNTS TABLE
CREATE TABLE IF NOT EXISTS `erp_cash_accounts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `account_name` VARCHAR(255) NOT NULL,
    `account_number` VARCHAR(50) DEFAULT NULL,
    `bank_name` VARCHAR(255) DEFAULT NULL,
    `currency` VARCHAR(10) DEFAULT 'NGN',
    `current_balance` DECIMAL(15,2) DEFAULT 0,
    `gl_account_id` INT(11) DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
