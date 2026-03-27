<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_004_standardize_accounts_table {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function up() {
        $prefix = $this->db->getPrefix();
        
        // Use raw SQL to check and add columns (compatible with our Database class)
        $this->ensureColumn($prefix . 'accounts', 'parent_account_id', "INT NULL AFTER `id` (FOREIGN KEY REFERENCES `{$prefix}accounts`(`id`) ON DELETE SET NULL)");
        $this->ensureColumn($prefix . 'accounts', 'account_category', "VARCHAR(50) NULL AFTER `account_type`");
        $this->ensureColumn($prefix . 'accounts', 'is_system_account', "TINYINT(1) DEFAULT 0 AFTER `account_category`");
        $this->ensureColumn($prefix . 'accounts', 'opening_balance', "DECIMAL(15,2) DEFAULT 0.00 AFTER `balance`");
        $this->ensureColumn($prefix . 'accounts', 'opening_balance_date', "DATE NULL AFTER `opening_balance`");
        $this->ensureColumn($prefix . 'accounts', 'description', "TEXT NULL AFTER `account_name`");
        
        // Update account_code to be unique
        try {
            $this->db->query("ALTER TABLE `{$prefix}accounts` MODIFY COLUMN `account_code` VARCHAR(50) UNIQUE");
        } catch (Exception $e) {
            // Ignore if already unique
        }
        
        error_log('Migration 004: Standardized accounts table structure');
    }
    
    private function ensureColumn($table, $column, $definition) {
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if ($stmt->rowCount() == 0) {
            $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
    
    public function down() {
        $prefix = $this->db->getPrefix();
        // Rollback logic is usually not needed for auto-migrations but here is a simple one
        $columns = ['parent_account_id', 'account_category', 'is_system_account', 'opening_balance', 'opening_balance_date', 'description'];
        foreach ($columns as $column) {
            try {
                $this->db->query("ALTER TABLE `{$prefix}accounts` DROP COLUMN `{$column}`");
            } catch (Exception $e) {}
        }
    }
}
