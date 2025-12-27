-- ============================================================================
-- MIGRATION: 016_nigeria_paye_tax_brackets
-- ============================================================================
-- Purpose: Add Nigeria PAYE progressive tax brackets for payroll calculations
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================

-- Create tax brackets table if not exists
CREATE TABLE IF NOT EXISTS `erp_tax_brackets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tax_type_code` VARCHAR(50) NOT NULL COMMENT 'Reference to tax_types.code',
    `bracket_name` VARCHAR(100) NOT NULL,
    `min_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `max_amount` DECIMAL(15,2) DEFAULT NULL COMMENT 'NULL for unlimited',
    `rate` DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT 'Percentage rate',
    `fixed_amount` DECIMAL(15,2) DEFAULT 0 COMMENT 'Fixed amount if any',
    `cumulative_tax` DECIMAL(15,2) DEFAULT 0 COMMENT 'Cumulative tax up to this bracket',
    `sort_order` INT(11) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tax_type` (`tax_type_code`),
    KEY `idx_sort_order` (`sort_order`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- NIGERIA PAYE TAX BRACKETS (2024)
-- Based on Personal Income Tax Act (PITA)
-- Annual taxable income brackets
-- ============================================================================

-- Clear existing PAYE brackets to ensure clean data
DELETE FROM `erp_tax_brackets` WHERE `tax_type_code` = 'PAYE';

-- Insert Nigeria PAYE progressive tax brackets
-- First NGN 300,000: 7%
INSERT INTO `erp_tax_brackets` 
(`tax_type_code`, `bracket_name`, `min_amount`, `max_amount`, `rate`, `cumulative_tax`, `sort_order`, `is_active`, `created_at`)
VALUES 
('PAYE', 'First ₦300,000', 0.00, 300000.00, 7.00, 0.00, 1, 1, NOW())
ON DUPLICATE KEY UPDATE rate = 7.00, cumulative_tax = 0.00;

-- Next NGN 300,000: 11%
INSERT INTO `erp_tax_brackets` 
(`tax_type_code`, `bracket_name`, `min_amount`, `max_amount`, `rate`, `cumulative_tax`, `sort_order`, `is_active`, `created_at`)
VALUES 
('PAYE', 'Next ₦300,000', 300000.01, 600000.00, 11.00, 21000.00, 2, 1, NOW())
ON DUPLICATE KEY UPDATE rate = 11.00, cumulative_tax = 21000.00;

-- Next NGN 500,000: 15%
INSERT INTO `erp_tax_brackets` 
(`tax_type_code`, `bracket_name`, `min_amount`, `max_amount`, `rate`, `cumulative_tax`, `sort_order`, `is_active`, `created_at`)
VALUES 
('PAYE', 'Next ₦500,000', 600000.01, 1100000.00, 15.00, 54000.00, 3, 1, NOW())
ON DUPLICATE KEY UPDATE rate = 15.00, cumulative_tax = 54000.00;

-- Next NGN 500,000: 19%
INSERT INTO `erp_tax_brackets` 
(`tax_type_code`, `bracket_name`, `min_amount`, `max_amount`, `rate`, `cumulative_tax`, `sort_order`, `is_active`, `created_at`)
VALUES 
('PAYE', 'Next ₦500,000', 1100000.01, 1600000.00, 19.00, 129000.00, 4, 1, NOW())
ON DUPLICATE KEY UPDATE rate = 19.00, cumulative_tax = 129000.00;

-- Next NGN 1,600,000: 21%
INSERT INTO `erp_tax_brackets` 
(`tax_type_code`, `bracket_name`, `min_amount`, `max_amount`, `rate`, `cumulative_tax`, `sort_order`, `is_active`, `created_at`)
VALUES 
('PAYE', 'Next ₦1,600,000', 1600000.01, 3200000.00, 21.00, 224000.00, 5, 1, NOW())
ON DUPLICATE KEY UPDATE rate = 21.00, cumulative_tax = 224000.00;

-- Above NGN 3,200,000: 24%
INSERT INTO `erp_tax_brackets` 
(`tax_type_code`, `bracket_name`, `min_amount`, `max_amount`, `rate`, `cumulative_tax`, `sort_order`, `is_active`, `created_at`)
VALUES 
('PAYE', 'Above ₦3,200,000', 3200000.01, NULL, 24.00, 560000.00, 6, 1, NOW())
ON DUPLICATE KEY UPDATE rate = 24.00, cumulative_tax = 560000.00;

-- ============================================================================
-- NIGERIAN TAX RELIEF ALLOWANCES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `erp_tax_reliefs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `relief_type` ENUM('fixed', 'percentage', 'formula') DEFAULT 'fixed',
    `value` DECIMAL(15,2) DEFAULT 0,
    `percentage_of` VARCHAR(100) DEFAULT NULL COMMENT 'Column name if percentage type',
    `max_amount` DECIMAL(15,2) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Nigerian tax relief allowances
INSERT INTO `erp_tax_reliefs` (`code`, `name`, `description`, `relief_type`, `value`, `percentage_of`, `is_active`, `created_at`)
VALUES 
('CRA', 'Consolidated Relief Allowance', '₦200,000 or 1% of gross income, whichever is higher + 20% of gross income', 'formula', 200000.00, 'gross_income', 1, NOW()),
('PENSION', 'Pension Contribution', 'Employee pension contribution (8% of basic + housing + transport)', 'percentage', 8.00, 'pensionable_income', 1, NOW()),
('NHF', 'National Housing Fund', '2.5% of basic salary', 'percentage', 2.50, 'basic_salary', 1, NOW()),
('NHIS', 'National Health Insurance', 'Health insurance contributions', 'percentage', 5.00, 'basic_salary', 1, NOW()),
('LIFE_ASSURANCE', 'Life Assurance Premium', 'Life assurance premium relief', 'fixed', 0.00, NULL, 1, NOW()),
('GRATUITY', 'Gratuity', 'Approved gratuity fund contributions', 'fixed', 0.00, NULL, 1, NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

-- ============================================================================
-- VERIFICATION
-- ============================================================================
SELECT 'PAYE Tax Brackets' as migration, COUNT(*) as brackets_count
FROM `erp_tax_brackets` 
WHERE `tax_type_code` = 'PAYE' AND `is_active` = 1;
