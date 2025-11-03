<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification System Migration
 * Creates tables for email and in-app notifications
 */

function runNotificationMigrations($pdo, $prefix = 'erp_') {
    try {
        // Notifications table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}notifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL COMMENT 'For admin users',
            `customer_email` varchar(255) DEFAULT NULL COMMENT 'For customer portal users',
            `type` enum('booking_confirmation','booking_reminder','payment_received','payment_due','booking_cancelled','booking_modified','system','other') NOT NULL DEFAULT 'other',
            `title` varchar(255) NOT NULL,
            `message` text NOT NULL,
            `related_module` varchar(50) DEFAULT NULL,
            `related_id` int(11) DEFAULT NULL,
            `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
            `is_read` tinyint(1) DEFAULT 0,
            `read_at` datetime DEFAULT NULL,
            `email_sent` tinyint(1) DEFAULT 0,
            `email_sent_at` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `customer_email` (`customer_email`),
            KEY `type` (`type`),
            KEY `is_read` (`is_read`),
            KEY `created_at` (`created_at`),
            KEY `related` (`related_module`, `related_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Notification Preferences table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}notification_preferences` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `customer_email` varchar(255) DEFAULT NULL,
            `preference_type` enum('email','sms','push','in_app') NOT NULL,
            `notification_type` varchar(50) DEFAULT NULL COMMENT 'Specific notification type or NULL for all',
            `enabled` tinyint(1) DEFAULT 1,
            `frequency` enum('instant','daily','weekly','never') DEFAULT 'instant',
            `quiet_hours_start` time DEFAULT NULL,
            `quiet_hours_end` time DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `customer_email` (`customer_email`),
            KEY `preference_type` (`preference_type`),
            KEY `notification_type` (`notification_type`),
            UNIQUE KEY `unique_preference` (`user_id`, `customer_email`, `preference_type`, `notification_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Email queue table (for batch email sending)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}email_queue` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `to_email` varchar(255) NOT NULL,
            `to_name` varchar(255) DEFAULT NULL,
            `subject` varchar(255) NOT NULL,
            `body` text NOT NULL,
            `body_html` text DEFAULT NULL,
            `from_email` varchar(255) DEFAULT NULL,
            `from_name` varchar(255) DEFAULT NULL,
            `priority` int(11) DEFAULT 0,
            `status` enum('pending','sent','failed') DEFAULT 'pending',
            `sent_at` datetime DEFAULT NULL,
            `attempts` int(11) DEFAULT 0,
            `last_attempt_at` datetime DEFAULT NULL,
            `error_message` text DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `status` (`status`),
            KEY `priority` (`priority`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Notification templates
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}notification_templates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `template_code` varchar(100) NOT NULL,
            `template_name` varchar(255) NOT NULL,
            `type` enum('email','sms','push','in_app') NOT NULL DEFAULT 'email',
            `subject` varchar(255) DEFAULT NULL,
            `body` text NOT NULL,
            `variables` text DEFAULT NULL COMMENT 'JSON: available template variables',
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `template_code` (`template_code`),
            KEY `type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insert default notification templates
        $defaultTemplates = [
            [
                'template_code' => 'booking_confirmation',
                'template_name' => 'Booking Confirmation',
                'type' => 'email',
                'subject' => 'Booking Confirmed - {booking_number}',
                'body' => "Dear {customer_name},\n\nYour booking has been confirmed!\n\nBooking Details:\nBooking Number: {booking_number}\nResource: {facility_name}\nDate: {booking_date}\nTime: {start_time} - {end_time}\nTotal Amount: {total_amount}\n\nThank you for your booking!",
                'variables' => json_encode(['booking_number', 'customer_name', 'facility_name', 'booking_date', 'start_time', 'end_time', 'total_amount'])
            ],
            [
                'template_code' => 'booking_reminder',
                'template_name' => 'Booking Reminder',
                'type' => 'email',
                'subject' => 'Reminder: Your booking tomorrow - {booking_number}',
                'body' => "Dear {customer_name},\n\nThis is a reminder that you have a booking tomorrow:\n\nBooking Number: {booking_number}\nResource: {facility_name}\nDate: {booking_date}\nTime: {start_time} - {end_time}\n\nWe look forward to seeing you!",
                'variables' => json_encode(['booking_number', 'customer_name', 'facility_name', 'booking_date', 'start_time', 'end_time'])
            ],
            [
                'template_code' => 'payment_received',
                'template_name' => 'Payment Received',
                'type' => 'email',
                'subject' => 'Payment Received - {booking_number}',
                'body' => "Dear {customer_name},\n\nWe have received your payment of {payment_amount} for booking {booking_number}.\n\nThank you!",
                'variables' => json_encode(['customer_name', 'booking_number', 'payment_amount'])
            ],
            [
                'template_code' => 'payment_due',
                'template_name' => 'Payment Due Reminder',
                'type' => 'email',
                'subject' => 'Payment Due - {booking_number}',
                'body' => "Dear {customer_name},\n\nYou have an outstanding balance of {balance_amount} for booking {booking_number}.\n\nPlease make payment at your earliest convenience.\n\nThank you!",
                'variables' => json_encode(['customer_name', 'booking_number', 'balance_amount'])
            ]
        ];

        foreach ($defaultTemplates as $template) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}notification_templates` 
                    (template_code, template_name, type, subject, body, variables, is_active, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->execute([
                    $template['template_code'],
                    $template['template_name'],
                    $template['type'],
                    $template['subject'],
                    $template['body'],
                    $template['variables']
                ]);
            } catch (PDOException $e) {
                error_log("Failed to insert notification template {$template['template_code']}: " . $e->getMessage());
            }
        }

        echo "Notification system tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Notification migration error: " . $e->getMessage());
        throw $e;
    }
}

