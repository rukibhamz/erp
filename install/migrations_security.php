<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function runSecurityMigrations($pdo, $prefix = 'erp_') {
    try {
        // Rate Limiting Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}rate_limits` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `identifier` VARCHAR(255) NOT NULL COMMENT 'Username, email, or IP',
            `ip_address` VARCHAR(45) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `identifier` (`identifier`),
            KEY `ip_address` (`ip_address`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // IP Restrictions Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}ip_restrictions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `ip_address` VARCHAR(45) NOT NULL,
            `type` ENUM('whitelist', 'blacklist') NOT NULL,
            `description` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ip_type` (`ip_address`, `type`),
            KEY `type` (`type`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Security Log Table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}security_log` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) DEFAULT NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `action` VARCHAR(100) NOT NULL COMMENT 'login_failed, login_success, permission_denied, etc',
            `details` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `ip_address` (`ip_address`),
            KEY `action` (`action`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        return true;
    } catch (PDOException $e) {
        error_log("Security migrations error: " . $e->getMessage());
        throw $e;
    }
}



