<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function runBookingMigrations($pdo, $prefix = '') {
    try {
        // Facilities table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}facilities` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `facility_code` VARCHAR(50) NOT NULL,
            `facility_name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `capacity` INT(11) DEFAULT 0,
            `hourly_rate` DECIMAL(15,2) DEFAULT 0.00,
            `daily_rate` DECIMAL(15,2) DEFAULT 0.00,
            `weekend_rate` DECIMAL(15,2) DEFAULT 0.00,
            `peak_rate` DECIMAL(15,2) DEFAULT 0.00,
            `security_deposit` DECIMAL(15,2) DEFAULT 0.00,
            `minimum_duration` INT(11) DEFAULT 1 COMMENT 'Hours',
            `setup_time` INT(11) DEFAULT 0 COMMENT 'Minutes',
            `cleanup_time` INT(11) DEFAULT 0 COMMENT 'Minutes',
            `amenities` TEXT COMMENT 'JSON array of amenities',
            `features` TEXT COMMENT 'JSON array of features',
            `pricing_rules` TEXT COMMENT 'JSON for pricing rules',
            `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `facility_code` (`facility_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Facility photos table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}facility_photos` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `facility_id` INT(11) UNSIGNED NOT NULL,
            `photo_path` VARCHAR(255) NOT NULL,
            `photo_name` VARCHAR(255),
            `is_primary` TINYINT(1) DEFAULT 0,
            `display_order` INT(11) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `facility_id` (`facility_id`),
            CONSTRAINT `{$prefix}facility_photos_ibfk_1` FOREIGN KEY (`facility_id`) 
                REFERENCES `{$prefix}facilities` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Bookings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}bookings` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `booking_number` VARCHAR(50) NOT NULL,
            `facility_id` INT(11) UNSIGNED NOT NULL,
            `customer_name` VARCHAR(255) NOT NULL,
            `customer_email` VARCHAR(255),
            `customer_phone` VARCHAR(50),
            `customer_address` TEXT,
            `booking_date` DATE NOT NULL,
            `start_time` TIME NOT NULL,
            `end_time` TIME NOT NULL,
            `duration_hours` DECIMAL(10,2) DEFAULT 0.00,
            `number_of_guests` INT(11) DEFAULT 0,
            `booking_type` ENUM('hourly', 'daily') DEFAULT 'hourly',
            `base_amount` DECIMAL(15,2) DEFAULT 0.00,
            `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
            `tax_amount` DECIMAL(15,2) DEFAULT 0.00,
            `security_deposit` DECIMAL(15,2) DEFAULT 0.00,
            `total_amount` DECIMAL(15,2) DEFAULT 0.00,
            `paid_amount` DECIMAL(15,2) DEFAULT 0.00,
            `balance_amount` DECIMAL(15,2) DEFAULT 0.00,
            `refund_amount` DECIMAL(15,2) DEFAULT 0.00,
            `currency` VARCHAR(3) DEFAULT 'NGN',
            `status` ENUM('pending', 'confirmed', 'cancelled', 'completed', 'refunded') DEFAULT 'pending',
            `payment_status` ENUM('unpaid', 'partial', 'paid', 'overpaid', 'refunded') DEFAULT 'unpaid',
            `booking_notes` TEXT,
            `special_requests` TEXT,
            `cancellation_reason` TEXT,
            `confirmed_at` TIMESTAMP NULL,
            `cancelled_at` TIMESTAMP NULL,
            `completed_at` TIMESTAMP NULL,
            `created_by` INT(11) UNSIGNED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `booking_number` (`booking_number`),
            KEY `facility_id` (`facility_id`),
            KEY `booking_date` (`booking_date`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}bookings_ibfk_1` FOREIGN KEY (`facility_id`) 
                REFERENCES `{$prefix}facilities` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Booking payments table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}booking_payments` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `booking_id` INT(11) UNSIGNED NOT NULL,
            `payment_number` VARCHAR(50) NOT NULL,
            `payment_date` DATE NOT NULL,
            `payment_type` ENUM('deposit', 'partial', 'full', 'refund', 'deposit_refund') DEFAULT 'partial',
            `payment_method` VARCHAR(50) DEFAULT 'cash',
            `amount` DECIMAL(15,2) NOT NULL,
            `currency` VARCHAR(3) DEFAULT 'NGN',
            `reference_number` VARCHAR(255),
            `notes` TEXT,
            `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
            `created_by` INT(11) UNSIGNED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `booking_id` (`booking_id`),
            KEY `payment_date` (`payment_date`),
            CONSTRAINT `{$prefix}booking_payments_ibfk_1` FOREIGN KEY (`booking_id`) 
                REFERENCES `{$prefix}bookings` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Booking slots (for availability tracking)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}booking_slots` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `booking_id` INT(11) UNSIGNED NOT NULL,
            `facility_id` INT(11) UNSIGNED NOT NULL,
            `slot_date` DATE NOT NULL,
            `slot_start_time` TIME NOT NULL,
            `slot_end_time` TIME NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `facility_id` (`facility_id`),
            KEY `slot_date` (`slot_date`),
            KEY `booking_id` (`booking_id`),
            CONSTRAINT `{$prefix}booking_slots_ibfk_1` FOREIGN KEY (`booking_id`) 
                REFERENCES `{$prefix}bookings` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}booking_slots_ibfk_2` FOREIGN KEY (`facility_id`) 
                REFERENCES `{$prefix}facilities` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Add indexes for performance
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_date_time ON `{$prefix}bookings` (`booking_date`, `start_time`, `end_time`)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bookings_status_date ON `{$prefix}bookings` (`status`, `booking_date`)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_slots_facility_date ON `{$prefix}booking_slots` (`facility_id`, `slot_date`, `slot_start_time`)");

        return true;
    } catch (PDOException $e) {
        error_log('Booking migrations error: ' . $e->getMessage());
        return false;
    }
}

