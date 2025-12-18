-- Migration: 001_wholesale_pricing.sql
-- Description: Add Wholesale Pricing and MOQ fields

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Add wholesale fields to erp_items table
ALTER TABLE `erp_items` 
ADD COLUMN `wholesale_moq` DECIMAL(10,2) DEFAULT 0.00 AFTER `reorder_quantity`,
ADD COLUMN `is_wholesale_enabled` TINYINT(1) DEFAULT 0 AFTER `item_status`;

-- 2. Create erp_customer_types table
CREATE TABLE IF NOT EXISTS `erp_customer_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT 'Retail, Wholesale, VIP, Distributor, Corporate',
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `discount_percentage` DECIMAL(5,2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default customer types
INSERT IGNORE INTO `erp_customer_types` (`name`, `code`, `description`, `discount_percentage`) VALUES
('Retail', 'RETAIL', 'Standard retail customers', 0.00),
('Wholesale', 'WHOLESALE', 'Wholesale customers with MOQ requirements', 0.00),
('VIP', 'VIP', 'High-value customers', 5.00),
('Distributor', 'DISTRIBUTOR', 'Large scale distributors', 10.00),
('Corporate', 'CORPORATE', 'Business/Corporate accounts', 7.50);

-- 3. Add customer_type_id to erp_customers table
-- Requirements said companies table, but in this system customers are in erp_customers
-- and companies might be entities. I will add to both if necessary or just customers.
-- Since Sales/Invoices use customers, I will prioritize erp_customers.
ALTER TABLE `erp_customers` 
ADD COLUMN `customer_type_id` INT(11) DEFAULT NULL AFTER `currency`,
ADD CONSTRAINT `fk_customers_type` FOREIGN KEY (`customer_type_id`) REFERENCES `erp_customer_types` (`id`) ON DELETE SET NULL;

-- 4. Create erp_discount_tiers table
CREATE TABLE IF NOT EXISTS `erp_discount_tiers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `item_id` INT(11) NOT NULL,
    `min_quantity` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_type` ENUM('percentage', 'fixed_price') DEFAULT 'percentage',
    `discount_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_item_id` (`item_id`),
    CONSTRAINT `fk_discount_tiers_item` FOREIGN KEY (`item_id`) REFERENCES `erp_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create erp_price_history table
CREATE TABLE IF NOT EXISTS `erp_price_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `item_id` INT(11) NOT NULL,
    `old_retail_price` DECIMAL(15,2) NOT NULL,
    `new_retail_price` DECIMAL(15,2) NOT NULL,
    `old_wholesale_price` DECIMAL(15,2) NOT NULL,
    `new_wholesale_price` DECIMAL(15,2) NOT NULL,
    `changed_by` INT(11) NOT NULL,
    `change_reason` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_item_id` (`item_id`),
    CONSTRAINT `fk_price_history_item` FOREIGN KEY (`item_id`) REFERENCES `erp_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Add permissions for Wholesale Pricing
INSERT IGNORE INTO `erp_permissions` (`module`, `permission`, `description`, `created_at`) VALUES
('wholesale_pricing', 'read', 'View wholesale pricing and MOQ', NOW()),
('wholesale_pricing', 'create', 'Create wholesale pricing rules', NOW()),
('wholesale_pricing', 'update', 'Update wholesale pricing and MOQ', NOW()),
('wholesale_pricing', 'delete', 'Delete wholesale pricing rules', NOW()),
('customer_types', 'read', 'View customer types', NOW()),
('customer_types', 'write', 'Manage customer types', NOW());

-- Assign permissions to super_admin and admin
INSERT INTO `erp_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT r.id, p.id, NOW()
FROM `erp_roles` r
CROSS JOIN `erp_permissions` p
WHERE r.role_code IN ('super_admin', 'admin')
AND p.module IN ('wholesale_pricing', 'customer_types')
AND NOT EXISTS (
    SELECT 1 FROM `erp_role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
);

SET FOREIGN_KEY_CHECKS = 1;
