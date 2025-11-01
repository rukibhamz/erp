<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function runBookingEnhancedMigrations($pdo, $prefix = '') {
    try {
        // Helper function to check if column exists
        $columnExists = function($table, $column) use ($pdo, $prefix) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$prefix}{$table}` LIKE '{$column}'");
                return $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                return false;
            }
        };
        
        // Enhanced Resources table (facilities)
        $resourceColumns = [
            'resource_type' => "ADD COLUMN `resource_type` enum('hall','meeting_room','equipment','vehicle','staff','other') DEFAULT 'hall' AFTER `facility_code`",
            'category' => "ADD COLUMN `category` varchar(100) DEFAULT NULL AFTER `resource_type`",
            'location' => "ADD COLUMN `location` varchar(255) DEFAULT NULL AFTER `category`",
            'building' => "ADD COLUMN `building` varchar(255) DEFAULT NULL AFTER `location`",
            'min_capacity' => "ADD COLUMN `min_capacity` int(11) DEFAULT NULL AFTER `capacity`",
            'setup_time' => "ADD COLUMN `setup_time` int(11) DEFAULT 0 COMMENT 'minutes' AFTER `minimum_duration`",
            'cleanup_time' => "ADD COLUMN `cleanup_time` int(11) DEFAULT 0 COMMENT 'minutes' AFTER `setup_time`",
            'buffer_time' => "ADD COLUMN `buffer_time` int(11) DEFAULT 0 COMMENT 'minutes' AFTER `cleanup_time`",
            'amenities' => "ADD COLUMN `amenities` text DEFAULT NULL COMMENT 'JSON array of amenities' AFTER `description`",
            'lead_time' => "ADD COLUMN `lead_time` int(11) DEFAULT 0 COMMENT 'how far in advance (days)' AFTER `buffer_time`",
            'cutoff_time' => "ADD COLUMN `cutoff_time` int(11) DEFAULT 0 COMMENT 'minimum notice required (hours)' AFTER `lead_time`",
            'max_duration' => "ADD COLUMN `max_duration` int(11) DEFAULT NULL COMMENT 'maximum booking duration in hours' AFTER `minimum_duration`",
            'simultaneous_limit' => "ADD COLUMN `simultaneous_limit` int(11) DEFAULT 1 COMMENT 'how many simultaneous bookings allowed' AFTER `max_duration`",
            'slot_duration' => "ADD COLUMN `slot_duration` int(11) DEFAULT 60 COMMENT 'slot duration in minutes' AFTER `simultaneous_limit`",
            'allow_waitlist' => "ADD COLUMN `allow_waitlist` tinyint(1) DEFAULT 0 AFTER `simultaneous_limit`",
            'half_day_rate' => "ADD COLUMN `half_day_rate` decimal(15,2) DEFAULT 0.00 AFTER `daily_rate`",
            'weekly_rate' => "ADD COLUMN `weekly_rate` decimal(15,2) DEFAULT 0.00 AFTER `half_day_rate`",
            'member_rate' => "ADD COLUMN `member_rate` decimal(15,2) DEFAULT NULL AFTER `weekly_rate`",
            'seasonal_pricing' => "ADD COLUMN `seasonal_pricing` text DEFAULT NULL COMMENT 'JSON array of seasonal rates' AFTER `member_rate`",
            'pricing_rules' => "ADD COLUMN `pricing_rules` text DEFAULT NULL COMMENT 'JSON pricing rules' AFTER `seasonal_pricing`",
            'status' => "ADD COLUMN `status` enum('available','under_maintenance','retired') DEFAULT 'available' AFTER `allow_waitlist`"
        ];
        
        foreach ($resourceColumns as $column => $sql) {
            if (!$columnExists('facilities', $column)) {
                try {
                    $pdo->exec("ALTER TABLE `{$prefix}facilities` {$sql}");
                } catch (PDOException $e) {
                    error_log("Error adding facilities.{$column}: " . $e->getMessage());
                }
            }
        }

        // Resource Pricing table (for flexible pricing)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}resource_pricing` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `resource_id` int(11) NOT NULL,
            `rate_type` enum('hourly','half_day','full_day','weekly','monthly') NOT NULL,
            `price` decimal(15,2) NOT NULL,
            `peak_price` decimal(15,2) DEFAULT NULL,
            `member_price` decimal(15,2) DEFAULT NULL,
            `start_date` date DEFAULT NULL,
            `end_date` date DEFAULT NULL,
            `day_of_week` int(11) DEFAULT NULL COMMENT '0=Sunday, 6=Saturday, NULL=all days',
            `is_seasonal` tinyint(1) DEFAULT 0,
            `season_name` varchar(50) DEFAULT NULL,
            `min_duration` int(11) DEFAULT NULL,
            `max_duration` int(11) DEFAULT NULL,
            `quantity_discount` text DEFAULT NULL COMMENT 'JSON: quantity ranges with discount',
            `duration_discount` text DEFAULT NULL COMMENT 'JSON: duration ranges with discount',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `resource_id` (`resource_id`),
            KEY `rate_type` (`rate_type`),
            KEY `start_date` (`start_date`),
            KEY `end_date` (`end_date`),
            CONSTRAINT `{$prefix}resource_pricing_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `{$prefix}facilities` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Resource Availability table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}resource_availability` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `resource_id` int(11) NOT NULL,
            `day_of_week` int(11) NOT NULL COMMENT '0=Sunday, 6=Saturday',
            `is_available` tinyint(1) DEFAULT 1,
            `start_time` time DEFAULT NULL,
            `end_time` time DEFAULT NULL,
            `break_start` time DEFAULT NULL,
            `break_end` time DEFAULT NULL,
            `time_slots` text DEFAULT NULL COMMENT 'JSON array of available time slots',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `resource_day` (`resource_id`, `day_of_week`),
            CONSTRAINT `{$prefix}resource_availability_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `{$prefix}facilities` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Resource Blockouts table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}resource_blockouts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `resource_id` int(11) NOT NULL,
            `start_date` date NOT NULL,
            `end_date` date NOT NULL,
            `start_time` time DEFAULT NULL,
            `end_time` time DEFAULT NULL,
            `reason` varchar(255) DEFAULT NULL,
            `notes` text DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `resource_id` (`resource_id`),
            KEY `start_date` (`start_date`),
            KEY `end_date` (`end_date`),
            CONSTRAINT `{$prefix}resource_blockouts_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `{$prefix}facilities` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Enhanced Bookings table
        $bookingColumns = [
            'customer_id' => "ADD COLUMN `customer_id` int(11) DEFAULT NULL AFTER `customer_phone`",
            'booking_source' => "ADD COLUMN `booking_source` enum('online','phone','walk_in','email','other') DEFAULT 'online' AFTER `customer_id`",
            'status' => "ADD COLUMN `status` enum('inquiry','pending','confirmed','in_progress','completed','cancelled','no_show') DEFAULT 'pending' AFTER `booking_source`",
            'payment_plan' => "ADD COLUMN `payment_plan` enum('full','deposit','installment','pay_later') DEFAULT 'full' AFTER `payment_status`",
            'deposit_percentage' => "ADD COLUMN `deposit_percentage` decimal(5,2) DEFAULT NULL AFTER `payment_plan`",
            'deposit_amount' => "ADD COLUMN `deposit_amount` decimal(15,2) DEFAULT 0.00 AFTER `deposit_percentage`",
            'security_deposit_held' => "ADD COLUMN `security_deposit_held` decimal(15,2) DEFAULT 0.00 AFTER `security_deposit`",
            'subtotal' => "ADD COLUMN `subtotal` decimal(15,2) DEFAULT 0.00 AFTER `base_amount`",
            'discount_amount' => "ADD COLUMN `discount_amount` decimal(15,2) DEFAULT 0.00 AFTER `tax_amount`",
            'promo_code' => "ADD COLUMN `promo_code` varchar(50) DEFAULT NULL AFTER `discount_amount`",
            'additional_fees' => "ADD COLUMN `additional_fees` decimal(15,2) DEFAULT 0.00 AFTER `total_amount`",
            'cancellation_policy_id' => "ADD COLUMN `cancellation_policy_id` int(11) DEFAULT NULL AFTER `special_requests`",
            'cancelled_at' => "ADD COLUMN `cancelled_at` datetime DEFAULT NULL AFTER `completed_at`",
            'cancellation_reason' => "ADD COLUMN `cancellation_reason` text DEFAULT NULL AFTER `cancelled_at`",
            'is_recurring' => "ADD COLUMN `is_recurring` tinyint(1) DEFAULT 0 AFTER `cancellation_reason`",
            'recurring_pattern' => "ADD COLUMN `recurring_pattern` varchar(50) DEFAULT NULL AFTER `is_recurring`",
            'parent_booking_id' => "ADD COLUMN `parent_booking_id` int(11) DEFAULT NULL AFTER `recurring_pattern`",
            'assigned_staff' => "ADD COLUMN `assigned_staff` text DEFAULT NULL COMMENT 'JSON array of staff IDs' AFTER `parent_booking_id`",
            'setup_completed' => "ADD COLUMN `setup_completed` tinyint(1) DEFAULT 0 AFTER `assigned_staff`",
            'cleanup_completed' => "ADD COLUMN `cleanup_completed` tinyint(1) DEFAULT 0 AFTER `setup_completed`"
        ];
        
        foreach ($bookingColumns as $column => $sql) {
            if (!$columnExists('bookings', $column)) {
                try {
                    $pdo->exec("ALTER TABLE `{$prefix}bookings` {$sql}");
                } catch (PDOException $e) {
                    error_log("Error adding bookings.{$column}: " . $e->getMessage());
                }
            }
        }
        
        // Add indexes
        try {
            $indexExists = function($table, $index) use ($pdo, $prefix) {
                try {
                    $stmt = $pdo->query("SHOW INDEXES FROM `{$prefix}{$table}` WHERE Key_name = '{$index}'");
                    return $stmt->rowCount() > 0;
                } catch (PDOException $e) {
                    return false;
                }
            };
            
            if (!$indexExists('bookings', 'customer_id')) {
                $pdo->exec("ALTER TABLE `{$prefix}bookings` ADD INDEX `customer_id` (`customer_id`)");
            }
            if (!$indexExists('bookings', 'booking_source')) {
                $pdo->exec("ALTER TABLE `{$prefix}bookings` ADD INDEX `booking_source` (`booking_source`)");
            }
            if (!$indexExists('bookings', 'status')) {
                $pdo->exec("ALTER TABLE `{$prefix}bookings` ADD INDEX `status` (`status`)");
            }
            if (!$indexExists('bookings', 'parent_booking_id')) {
                $pdo->exec("ALTER TABLE `{$prefix}bookings` ADD INDEX `parent_booking_id` (`parent_booking_id`)");
            }
        } catch (PDOException $e) {
            error_log("Error adding booking indexes: " . $e->getMessage());
        }

        // Booking Resources table (many-to-many relationship)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}booking_resources` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `booking_id` int(11) NOT NULL,
            `resource_id` int(11) NOT NULL,
            `start_time` datetime NOT NULL,
            `end_time` datetime NOT NULL,
            `quantity` int(11) DEFAULT 1,
            `rate` decimal(15,2) NOT NULL,
            `rate_type` varchar(20) DEFAULT 'hourly',
            `amount` decimal(15,2) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `booking_id` (`booking_id`),
            KEY `resource_id` (`resource_id`),
            KEY `start_time` (`start_time`),
            CONSTRAINT `{$prefix}booking_resources_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `{$prefix}bookings` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}booking_resources_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `{$prefix}facilities` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Addons table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}addons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `addon_type` enum('equipment','service','catering','decoration','other') NOT NULL,
            `price` decimal(15,2) NOT NULL,
            `resource_id` int(11) DEFAULT NULL COMMENT 'If specific to a resource',
            `is_active` tinyint(1) DEFAULT 1,
            `display_order` int(11) DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `resource_id` (`resource_id`),
            KEY `is_active` (`is_active`),
            CONSTRAINT `{$prefix}addons_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `{$prefix}facilities` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Booking Addons table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}booking_addons` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `booking_id` int(11) NOT NULL,
            `addon_id` int(11) NOT NULL,
            `quantity` int(11) DEFAULT 1,
            `unit_price` decimal(15,2) NOT NULL,
            `total_price` decimal(15,2) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `booking_id` (`booking_id`),
            KEY `addon_id` (`addon_id`),
            CONSTRAINT `{$prefix}booking_addons_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `{$prefix}bookings` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}booking_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `{$prefix}addons` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Promo Codes table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}promo_codes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(50) NOT NULL,
            `description` text DEFAULT NULL,
            `discount_type` enum('percentage','fixed') NOT NULL,
            `discount_value` decimal(10,2) NOT NULL,
            `minimum_amount` decimal(15,2) DEFAULT NULL,
            `maximum_discount` decimal(15,2) DEFAULT NULL,
            `valid_from` date NOT NULL,
            `valid_to` date NOT NULL,
            `usage_limit` int(11) DEFAULT NULL,
            `used_count` int(11) DEFAULT 0,
            `applicable_to` enum('all','resource','category','addon') DEFAULT 'all',
            `applicable_ids` text DEFAULT NULL COMMENT 'JSON array of resource/addon IDs',
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`),
            KEY `valid_from` (`valid_from`),
            KEY `valid_to` (`valid_to`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Cancellation Policies table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}cancellation_policies` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `rules` text NOT NULL COMMENT 'JSON: cancellation rules with days and refund percentage',
            `is_default` tinyint(1) DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `is_default` (`is_default`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Booking Modifications table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}booking_modifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `booking_id` int(11) NOT NULL,
            `changed_by` int(11) DEFAULT NULL COMMENT 'user_id or NULL for customer',
            `change_type` enum('reschedule','resource_change','addon_change','status_change','amount_change','other') NOT NULL,
            `old_value` text DEFAULT NULL,
            `new_value` text DEFAULT NULL,
            `reason` text DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `booking_id` (`booking_id`),
            KEY `changed_by` (`changed_by`),
            CONSTRAINT `{$prefix}booking_modifications_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `{$prefix}bookings` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Booking Reviews table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}booking_reviews` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `booking_id` int(11) NOT NULL,
            `customer_id` int(11) DEFAULT NULL,
            `customer_email` varchar(255) DEFAULT NULL,
            `rating` int(11) NOT NULL COMMENT '1-5 stars',
            `comment` text DEFAULT NULL,
            `review_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `is_approved` tinyint(1) DEFAULT 0,
            `is_visible` tinyint(1) DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `booking_id` (`booking_id`),
            KEY `customer_id` (`customer_id`),
            KEY `rating` (`rating`),
            CONSTRAINT `{$prefix}booking_reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `{$prefix}bookings` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Waitlist table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}booking_waitlist` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `resource_id` int(11) NOT NULL,
            `customer_name` varchar(255) NOT NULL,
            `customer_email` varchar(255) NOT NULL,
            `customer_phone` varchar(50) DEFAULT NULL,
            `desired_date` date NOT NULL,
            `desired_start_time` time DEFAULT NULL,
            `desired_end_time` time DEFAULT NULL,
            `status` enum('active','notified','converted','cancelled') DEFAULT 'active',
            `priority` int(11) DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `resource_id` (`resource_id`),
            KEY `desired_date` (`desired_date`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}booking_waitlist_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `{$prefix}facilities` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Payment Schedule table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}payment_schedule` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `booking_id` int(11) NOT NULL,
            `payment_number` int(11) NOT NULL COMMENT '1, 2, 3, etc.',
            `due_date` date NOT NULL,
            `amount` decimal(15,2) NOT NULL,
            `paid_amount` decimal(15,2) DEFAULT 0.00,
            `status` enum('pending','partial','paid','overdue') DEFAULT 'pending',
            `paid_at` datetime DEFAULT NULL,
            `reminder_sent` tinyint(1) DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `booking_id` (`booking_id`),
            KEY `due_date` (`due_date`),
            KEY `status` (`status`),
            CONSTRAINT `{$prefix}payment_schedule_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `{$prefix}bookings` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insert default cancellation policy
        $defaultPolicy = json_encode([
            ['days_before' => 7, 'refund_percentage' => 100],
            ['days_before' => 3, 'refund_percentage' => 50],
            ['days_before' => 1, 'refund_percentage' => 0]
        ]);
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}cancellation_policies` 
            (name, description, rules, is_default) 
            VALUES ('Standard Policy', 'Full refund if cancelled 7+ days before, 50% if 3-7 days, no refund if less than 3 days', ?, 1)");
        $stmt->execute([$defaultPolicy]);

        echo "Enhanced booking system tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Booking enhanced migration error: " . $e->getMessage());
        throw $e;
    }
}
