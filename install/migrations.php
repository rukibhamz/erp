<?php
/**
 * Database Migration Script
 * Creates all required database tables
 */

function runMigrations($pdo, $prefix = 'erp_') {
    $migrations = [
        'users' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `username` varchar(50) NOT NULL,
                `email` varchar(100) NOT NULL,
                `password` varchar(255) NOT NULL,
                `first_name` varchar(100) DEFAULT NULL,
                `last_name` varchar(100) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `avatar` varchar(255) DEFAULT NULL,
                `role` enum('super_admin','admin','manager','staff','user') NOT NULL DEFAULT 'user',
                `status` enum('active','inactive','suspended','locked') NOT NULL DEFAULT 'active',
                `failed_login_attempts` int(11) DEFAULT 0,
                `locked_until` datetime DEFAULT NULL,
                `remember_token` varchar(100) DEFAULT NULL,
                `password_reset_token` varchar(100) DEFAULT NULL,
                `password_reset_expires` datetime DEFAULT NULL,
                `two_factor_secret` varchar(255) DEFAULT NULL,
                `two_factor_enabled` tinyint(1) DEFAULT 0,
                `last_login` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`),
                UNIQUE KEY `email` (`email`),
                KEY `status` (`status`),
                KEY `role` (`role`),
                KEY `password_reset_token` (`password_reset_token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'permissions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module` varchar(50) NOT NULL,
                `permission` varchar(50) NOT NULL,
                `description` varchar(255) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `module_permission` (`module`, `permission`),
                KEY `module` (`module`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'user_permissions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}user_permissions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `permission_id` int(11) NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_permission` (`user_id`, `permission_id`),
                KEY `user_id` (`user_id`),
                KEY `permission_id` (`permission_id`),
                CONSTRAINT `{$prefix}user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{$prefix}user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `{$prefix}permissions` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'sessions' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}sessions` (
                `id` varchar(128) NOT NULL,
                `user_id` int(11) DEFAULT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `last_activity` int(11) NOT NULL,
                `data` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `last_activity` (`last_activity`),
                CONSTRAINT `{$prefix}sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'companies' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}companies` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `address` text DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `zip_code` varchar(20) DEFAULT NULL,
                `country` varchar(100) DEFAULT NULL,
                `phone` varchar(50) DEFAULT NULL,
                `email` varchar(100) DEFAULT NULL,
                `website` varchar(255) DEFAULT NULL,
                `tax_id` varchar(100) DEFAULT NULL,
                `currency` varchar(10) DEFAULT 'USD',
                `logo` varchar(255) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'modules_settings' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}modules_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `module_name` varchar(100) NOT NULL,
                `settings_json` text DEFAULT NULL,
                `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `module_name` (`module_name`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
        
        'activity_log' => "
            CREATE TABLE IF NOT EXISTS `{$prefix}activity_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) DEFAULT NULL,
                `action` varchar(100) NOT NULL,
                `module` varchar(50) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `module` (`module`),
                KEY `created_at` (`created_at`),
                CONSTRAINT `{$prefix}activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ",
    ];
    
    foreach ($migrations as $table => $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Failed to create table {$table}: " . $e->getMessage());
        }
    }
    
    // Insert default permissions
    insertDefaultPermissions($pdo, $prefix);
}

function insertDefaultPermissions($pdo, $prefix) {
    $modules = ['users', 'companies', 'settings', 'reports', 'modules'];
    $actions = ['create', 'read', 'update', 'delete'];
    
    foreach ($modules as $module) {
        foreach ($actions as $action) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}permissions` (module, permission, description, created_at) VALUES (?, ?, ?, NOW())");
                $description = ucfirst($action) . ' ' . ucfirst($module);
                $stmt->execute([$module, $action, $description]);
            } catch (PDOException $e) {
                // Ignore duplicate errors
            }
        }
    }
}

