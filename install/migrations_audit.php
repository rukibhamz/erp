<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Enhanced Audit Trail Migration
 * Creates table for detailed change tracking with before/after values
 */

function runAuditMigrations($pdo, $prefix = 'erp_') {
    try {
        // Enhanced Audit Trail table with before/after values
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}audit_trail` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL COMMENT 'User who performed the action',
            `action` enum('create','update','delete','read','login','logout','permission_change','export','import','backup','restore') NOT NULL,
            `module` varchar(50) DEFAULT NULL COMMENT 'Module/table name',
            `record_id` int(11) DEFAULT NULL COMMENT 'ID of the affected record',
            `table_name` varchar(100) DEFAULT NULL COMMENT 'Database table name',
            `field_name` varchar(100) DEFAULT NULL COMMENT 'Specific field changed (for updates)',
            `old_value` text DEFAULT NULL COMMENT 'Previous value (before change)',
            `new_value` text DEFAULT NULL COMMENT 'New value (after change)',
            `changes_json` text DEFAULT NULL COMMENT 'JSON object of all changes for this action',
            `description` text DEFAULT NULL COMMENT 'Human-readable description',
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `related_module` varchar(50) DEFAULT NULL COMMENT 'Related module for linked actions',
            `related_id` int(11) DEFAULT NULL COMMENT 'Related record ID',
            `metadata` text DEFAULT NULL COMMENT 'Additional JSON metadata',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `action` (`action`),
            KEY `module` (`module`),
            KEY `table_name` (`table_name`),
            KEY `record_id` (`record_id`),
            KEY `created_at` (`created_at`),
            KEY `module_record` (`module`, `record_id`),
            KEY `user_action` (`user_id`, `action`),
            CONSTRAINT `{$prefix}audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Audit Trail Summary View (for reporting)
        $pdo->exec("CREATE OR REPLACE VIEW `{$prefix}audit_trail_summary` AS
            SELECT 
                DATE(created_at) as audit_date,
                user_id,
                action,
                module,
                COUNT(*) as action_count,
                COUNT(DISTINCT record_id) as records_affected
            FROM `{$prefix}audit_trail`
            GROUP BY DATE(created_at), user_id, action, module");

        echo "Enhanced audit trail tables created successfully.\n";
        return true;
    } catch (PDOException $e) {
        error_log("Audit migration error: " . $e->getMessage());
        throw $e;
    }
}


