<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer Portal Migration
 * Creates tables for customer portal authentication and management
 */

function runCustomerPortalMigrations($pdo, $prefix = 'erp_') {
    try {
        // Customer Portal Users table (separate from admin users)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}customer_portal_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(100) NOT NULL,
            `password` varchar(255) NOT NULL,
            `first_name` varchar(100) DEFAULT NULL,
            `last_name` varchar(100) DEFAULT NULL,
            `phone` varchar(50) DEFAULT NULL,
            `company_name` varchar(255) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `city` varchar(100) DEFAULT NULL,
            `state` varchar(100) DEFAULT NULL,
            `zip_code` varchar(20) DEFAULT NULL,
            `country` varchar(100) DEFAULT NULL,
            `customer_id` int(11) DEFAULT NULL COMMENT 'Link to customers table if exists',
            `status` enum('active','inactive','suspended') DEFAULT 'active',
            `email_verified` tinyint(1) DEFAULT 0,
            `email_verification_token` varchar(100) DEFAULT NULL,
            `password_reset_token` varchar(100) DEFAULT NULL,
            `password_reset_expires` datetime DEFAULT NULL,
            `remember_token` varchar(100) DEFAULT NULL,
            `last_login` datetime DEFAULT NULL,
            `failed_login_attempts` int(11) DEFAULT 0,
            `locked_until` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`),
            KEY `customer_id` (`customer_id`),
            KEY `status` (`status`),
            KEY `email_verification_token` (`email_verification_token`),
            KEY `password_reset_token` (`password_reset_token`),
            CONSTRAINT `{$prefix}customer_portal_users_ibfk_1` FOREIGN KEY (`customer_id`) 
                REFERENCES `{$prefix}customers` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Add customer_email index to bookings if not exists
        try {
            $pdo->exec("ALTER TABLE `{$prefix}bookings` ADD INDEX IF NOT EXISTS `customer_email` (`customer_email`)");
        } catch (PDOException $e) {
            // Index may already exist
        }
        
        // Add customer_portal_user_id column to bookings table for linking
        try {
            $pdo->exec("ALTER TABLE `{$prefix}bookings` ADD COLUMN IF NOT EXISTS `customer_portal_user_id` int(11) DEFAULT NULL AFTER `customer_email`");
            $pdo->exec("ALTER TABLE `{$prefix}bookings` ADD INDEX IF NOT EXISTS `customer_portal_user_id` (`customer_portal_user_id`)");
        } catch (PDOException $e) {
            // Column/index may already exist
        }

        echo "Customer portal tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Customer portal migration error: " . $e->getMessage());
        throw $e;
    }
}

