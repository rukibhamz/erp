<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_004_standardize_accounts_table extends CI_Migration {
    
    public function up() {
        $prefix = $this->db->dbprefix;
        
        // Add parent_account_id for account hierarchy
        if (!$this->db->field_exists('parent_account_id', $prefix . 'accounts')) {
            $this->dbforge->add_column($prefix . 'accounts', [
                'parent_account_id' => [
                    'type' => 'INT',
                    'null' => TRUE,
                    'after' => 'id'
                ]
            ]);
            
            // Add foreign key
            $this->db->query("ALTER TABLE `{$prefix}accounts` 
                ADD CONSTRAINT `fk_parent_account` 
                FOREIGN KEY (`parent_account_id`) 
                REFERENCES `{$prefix}accounts`(`id`) 
                ON DELETE SET NULL");
        }
        
        // Add account_category for detailed classification
        if (!$this->db->field_exists('account_category', $prefix . 'accounts')) {
            $this->dbforge->add_column($prefix . 'accounts', [
                'account_category' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => TRUE,
                    'after' => 'account_type'
                ]
            ]);
        }
        
        // Add is_system_account flag
        if (!$this->db->field_exists('is_system_account', $prefix . 'accounts')) {
            $this->dbforge->add_column($prefix . 'accounts', [
                'is_system_account' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'account_category'
                ]
            ]);
        }
        
        // Add opening_balance
        if (!$this->db->field_exists('opening_balance', $prefix . 'accounts')) {
            $this->dbforge->add_column($prefix . 'accounts', [
                'opening_balance' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0.00,
                    'after' => 'balance'
                ]
            ]);
        }
        
        // Add opening_balance_date
        if (!$this->db->field_exists('opening_balance_date', $prefix . 'accounts')) {
            $this->dbforge->add_column($prefix . 'accounts', [
                'opening_balance_date' => [
                    'type' => 'DATE',
                    'null' => TRUE,
                    'after' => 'opening_balance'
                ]
            ]);
        }
        
        // Add description field if not exists
        if (!$this->db->field_exists('description', $prefix . 'accounts')) {
            $this->dbforge->add_column($prefix . 'accounts', [
                'description' => [
                    'type' => 'TEXT',
                    'null' => TRUE,
                    'after' => 'account_name'
                ]
            ]);
        }
        
        // Update account_code to be unique
        $this->db->query("ALTER TABLE `{$prefix}accounts` 
            MODIFY COLUMN `account_code` VARCHAR(50) UNIQUE");
        
        log_message('info', 'Migration 004: Standardized accounts table structure');
    }
    
    public function down() {
        $prefix = $this->db->dbprefix;
        
        // Remove foreign key first
        $this->db->query("ALTER TABLE `{$prefix}accounts` DROP FOREIGN KEY `fk_parent_account`");
        
        // Remove columns
        if ($this->db->field_exists('parent_account_id', $prefix . 'accounts')) {
            $this->dbforge->drop_column($prefix . 'accounts', 'parent_account_id');
        }
        
        if ($this->db->field_exists('account_category', $prefix . 'accounts')) {
            $this->dbforge->drop_column($prefix . 'accounts', 'account_category');
        }
        
        if ($this->db->field_exists('is_system_account', $prefix . 'accounts')) {
            $this->dbforge->drop_column($prefix . 'accounts', 'is_system_account');
        }
        
        if ($this->db->field_exists('opening_balance', $prefix . 'accounts')) {
            $this->dbforge->drop_column($prefix . 'accounts', 'opening_balance');
        }
        
        if ($this->db->field_exists('opening_balance_date', $prefix . 'accounts')) {
            $this->dbforge->drop_column($prefix . 'accounts', 'opening_balance_date');
        }
        
        if ($this->db->field_exists('description', $prefix . 'accounts')) {
            $this->dbforge->drop_column($prefix . 'accounts', 'description');
        }
        
        log_message('info', 'Migration 004: Rolled back accounts table standardization');
    }
}
