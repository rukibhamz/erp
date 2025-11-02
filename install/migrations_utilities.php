<?php
/**
 * Database Migration Script for Utilities Management Module
 */

function runUtilitiesMigrations($pdo, $prefix = 'erp_') {
    try {
        // Utility Types
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}utility_types` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `code` VARCHAR(50) NOT NULL UNIQUE,
            `unit_of_measure` VARCHAR(20) NOT NULL COMMENT 'kWh, m³, liters, etc.',
            `icon` VARCHAR(50) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Utility Providers
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}utility_providers` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `provider_name` VARCHAR(255) NOT NULL,
            `utility_type_id` INT(11) NOT NULL,
            `account_number` VARCHAR(100) DEFAULT NULL,
            `contact_person` VARCHAR(255) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `service_areas` TEXT DEFAULT NULL COMMENT 'JSON array of service areas',
            `payment_terms` INT(11) DEFAULT 30 COMMENT 'days',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `utility_type_id` (`utility_type_id`),
            CONSTRAINT `{$prefix}utility_providers_ibfk_1` FOREIGN KEY (`utility_type_id`) REFERENCES `{$prefix}utility_types` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Tariffs
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}tariffs` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `provider_id` INT(11) NOT NULL,
            `tariff_name` VARCHAR(255) NOT NULL,
            `effective_date` DATE NOT NULL,
            `expiry_date` DATE DEFAULT NULL,
            `structure_json` TEXT NOT NULL COMMENT 'JSON: fixed_charge, variable_rate, tiered_rates, demand_charge, taxes, etc.',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `provider_id` (`provider_id`),
            KEY `effective_date` (`effective_date`),
            CONSTRAINT `{$prefix}tariffs_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `{$prefix}utility_providers` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Meters
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}meters` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `meter_number` VARCHAR(100) NOT NULL UNIQUE,
            `utility_type_id` INT(11) NOT NULL,
            `meter_type` ENUM('master','sub_meter') DEFAULT 'master',
            `parent_meter_id` INT(11) DEFAULT NULL COMMENT 'For sub-meters',
            `property_id` INT(11) DEFAULT NULL,
            `space_id` INT(11) DEFAULT NULL,
            `tenant_id` INT(11) DEFAULT NULL,
            `meter_location` VARCHAR(255) DEFAULT NULL,
            `installation_date` DATE DEFAULT NULL,
            `meter_make` VARCHAR(100) DEFAULT NULL,
            `meter_model` VARCHAR(100) DEFAULT NULL,
            `meter_capacity` VARCHAR(50) DEFAULT NULL,
            `meter_rating` VARCHAR(50) DEFAULT NULL,
            `meter_photo` VARCHAR(255) DEFAULT NULL,
            `last_calibration_date` DATE DEFAULT NULL,
            `next_calibration_due` DATE DEFAULT NULL,
            `initial_reading` DECIMAL(15,4) DEFAULT 0.0000,
            `last_reading` DECIMAL(15,4) DEFAULT 0.0000,
            `last_reading_date` DATE DEFAULT NULL,
            `reading_frequency` ENUM('daily','weekly','monthly','quarterly') DEFAULT 'monthly',
            `barcode` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('active','faulty','retired','maintenance') DEFAULT 'active',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `meter_number` (`meter_number`),
            KEY `utility_type_id` (`utility_type_id`),
            KEY `property_id` (`property_id`),
            KEY `space_id` (`space_id`),
            KEY `tenant_id` (`tenant_id`),
            KEY `parent_meter_id` (`parent_meter_id`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}meters_ibfk_1` FOREIGN KEY (`utility_type_id`) REFERENCES `{$prefix}utility_types` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}meters_ibfk_2` FOREIGN KEY (`parent_meter_id`) REFERENCES `{$prefix}meters` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Meter Readings
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}meter_readings` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `meter_id` INT(11) NOT NULL,
            `reading_date` DATE NOT NULL,
            `reading_value` DECIMAL(15,4) NOT NULL,
            `previous_reading` DECIMAL(15,4) DEFAULT NULL,
            `consumption` DECIMAL(15,4) DEFAULT NULL COMMENT 'Auto-calculated',
            `reading_type` ENUM('actual','estimated','corrected') DEFAULT 'actual',
            `reader_id` INT(11) DEFAULT NULL COMMENT 'user_id',
            `reader_name` VARCHAR(255) DEFAULT NULL,
            `photo_url` VARCHAR(255) DEFAULT NULL,
            `gps_latitude` DECIMAL(10,8) DEFAULT NULL,
            `gps_longitude` DECIMAL(11,8) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `is_verified` TINYINT(1) DEFAULT 0,
            `verified_by` INT(11) DEFAULT NULL,
            `verified_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `meter_id` (`meter_id`),
            KEY `reading_date` (`reading_date`),
            KEY `reading_type` (`reading_type`),
            KEY `reader_id` (`reader_id`),
            CONSTRAINT `{$prefix}meter_readings_ibfk_1` FOREIGN KEY (`meter_id`) REFERENCES `{$prefix}meters` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Utility Bills
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}utility_bills` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `bill_number` VARCHAR(100) NOT NULL UNIQUE,
            `meter_id` INT(11) NOT NULL,
            `provider_id` INT(11) DEFAULT NULL,
            `billing_period_start` DATE NOT NULL,
            `billing_period_end` DATE NOT NULL,
            `billing_date` DATE NOT NULL,
            `due_date` DATE NOT NULL,
            `previous_reading` DECIMAL(15,4) DEFAULT NULL,
            `current_reading` DECIMAL(15,4) NOT NULL,
            `consumption` DECIMAL(15,4) NOT NULL,
            `consumption_unit` VARCHAR(20) DEFAULT NULL,
            `fixed_charge` DECIMAL(15,2) DEFAULT 0.00,
            `variable_charge` DECIMAL(15,2) DEFAULT 0.00,
            `demand_charge` DECIMAL(15,2) DEFAULT 0.00,
            `tax_amount` DECIMAL(15,2) DEFAULT 0.00,
            `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
            `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
            `surcharge_amount` DECIMAL(15,2) DEFAULT 0.00,
            `previous_balance` DECIMAL(15,2) DEFAULT 0.00,
            `amount` DECIMAL(15,2) NOT NULL,
            `total_amount` DECIMAL(15,2) NOT NULL,
            `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
            `balance_amount` DECIMAL(15,2) DEFAULT 0.00,
            `bill_type` ENUM('actual','estimated','adjusted') DEFAULT 'actual',
            `status` ENUM('draft','sent','paid','partial','overdue','cancelled') DEFAULT 'draft',
            `pdf_url` VARCHAR(255) DEFAULT NULL,
            `email_sent` TINYINT(1) DEFAULT 0,
            `sent_at` DATETIME DEFAULT NULL,
            `paid_date` DATETIME DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `bill_number` (`bill_number`),
            KEY `meter_id` (`meter_id`),
            KEY `provider_id` (`provider_id`),
            KEY `billing_period_start` (`billing_period_start`),
            KEY `billing_period_end` (`billing_period_end`),
            KEY `status` (`status`),
            KEY `due_date` (`due_date`),
            CONSTRAINT `{$prefix}utility_bills_ibfk_1` FOREIGN KEY (`meter_id`) REFERENCES `{$prefix}meters` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `{$prefix}utility_bills_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `{$prefix}utility_providers` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Utility Payments
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}utility_payments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `payment_number` VARCHAR(100) NOT NULL UNIQUE,
            `bill_id` INT(11) NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `payment_date` DATE NOT NULL,
            `payment_method` ENUM('cash','bank_transfer','cheque','online','card','other') DEFAULT 'bank_transfer',
            `reference_number` VARCHAR(255) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `payment_number` (`payment_number`),
            KEY `bill_id` (`bill_id`),
            KEY `payment_date` (`payment_date`),
            CONSTRAINT `{$prefix}utility_payments_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `{$prefix}utility_bills` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Vendor Bills (bills from utility providers)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}vendor_utility_bills` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `vendor_bill_number` VARCHAR(100) NOT NULL,
            `provider_id` INT(11) NOT NULL,
            `bill_date` DATE NOT NULL,
            `due_date` DATE NOT NULL,
            `period_start` DATE DEFAULT NULL,
            `period_end` DATE DEFAULT NULL,
            `consumption` DECIMAL(15,4) DEFAULT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `tax_amount` DECIMAL(15,2) DEFAULT 0.00,
            `total_amount` DECIMAL(15,2) NOT NULL,
            `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
            `balance_amount` DECIMAL(15,2) DEFAULT 0.00,
            `status` ENUM('pending','verified','approved','paid','overdue') DEFAULT 'pending',
            `pdf_url` VARCHAR(255) DEFAULT NULL,
            `verified_by` INT(11) DEFAULT NULL,
            `verified_at` DATETIME DEFAULT NULL,
            `approved_by` INT(11) DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `paid_date` DATETIME DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `provider_id` (`provider_id`),
            KEY `bill_date` (`bill_date`),
            KEY `due_date` (`due_date`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}vendor_utility_bills_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `{$prefix}utility_providers` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Consumption Targets
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}consumption_targets` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `property_id` INT(11) DEFAULT NULL,
            `space_id` INT(11) DEFAULT NULL,
            `utility_type_id` INT(11) NOT NULL,
            `meter_id` INT(11) DEFAULT NULL,
            `target_amount` DECIMAL(15,4) NOT NULL COMMENT 'Target consumption',
            `period_start` DATE NOT NULL,
            `period_end` DATE NOT NULL,
            `period_type` ENUM('daily','weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `space_id` (`space_id`),
            KEY `utility_type_id` (`utility_type_id`),
            KEY `meter_id` (`meter_id`),
            KEY `period_start` (`period_start`),
            KEY `period_end` (`period_end`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Utility Budgets
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}utility_budgets` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `property_id` INT(11) DEFAULT NULL,
            `utility_type_id` INT(11) NOT NULL,
            `budget_year` INT(4) NOT NULL,
            `monthly_budgets_json` TEXT NOT NULL COMMENT 'JSON: {1: amount, 2: amount, ...}',
            `total_budget` DECIMAL(15,2) NOT NULL,
            `created_by` INT(11) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `utility_type_id` (`utility_type_id`),
            KEY `budget_year` (`budget_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Utility Allocations (for tenant/department billing)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}utility_allocations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `bill_id` INT(11) NOT NULL,
            `tenant_id` INT(11) DEFAULT NULL,
            `space_id` INT(11) DEFAULT NULL,
            `department_id` INT(11) DEFAULT NULL,
            `allocation_method` ENUM('direct','proportional','equal_split','custom') DEFAULT 'direct',
            `allocation_percentage` DECIMAL(5,2) DEFAULT NULL,
            `allocation_amount` DECIMAL(15,2) NOT NULL,
            `allocated_consumption` DECIMAL(15,4) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `bill_id` (`bill_id`),
            KEY `tenant_id` (`tenant_id`),
            KEY `space_id` (`space_id`),
            CONSTRAINT `{$prefix}utility_allocations_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `{$prefix}utility_bills` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Meter Alerts
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}meter_alerts` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `meter_id` INT(11) NOT NULL,
            `alert_type` ENUM('missed_reading','unusual_consumption','meter_fault','calibration_due','high_consumption','zero_consumption','reverse_consumption') NOT NULL,
            `alert_date` DATETIME NOT NULL,
            `description` TEXT NOT NULL,
            `severity` ENUM('low','medium','high','critical') DEFAULT 'medium',
            `is_resolved` TINYINT(1) DEFAULT 0,
            `resolved_at` DATETIME DEFAULT NULL,
            `resolved_by` INT(11) DEFAULT NULL,
            `resolution_notes` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `meter_id` (`meter_id`),
            KEY `alert_type` (`alert_type`),
            KEY `is_resolved` (`is_resolved`),
            KEY `alert_date` (`alert_date`),
            CONSTRAINT `{$prefix}meter_alerts_ibfk_1` FOREIGN KEY (`meter_id`) REFERENCES `{$prefix}meters` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Reading Schedules
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}reading_schedules` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `meter_id` INT(11) NOT NULL,
            `schedule_type` ENUM('daily','weekly','monthly','quarterly') NOT NULL,
            `day_of_month` INT(2) DEFAULT NULL COMMENT 'For monthly: which day',
            `day_of_week` INT(1) DEFAULT NULL COMMENT '0=Sunday, 6=Saturday',
            `time_of_day` TIME DEFAULT NULL,
            `assigned_reader_id` INT(11) DEFAULT NULL,
            `route_id` INT(11) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `next_reading_date` DATE DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `meter_id` (`meter_id`),
            KEY `assigned_reader_id` (`assigned_reader_id`),
            KEY `next_reading_date` (`next_reading_date`),
            CONSTRAINT `{$prefix}reading_schedules_ibfk_1` FOREIGN KEY (`meter_id`) REFERENCES `{$prefix}meters` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insert default utility types
        $defaultTypes = [
            ['name' => 'Electricity', 'code' => 'electricity', 'unit_of_measure' => 'kWh', 'icon' => 'bi-lightning'],
            ['name' => 'Water', 'code' => 'water', 'unit_of_measure' => 'm³', 'icon' => 'bi-droplet'],
            ['name' => 'Gas', 'code' => 'gas', 'unit_of_measure' => 'm³', 'icon' => 'bi-fire'],
            ['name' => 'Internet/Broadband', 'code' => 'internet', 'unit_of_measure' => 'GB', 'icon' => 'bi-wifi'],
            ['name' => 'Phone/Mobile', 'code' => 'phone', 'unit_of_measure' => 'minutes', 'icon' => 'bi-telephone'],
            ['name' => 'Waste Management', 'code' => 'waste', 'unit_of_measure' => 'kg', 'icon' => 'bi-trash'],
            ['name' => 'Sewage', 'code' => 'sewage', 'unit_of_measure' => 'm³', 'icon' => 'bi-water'],
            ['name' => 'HVAC', 'code' => 'hvac', 'unit_of_measure' => 'kWh', 'icon' => 'bi-snow'],
            ['name' => 'Security Services', 'code' => 'security', 'unit_of_measure' => 'hours', 'icon' => 'bi-shield']
        ];

        foreach ($defaultTypes as $type) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}utility_types` 
                    (name, code, unit_of_measure, icon, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())");
                $stmt->execute([$type['name'], $type['code'], $type['unit_of_measure'], $type['icon']]);
            } catch (PDOException $e) {
                error_log("Failed to insert utility type {$type['name']}: " . $e->getMessage());
            }
        }

        echo "Utilities management tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Utilities migration error: " . $e->getMessage());
        throw $e;
    }
}

