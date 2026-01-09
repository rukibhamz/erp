-- ============================================================================
-- COMPLETE SYSTEM TABLES FIX - RUN DIRECTLY IN PHPMYADMIN
-- ============================================================================
-- This migration creates ALL missing tables and views for the ERP system.
-- RUN THIS IN PHPMYADMIN: Select your 'erps' database, go to SQL tab, paste, Execute
-- IDEMPOTENT - Safe to run multiple times
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================================================
-- INVENTORY TABLES
-- ============================================================================

-- Stock Transactions
CREATE TABLE IF NOT EXISTS `erp_stock_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_number` VARCHAR(50) NOT NULL,
    `transaction_type` ENUM('receive','issue','transfer','adjust','return','sale') NOT NULL,
    `item_id` INT(11) NOT NULL,
    `location_from_id` INT(11) DEFAULT NULL,
    `location_to_id` INT(11) DEFAULT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,2) DEFAULT 0,
    `unit_price` DECIMAL(15,2) DEFAULT 0,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT(11) DEFAULT NULL,
    `transaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_transaction_number` (`transaction_number`),
    KEY `idx_item_id` (`item_id`),
    KEY `idx_transaction_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Adjustments
CREATE TABLE IF NOT EXISTS `erp_stock_adjustments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `adjustment_number` VARCHAR(50) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `location_id` INT(11) DEFAULT NULL,
    `adjustment_type` ENUM('addition', 'subtraction', 'reset') NOT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `reason` TEXT DEFAULT NULL,
    `adjusted_by` INT(11) DEFAULT NULL,
    `adjustment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('draft', 'posted', 'cancelled') DEFAULT 'draft',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_adjustment_number` (`adjustment_number`),
    KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Takes (Physical Inventory)
CREATE TABLE IF NOT EXISTS `erp_stock_takes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `stock_take_number` VARCHAR(50) NOT NULL,
    `location_id` INT(11) DEFAULT NULL,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `status` ENUM('in_progress', 'completed', 'cancelled') DEFAULT 'in_progress',
    `conducted_by` INT(11) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_stock_take_number` (`stock_take_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Take Items
CREATE TABLE IF NOT EXISTS `erp_stock_take_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `stock_take_id` INT(11) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `system_quantity` DECIMAL(15,4) DEFAULT 0,
    `counted_quantity` DECIMAL(15,4) DEFAULT NULL,
    `variance` DECIMAL(15,4) DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_stock_take_id` (`stock_take_id`),
    KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUPPLIER/PURCHASING TABLES
-- ============================================================================

-- Suppliers
CREATE TABLE IF NOT EXISTS `erp_suppliers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `supplier_code` VARCHAR(50) NOT NULL,
    `supplier_name` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'Nigeria',
    `tax_number` VARCHAR(50) DEFAULT NULL,
    `payment_terms` INT(11) DEFAULT 0,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_supplier_code` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Orders
CREATE TABLE IF NOT EXISTS `erp_purchase_orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `po_number` VARCHAR(50) NOT NULL,
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
    KEY `idx_supplier_id` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchase Order Items
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

-- Goods Receipts
CREATE TABLE IF NOT EXISTS `erp_goods_receipts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `grn_number` VARCHAR(50) NOT NULL,
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
    KEY `idx_supplier_id` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goods Receipt Items
CREATE TABLE IF NOT EXISTS `erp_goods_receipt_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `grn_id` INT(11) NOT NULL,
    `item_id` INT(11) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `received_quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,2) DEFAULT 0,
    `line_total` DECIMAL(15,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_grn_id` (`grn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- BOOKING TABLES
-- ============================================================================

-- Booking Add-ons
CREATE TABLE IF NOT EXISTS `erp_booking_addons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `booking_id` INT(11) NOT NULL,
    `addon_id` INT(11) NOT NULL,
    `addon_name` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(10,2) DEFAULT 1,
    `unit_price` DECIMAL(15,2) DEFAULT 0,
    `total_price` DECIMAL(15,2) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_booking_id` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PROPERTY/LOCATION TABLES & VIEWS
-- ============================================================================

-- Locations View (points to properties for compatibility)
DROP VIEW IF EXISTS `erp_locations`;
CREATE VIEW `erp_locations` AS 
SELECT *, id AS location_id, property_name AS location_name FROM `erp_properties`;

-- Tenants
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
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rent Invoices
CREATE TABLE IF NOT EXISTS `erp_rent_invoices` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL,
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
    UNIQUE KEY `unique_invoice_number` (`invoice_number`),
    KEY `idx_lease_id` (`lease_id`),
    KEY `idx_tenant_id` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- UTILITIES TABLES
-- ============================================================================

-- Utility Meters
CREATE TABLE IF NOT EXISTS `erp_meters` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `meter_number` VARCHAR(100) NOT NULL,
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
    UNIQUE KEY `unique_meter_number` (`meter_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meter Readings
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
    KEY `idx_meter_id` (`meter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utility Providers
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
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utility Tariffs
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
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utility Payments
CREATE TABLE IF NOT EXISTS `erp_utility_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `bill_id` INT(11) NOT NULL,
    `payment_number` VARCHAR(50) NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_payment_number` (`payment_number`),
    KEY `idx_bill_id` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- PAYROLL TABLES
-- ============================================================================

-- Payroll Runs
CREATE TABLE IF NOT EXISTS `erp_payroll_runs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `period_name` VARCHAR(100) NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `run_date` DATE NOT NULL,
    `total_gross` DECIMAL(15,2) DEFAULT 0,
    `total_deductions` DECIMAL(15,2) DEFAULT 0,
    `total_net` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'processed', 'approved', 'paid', 'cancelled') DEFAULT 'draft',
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payslips
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

-- PAYE Deductions
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
    KEY `idx_employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- TAX TABLES
-- ============================================================================

-- Tax Payments
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
    KEY `idx_tax_type_id` (`tax_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- WHT Certificates
CREATE TABLE IF NOT EXISTS `erp_wht_certificates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `certificate_number` VARCHAR(100) NOT NULL,
    `vendor_id` INT(11) NOT NULL,
    `bill_id` INT(11) DEFAULT NULL,
    `issue_date` DATE NOT NULL,
    `amount_withheld` DECIMAL(15,2) NOT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','received','used') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_cert_number` (`certificate_number`),
    KEY `idx_vendor_id` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- FIXED ASSETS TABLES
-- ============================================================================

-- Fixed Assets
CREATE TABLE IF NOT EXISTS `erp_fixed_assets` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `asset_code` VARCHAR(50) NOT NULL,
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
    UNIQUE KEY `unique_asset_code` (`asset_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- RECORD MIGRATION EXECUTION
-- ============================================================================

-- Mark migrations as executed (if migrations table exists)
INSERT IGNORE INTO `erp_migrations` (`migration`, `batch`, `executed_at`) VALUES
('018_comprehensive_business_fix.sql', 99, NOW()),
('019_massive_system_fix.sql', 99, NOW()),
('020_complete_system_tables.sql', 99, NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- DONE! All tables created successfully.
-- ============================================================================
