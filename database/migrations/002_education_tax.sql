-- Migration: 002_education_tax.sql
-- Description: Add Education Tax features

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create erp_education_tax_config table
CREATE TABLE IF NOT EXISTS `erp_education_tax_config` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tax_year` INT(4) NOT NULL,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 2.50 COMMENT 'Rate in percentage',
    `threshold` DECIMAL(15,2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_tax_year` (`tax_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default config for 2024 and 2025
INSERT IGNORE INTO `erp_education_tax_config` (`tax_year`, `tax_rate`) VALUES (2024, 2.50), (2025, 2.50);

-- 2. Create erp_education_tax_payments table
CREATE TABLE IF NOT EXISTS `erp_education_tax_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tax_year` INT(4) NOT NULL,
    `amount_paid` DECIMAL(15,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `payment_reference` VARCHAR(100) DEFAULT NULL,
    `penalty_amount` DECIMAL(15,2) DEFAULT 0.00,
    `interest_amount` DECIMAL(15,2) DEFAULT 0.00,
    `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tax_year` (`tax_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create erp_education_tax_returns table
CREATE TABLE IF NOT EXISTS `erp_education_tax_returns` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tax_year` INT(4) NOT NULL,
    `assessable_profit` DECIMAL(15,2) NOT NULL,
    `tax_due` DECIMAL(15,2) NOT NULL,
    `filing_date` DATE NOT NULL,
    `status` ENUM('draft', 'filed', 'amended') DEFAULT 'filed',
    `submission_receipt` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_return_year` (`tax_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create view vw_education_tax_summary
CREATE OR REPLACE VIEW `vw_education_tax_summary` AS
SELECT 
    c.tax_year,
    IFNULL(r.assessable_profit, 0) as assessable_profit,
    c.tax_rate,
    IFNULL(r.tax_due, (IFNULL(r.assessable_profit, 0) * c.tax_rate / 100)) as tax_due,
    IFNULL(SUM(p.amount_paid), 0) as total_paid,
    (IFNULL(r.tax_due, (IFNULL(r.assessable_profit, 0) * c.tax_rate / 100)) - IFNULL(SUM(p.amount_paid), 0)) as balance_due,
    IFNULL(SUM(p.penalty_amount), 0) as total_penalties,
    IFNULL(SUM(p.interest_amount), 0) as total_interest
FROM `erp_education_tax_config` c
LEFT JOIN `erp_education_tax_returns` r ON c.tax_year = r.tax_year
LEFT JOIN `erp_education_tax_payments` p ON c.tax_year = p.tax_year AND p.status = 'completed'
GROUP BY c.tax_year;

-- 5. Add permissions for Education Tax
INSERT IGNORE INTO `erp_permissions` (`module`, `permission`, `description`, `created_at`) VALUES
('education_tax', 'read', 'View education tax filings and payments', NOW()),
('education_tax', 'create', 'Create education tax records', NOW()),
('education_tax', 'update', 'Update education tax records', NOW()),
('education_tax', 'delete', 'Delete education tax records', NOW());

-- Assign permissions to super_admin and admin
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code IN ('super_admin', 'admin')
AND p.module = 'education_tax'
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

SET FOREIGN_KEY_CHECKS = 1;
