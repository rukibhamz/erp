<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Report Builder Migration
 * Creates tables for custom report definitions
 */

function runReportBuilderMigrations($pdo, $prefix = 'erp_') {
    try {
        // Custom Reports table
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}custom_reports` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `report_name` varchar(255) NOT NULL,
            `report_code` varchar(100) NOT NULL UNIQUE,
            `description` text DEFAULT NULL,
            `module` varchar(50) NOT NULL,
            `report_type` enum('table','chart','summary','detailed') NOT NULL DEFAULT 'table',
            `data_source` varchar(100) NOT NULL COMMENT 'Table or model name',
            `fields_json` text NOT NULL COMMENT 'JSON array of selected fields',
            `filters_json` text DEFAULT NULL COMMENT 'JSON array of filter conditions',
            `grouping_json` text DEFAULT NULL COMMENT 'JSON array of grouping fields',
            `sorting_json` text DEFAULT NULL COMMENT 'JSON array of sort fields',
            `calculated_fields_json` text DEFAULT NULL COMMENT 'JSON array of calculated fields',
            `chart_config_json` text DEFAULT NULL COMMENT 'JSON chart configuration',
            `format_options_json` text DEFAULT NULL COMMENT 'JSON format options',
            `is_public` tinyint(1) DEFAULT 0 COMMENT 'Public report available to all users',
            `is_scheduled` tinyint(1) DEFAULT 0 COMMENT 'Scheduled report',
            `schedule_frequency` enum('daily','weekly','monthly') DEFAULT NULL,
            `schedule_time` time DEFAULT NULL,
            `schedule_emails` text DEFAULT NULL COMMENT 'Comma-separated email list',
            `created_by` int(11) NOT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `report_code` (`report_code`),
            KEY `module` (`module`),
            KEY `created_by` (`created_by`),
            KEY `is_public` (`is_public`),
            CONSTRAINT `{$prefix}custom_reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}custom_reports_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Report Categories
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}report_categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `category_name` varchar(100) NOT NULL,
            `category_code` varchar(50) NOT NULL UNIQUE,
            `description` text DEFAULT NULL,
            `icon` varchar(50) DEFAULT NULL,
            `sort_order` int(11) DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `category_code` (`category_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Report Access (who can view/run reports)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}report_access` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `report_id` int(11) NOT NULL,
            `user_id` int(11) DEFAULT NULL COMMENT 'Specific user',
            `role` varchar(50) DEFAULT NULL COMMENT 'Role-based access',
            `permission_type` enum('view','run','edit','delete') NOT NULL DEFAULT 'view',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `report_id` (`report_id`),
            KEY `user_id` (`user_id`),
            KEY `role` (`role`),
            UNIQUE KEY `unique_report_access` (`report_id`, `user_id`, `role`, `permission_type`),
            CONSTRAINT `{$prefix}report_access_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `{$prefix}custom_reports` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}report_access_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Report Execution Log
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}report_executions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `report_id` int(11) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `execution_time` decimal(10,3) DEFAULT NULL COMMENT 'Execution time in seconds',
            `rows_returned` int(11) DEFAULT NULL,
            `export_format` varchar(20) DEFAULT NULL COMMENT 'csv, pdf, excel, json',
            `status` enum('success','error','timeout') DEFAULT 'success',
            `error_message` text DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `report_id` (`report_id`),
            KEY `user_id` (`user_id`),
            KEY `created_at` (`created_at`),
            CONSTRAINT `{$prefix}report_executions_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `{$prefix}custom_reports` (`id`) ON DELETE CASCADE,
            CONSTRAINT `{$prefix}report_executions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Insert default report categories
        $defaultCategories = [
            ['category_name' => 'Financial Reports', 'category_code' => 'financial', 'icon' => 'bi-cash-stack', 'sort_order' => 1],
            ['category_name' => 'Sales Reports', 'category_code' => 'sales', 'icon' => 'bi-graph-up', 'sort_order' => 2],
            ['category_name' => 'Inventory Reports', 'category_code' => 'inventory', 'icon' => 'bi-box-seam', 'sort_order' => 3],
            ['category_name' => 'Customer Reports', 'category_code' => 'customers', 'icon' => 'bi-people', 'sort_order' => 4],
            ['category_name' => 'Tax Reports', 'category_code' => 'tax', 'icon' => 'bi-receipt', 'sort_order' => 5],
            ['category_name' => 'Operations Reports', 'category_code' => 'operations', 'icon' => 'bi-gear', 'sort_order' => 6],
            ['category_name' => 'Custom Reports', 'category_code' => 'custom', 'icon' => 'bi-file-earmark-text', 'sort_order' => 99]
        ];

        foreach ($defaultCategories as $category) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}report_categories` 
                    (category_name, category_code, icon, sort_order, created_at) 
                    VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $category['category_name'],
                    $category['category_code'],
                    $category['icon'],
                    $category['sort_order']
                ]);
            } catch (PDOException $e) {
                error_log("Failed to insert report category {$category['category_code']}: " . $e->getMessage());
            }
        }

        echo "Report builder tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Report builder migration error: " . $e->getMessage());
        throw $e;
    }
}


