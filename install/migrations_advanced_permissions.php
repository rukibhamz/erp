<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Advanced Permissions Migration
 * Creates tables for field-level and record-level permissions
 */

function runAdvancedPermissionsMigrations($pdo, $prefix = 'erp_') {
    try {
        // Field-level permissions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}field_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `permission_id` int(11) DEFAULT NULL COMMENT 'Parent module permission',
            `user_id` int(11) DEFAULT NULL COMMENT 'Specific user (optional)',
            `role` varchar(50) DEFAULT NULL COMMENT 'Role (optional)',
            `module` varchar(50) NOT NULL,
            `table_name` varchar(100) NOT NULL,
            `field_name` varchar(100) NOT NULL,
            `permission_type` enum('read','write','hidden') NOT NULL DEFAULT 'read',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `permission_id` (`permission_id`),
            KEY `user_id` (`user_id`),
            KEY `role` (`role`),
            KEY `module` (`module`),
            KEY `table_field` (`table_name`, `field_name`),
            UNIQUE KEY `unique_field_permission` (`user_id`, `role`, `module`, `table_name`, `field_name`),
            CONSTRAINT `{$prefix}field_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `{$prefix}permissions` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}field_permissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Record-level permissions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}record_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `module` varchar(50) NOT NULL,
            `table_name` varchar(100) NOT NULL,
            `record_id` int(11) NOT NULL,
            `permission_type` enum('read','write','delete','own') NOT NULL DEFAULT 'read',
            `granted_by` int(11) DEFAULT NULL COMMENT 'User who granted this permission',
            `granted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expires_at` datetime DEFAULT NULL COMMENT 'For temporary permissions',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `module` (`module`),
            KEY `table_record` (`table_name`, `record_id`),
            KEY `expires_at` (`expires_at`),
            UNIQUE KEY `unique_record_permission` (`user_id`, `table_name`, `record_id`, `permission_type`),
            CONSTRAINT `{$prefix}record_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}record_permissions_ibfk_2` FOREIGN KEY (`granted_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Department-based permissions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}department_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `department_id` int(11) DEFAULT NULL COMMENT 'Department ID (if using departments)',
            `location_id` int(11) DEFAULT NULL COMMENT 'Location ID (if using locations)',
            `module` varchar(50) NOT NULL,
            `permission_type` enum('read','write','delete','full') NOT NULL DEFAULT 'read',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `department_id` (`department_id`),
            KEY `location_id` (`location_id`),
            KEY `module` (`module`),
            UNIQUE KEY `unique_department_permission` (`user_id`, `department_id`, `location_id`, `module`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Roles table (for custom roles)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `role_name` varchar(100) NOT NULL,
            `role_code` varchar(50) NOT NULL UNIQUE,
            `description` text DEFAULT NULL,
            `is_system` tinyint(1) DEFAULT 0 COMMENT 'System roles cannot be deleted',
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `role_code` (`role_code`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Role permissions table (assign permissions to roles)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}role_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `role_id` int(11) NOT NULL,
            `permission_id` int(11) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
            KEY `role_id` (`role_id`),
            KEY `permission_id` (`permission_id`),
            CONSTRAINT `{$prefix}role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `{$prefix}roles` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `{$prefix}permissions` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // User roles table (assign multiple roles to users)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}user_roles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `role_id` int(11) NOT NULL,
            `is_primary` tinyint(1) DEFAULT 0 COMMENT 'Primary role',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
            KEY `user_id` (`user_id`),
            KEY `role_id` (`role_id`),
            CONSTRAINT `{$prefix}user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `{$prefix}roles` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insert default system roles
        $defaultRoles = [
            ['role_name' => 'Super Admin', 'role_code' => 'super_admin', 'description' => 'Full system access', 'is_system' => 1],
            ['role_name' => 'Admin', 'role_code' => 'admin', 'description' => 'Administrative access', 'is_system' => 1],
            ['role_name' => 'Manager', 'role_code' => 'manager', 'description' => 'Management level access', 'is_system' => 1],
            ['role_name' => 'Staff', 'role_code' => 'staff', 'description' => 'Staff level access', 'is_system' => 1],
            ['role_name' => 'User', 'role_code' => 'user', 'description' => 'Standard user access', 'is_system' => 1]
        ];

        foreach ($defaultRoles as $role) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}roles` 
                    (role_name, role_code, description, is_system, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())");
                $stmt->execute([
                    $role['role_name'],
                    $role['role_code'],
                    $role['description'],
                    $role['is_system']
                ]);
            } catch (PDOException $e) {
                error_log("Failed to insert role {$role['role_code']}: " . $e->getMessage());
            }
        }

        echo "Advanced permissions tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Advanced permissions migration error: " . $e->getMessage());
        throw $e;
    }
}


