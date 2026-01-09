-- ============================================================================
-- MISSING SYSTEM TABLES FIX (PART 2)
-- ============================================================================
-- This migration creates the remaining missing tables identified by the system audit
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. INVENTORY: STOCK ADJUSTMENTS
CREATE TABLE IF NOT EXISTS `erp_stock_adjustments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `adjustment_number` VARCHAR(50) NOT NULL UNIQUE,
    `item_id` INT(11) NOT NULL,
    `location_id` INT(11) NOT NULL,
    `adjustment_type` ENUM('addition', 'subtraction', 'reset') NOT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `adjusted_by` INT(11) DEFAULT NULL,
    `adjustment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('draft', 'posted', 'cancelled') DEFAULT 'draft',
    PRIMARY KEY (`id`),
    KEY `idx_item_id` (`item_id`),
    KEY `idx_location_id` (`location_id`),
    KEY `idx_adjustment_date` (`adjustment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. INVENTORY: PHYSICAL INVENTORY (STOCK TAKES)
CREATE TABLE IF NOT EXISTS `erp_stock_takes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `stock_take_number` VARCHAR(50) NOT NULL UNIQUE,
    `location_id` INT(11) NOT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `status` ENUM('in_progress', 'completed', 'cancelled') DEFAULT 'in_progress',
    `conducted_by` INT(11) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_location_id` (`location_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. LEASES & TENANTS: TENANT RECORDS
CREATE TABLE IF NOT EXISTS `erp_tenants` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tenant_name` VARCHAR(255) NOT NULL,
    `tenant_type` ENUM('individual', 'corporate') DEFAULT 'individual',
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `identification_type` VARCHAR(50) DEFAULT NULL,
    `identification_number` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tenant_name` (`tenant_name`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. LEASES & TENANTS: RENT INVOICES
CREATE TABLE IF NOT EXISTS `erp_rent_invoices` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `lease_id` INT(11) NOT NULL,
    `tenant_id` INT(11) NOT NULL,
    `property_id` INT(11) NOT NULL,
    `space_id` INT(11) DEFAULT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `tax_total` DECIMAL(15,2) DEFAULT 0,
    `total_amount` DECIMAL(15,2) DEFAULT 0,
    `balance_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'sent', 'partial', 'paid', 'cancelled', 'overdue') DEFAULT 'draft',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_lease_id` (`lease_id`),
    KEY `idx_tenant_id` (`tenant_id`),
    KEY `idx_status` (`status`),
    KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PAYROLL: PAYROLL PROCESSING RUNS
CREATE TABLE IF NOT EXISTS `erp_payroll_runs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `period_name` VARCHAR(100) NOT NULL COMMENT 'e.g. June 2024',
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `run_date` DATE NOT NULL,
    `total_gross` DECIMAL(15,2) DEFAULT 0,
    `total_deductions` DECIMAL(15,2) DEFAULT 0,
    `total_net` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'processed', 'approved', 'paid', 'cancelled') DEFAULT 'draft',
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. PAYROLL: EMPLOYEE PAYSLIPS
CREATE TABLE IF NOT EXISTS `erp_payslips` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `payroll_run_id` INT(11) NOT NULL,
    `employee_id` INT(11) NOT NULL,
    `basic_salary` DECIMAL(15,2) DEFAULT 0,
    `allowances` DECIMAL(15,2) DEFAULT 0,
    `bonus` DECIMAL(15,2) DEFAULT 0,
    `gross_pay` DECIMAL(15,2) DEFAULT 0,
    `tax_deduction` DECIMAL(15,2) DEFAULT 0,
    `pension_deduction` DECIMAL(15,2) DEFAULT 0,
    `other_deductions` DECIMAL(15,2) DEFAULT 0,
    `total_deductions` DECIMAL(15,2) DEFAULT 0,
    `net_pay` DECIMAL(15,2) DEFAULT 0,
    `payment_status` ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    `payment_date` DATE DEFAULT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payroll_run_id` (`payroll_run_id`),
    KEY `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. UTILITIES: UTILITY PAYMENTS (IF NOT EXISTS FROM PREVIOUS)
CREATE TABLE IF NOT EXISTS `erp_utility_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `bill_id` INT(11) NOT NULL,
    `payment_number` VARCHAR(50) NOT NULL UNIQUE,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. COMPATIBILITY: LOCATIONS VIEW
-- Creating a view for erp_locations to point to erp_properties for backward compatibility
DROP VIEW IF EXISTS `erp_locations`;
CREATE VIEW `erp_locations` AS SELECT *, id AS location_id, property_name AS location_name FROM `erp_properties`;

SET FOREIGN_KEY_CHECKS = 1;
