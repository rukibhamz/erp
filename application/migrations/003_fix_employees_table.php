<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_003_fix_employees_table {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function up() {
        $prefix = $this->db->getPrefix();
        
        // Check and add hire_date column
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$prefix}employees` LIKE 'hire_date'");
        if ($stmt->rowCount() == 0) {
            $this->db->query("ALTER TABLE `{$prefix}employees` 
                ADD COLUMN `hire_date` DATE NULL AFTER `employment_type`");
        }
        
        // Check and add salary_structure column
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$prefix}employees` LIKE 'salary_structure'");
        if ($stmt->rowCount() == 0) {
            $this->db->query("ALTER TABLE `{$prefix}employees` 
                ADD COLUMN `salary_structure` TEXT NULL AFTER `status`");
        }
        
        // Check and add address column
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$prefix}employees` LIKE 'address'");
        if ($stmt->rowCount() == 0) {
            $this->db->query("ALTER TABLE `{$prefix}employees` 
                ADD COLUMN `address` TEXT NULL AFTER `phone`");
        }
    }
    
    public function down() {
        $prefix = $this->db->getPrefix();
        // Remove added columns
        try {
            $this->db->query("ALTER TABLE `{$prefix}employees` 
                DROP COLUMN `hire_date`,
                DROP COLUMN `salary_structure`,
                DROP COLUMN `address` ");
        } catch (Exception $e) {}
    }
}
