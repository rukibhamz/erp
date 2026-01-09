-- ============================================================================
-- COMPREHENSIVE BUSINESS MODULE TABLES FIX
-- ============================================================================
-- This migration creates all missing tables referenced by various business modules
-- (Inventory, Purchases, Utilities, Fixed Assets, Bookings, Payroll, Tax)
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. INVENTORY: STOCK TRANSACTIONS
CREATE TABLE IF NOT EXISTS `erp_stock_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(50) NOT NULL UNIQUE,
    `transaction_type` ENUM('receive','issue','transfer','adjust','return','sale') NOT NULL,
    `item_id` INT(11) NOT NULL,
    `location_from_id` INT(11) DEFAULT NULL,
    `location_to_id` INT(11) DEFAULT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,2) DEFAULT 0,
    `unit_price` DECIMAL(15,2) DEFAULT 0,
    `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'purchase_order, goods_receipt, invoice, work_order, etc.',
    `reference_id` INT(11) DEFAULT NULL,
    `transaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_transaction_number` (`transaction_number`),
    KEY `idx_item_id` (`item_id`),
    KEY `idx_location_from_id` (`location_from_id`),
    KEY `idx_location_to_id` (`location_to_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. INVENTORY: SUPPLIERS
CREATE TABLE IF NOT EXISTS `erp_suppliers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `supplier_code` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_name` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'Nigeria',
    `tax_number` VARCHAR(50) DEFAULT NULL,
    `payment_terms` INT(11) DEFAULT 0 COMMENT 'Days',
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_supplier_code` (`supplier_code`),
    KEY `idx_supplier_name` (`supplier_name`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PURCHASES: PURCHASE ORDERS
CREATE TABLE IF NOT EXISTS `erp_purchase_orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `po_number` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_id` INT(11) NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_date` DATE DEFAULT NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `tax_total` DECIMAL(15,2) DEFAULT 0,
    `total_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft','sent','partially_received','received','cancelled','closed') DEFAULT 'draft',
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_po_number` (`po_number`),
    KEY `idx_supplier_id` (`supplier_id`),
    KEY `idx_status` (`status`),
    KEY `idx_order_date` (`order_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PURCHASES: PURCHASE ORDER ITEMS
CREATE TABLE IF NOT EXISTS `erp_purchase_order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `po_id` INT(11) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `received_quantity` DECIMAL(15,4) DEFAULT 0,
    `unit_price` DECIMAL(15,2) NOT NULL,
    `tax_rate` DECIMAL(5,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `line_total` DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_po_id` (`po_id`),
    KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. UTILITIES: METERS
CREATE TABLE IF NOT EXISTS `erp_meters` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `meter_number` VARCHAR(100) NOT NULL UNIQUE,
    `utility_type_id` INT(11) NOT NULL,
    `property_id` INT(11) DEFAULT NULL,
    `space_id` INT(11) DEFAULT NULL,
    `tenant_id` INT(11) DEFAULT NULL,
    `meter_location` VARCHAR(255) DEFAULT NULL,
    `initial_reading` DECIMAL(15,4) DEFAULT 0,
    `current_reading` DECIMAL(15,4) DEFAULT 0,
    `last_reading_date` DATETIME DEFAULT NULL,
    `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_meter_number` (`meter_number`),
    KEY `idx_utility_type_id` (`utility_type_id`),
    KEY `idx_property_id` (`property_id`),
    KEY `idx_space_id` (`space_id`),
    KEY `idx_tenant_id` (`tenant_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. UTILITIES: METER READINGS
CREATE TABLE IF NOT EXISTS `erp_meter_readings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `meter_id` INT(11) NOT NULL,
    `reading_date` DATETIME NOT NULL,
    `reading_value` DECIMAL(15,4) NOT NULL,
    `consumption` DECIMAL(15,4) DEFAULT 0,
    `reading_type` ENUM('actual','estimated','initial') DEFAULT 'actual',
    `recorded_by` INT(11) DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_meter_id` (`meter_id`),
    KEY `idx_reading_date` (`reading_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. UTILITIES: UTILITY PROVIDERS
CREATE TABLE IF NOT EXISTS `erp_utility_providers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `provider_name` VARCHAR(255) NOT NULL,
    `utility_type_id` INT(11) NOT NULL,
    `contact_person` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `account_number` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_utility_type_id` (`utility_type_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. UTILITIES: TARIFFS
CREATE TABLE IF NOT EXISTS `erp_tariffs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `utility_type_id` INT(11) NOT NULL,
    `tariff_name` VARCHAR(100) NOT NULL,
    `rate_per_unit` DECIMAL(15,4) NOT NULL,
    `fixed_charge` DECIMAL(15,2) DEFAULT 0,
    `min_charge` DECIMAL(15,2) DEFAULT 0,
    `effective_from` DATE DEFAULT NULL,
    `effective_to` DATE DEFAULT NULL,
    `status` ENUM('active','expired','pending') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_utility_type_id` (`utility_type_id`),
    KEY `idx_status` (`status`),
    KEY `idx_effective_from` (`effective_from`),
    KEY `idx_effective_to` (`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ASSETS: FIXED ASSETS
CREATE TABLE IF NOT EXISTS `erp_fixed_assets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asset_code` VARCHAR(50) NOT NULL UNIQUE,
    `asset_name` VARCHAR(255) NOT NULL,
    `category_id` INT(11) DEFAULT NULL,
    `property_id` INT(11) DEFAULT NULL,
    `space_id` INT(11) DEFAULT NULL,
    `purchase_date` DATE DEFAULT NULL,
    `purchase_value` DECIMAL(15,2) DEFAULT 0,
    `current_value` DECIMAL(15,2) DEFAULT 0,
    `depreciation_method` ENUM('straight_line','declining_balance','none') DEFAULT 'straight_line',
    `useful_life_years` INT(11) DEFAULT 5,
    `residual_value` DECIMAL(15,2) DEFAULT 0,
    `serial_number` VARCHAR(100) DEFAULT NULL,
    `manufacturer` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active','disposed','under_repair','lost') DEFAULT 'active',
    `disposal_date` DATE DEFAULT NULL,
    `disposal_value` DECIMAL(15,2) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_asset_code` (`asset_code`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_property_id` (`property_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. BOOKINGS: ADD-ONS (BOOKABLE EXTRAS)
CREATE TABLE IF NOT EXISTS `erp_booking_addons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `addon_id` INT(11) NOT NULL,
    `addon_name` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(10,2) DEFAULT 1,
    `unit_price` DECIMAL(15,2) DEFAULT 0,
    `total_price` DECIMAL(15,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_booking_id` (`booking_id`),
    KEY `idx_addon_id` (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. PAYROLL: PAYE DEDUCTIONS
CREATE TABLE IF NOT EXISTS `erp_paye_deductions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `payslip_id` INT(11) NOT NULL,
    `employee_id` INT(11) NOT NULL,
    `period` VARCHAR(7) NOT NULL,
    `taxable_income` DECIMAL(15,2) DEFAULT 0,
    `paye_amount` DECIMAL(15,2) DEFAULT 0,
    `posted_to_tax` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payslip_id` (`payslip_id`),
    KEY `idx_employee_id` (`employee_id`),
    KEY `idx_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. TAX: TAX PAYMENTS
CREATE TABLE IF NOT EXISTS `erp_tax_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `tax_type_id` INT(11) NOT NULL,
    `payment_date` DATE NOT NULL,
    `period_start` DATE DEFAULT NULL,
    `period_end` DATE DEFAULT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('draft','posted','cancelled') DEFAULT 'posted',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tax_type_id` (`tax_type_id`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. TAX: WHT CERTIFICATES
CREATE TABLE IF NOT EXISTS `erp_wht_certificates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `certificate_number` VARCHAR(100) NOT NULL UNIQUE,
    `vendor_id` INT(11) NOT NULL,
    `bill_id` INT(11) DEFAULT NULL,
    `issue_date` DATE NOT NULL,
    `amount_withheld` DECIMAL(15,2) NOT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','received','used') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_cert_number` (`certificate_number`),
    KEY `idx_vendor_id` (`vendor_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. INVENTORY: GOODS RECEIPTS
CREATE TABLE IF NOT EXISTS `erp_goods_receipts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `grn_number` VARCHAR(50) NOT NULL UNIQUE,
    `po_id` INT(11) DEFAULT NULL,
    `supplier_id` INT(11) NOT NULL,
    `received_date` DATE NOT NULL,
    `location_id` INT(11) DEFAULT NULL,
    `status` ENUM('draft','posted','cancelled') DEFAULT 'draft',
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_grn_number` (`grn_number`),
    KEY `idx_po_id` (`po_id`),
    KEY `idx_supplier_id` (`supplier_id`),
    KEY `idx_status` (`status`),
    KEY `idx_received_date` (`received_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. INVENTORY: GOODS RECEIPT ITEMS
CREATE TABLE IF NOT EXISTS `erp_goods_receipt_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `grn_id` INT(11) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `received_quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,2) DEFAULT 0,
    `line_total` DECIMAL(15,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_grn_id` (`grn_id`),
    KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
