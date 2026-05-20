-- Flutterwave collection subaccounts & split rules (optional feature)
-- AutoMigration also applies these changes on existing installs.

CREATE TABLE IF NOT EXISTS `erp_flutterwave_subaccounts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `subaccount_id` VARCHAR(64) NOT NULL COMMENT 'Flutterwave RS_… id',
    `flutterwave_numeric_id` INT(11) NULL,
    `business_name` VARCHAR(255) NOT NULL,
    `account_bank` VARCHAR(20) NOT NULL,
    `account_number` VARCHAR(64) NOT NULL,
    `account_number_masked` VARCHAR(64) NULL,
    `country` CHAR(2) NOT NULL DEFAULT 'NG',
    `split_type` ENUM('percentage','flat') NOT NULL DEFAULT 'percentage',
    `split_value` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `business_email` VARCHAR(255) NULL,
    `business_mobile` VARCHAR(50) NULL,
    `business_contact` VARCHAR(255) NULL,
    `business_contact_mobile` VARCHAR(50) NULL,
    `meta` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `test_mode` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_flw_subaccount_id` (`subaccount_id`),
    KEY `idx_flw_sub_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_flutterwave_split_rules` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `scope_type` ENUM('global','property','space') NOT NULL DEFAULT 'global',
    `scope_id` INT(11) NULL,
    `subaccount_row_id` INT(11) NOT NULL,
    `override_charge_type` VARCHAR(32) NULL,
    `override_charge` DECIMAL(12,4) NULL,
    `split_ratio` INT(11) NULL,
    `priority` INT(11) NOT NULL DEFAULT 0,
    `currency` VARCHAR(10) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_flw_rule_scope` (`scope_type`, `scope_id`),
    KEY `idx_flw_rule_active` (`is_active`),
    KEY `idx_flw_rule_subaccount` (`subaccount_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Run only if columns missing (AutoMigration handles this idempotently):
-- ALTER TABLE `erp_payment_transactions` ADD COLUMN `split_applied` TINYINT(1) NOT NULL DEFAULT 0;
-- ALTER TABLE `erp_payment_transactions` ADD COLUMN `split_payload` TEXT NULL;
