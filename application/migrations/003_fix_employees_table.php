<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_003_fix_employees_table extends Migration {
    
    public function up() {
        // Check if hire_date column exists
        $columns = $this->db->fetchAll("SHOW COLUMNS FROM `{$this->db->getPrefix()}employees` LIKE 'hire_date'");
        
        if (empty($columns)) {
            // Add hire_date column
            $this->db->query("ALTER TABLE `{$this->db->getPrefix()}employees` 
                ADD COLUMN `hire_date` DATE NULL AFTER `employment_type`");
        }
        
        // Check if salary_structure column exists
        $columns = $this->db->fetchAll("SHOW COLUMNS FROM `{$this->db->getPrefix()}employees` LIKE 'salary_structure'");
        
        if (empty($columns)) {
            // Add salary_structure column
            $this->db->query("ALTER TABLE `{$this->db->getPrefix()}employees` 
                ADD COLUMN `salary_structure` TEXT NULL AFTER `status`");
        }
        
        // Check if address column exists
        $columns = $this->db->fetchAll("SHOW COLUMNS FROM `{$this->db->getPrefix()}employees` LIKE 'address'");
        
        if (empty($columns)) {
            // Add address column
            $this->db->query("ALTER TABLE `{$this->db->getPrefix()}employees` 
                ADD COLUMN `address` TEXT NULL AFTER `phone`");
        }
    }
    
    public function down() {
        // Remove added columns
        $this->db->query("ALTER TABLE `{$this->db->getPrefix()}employees` 
            DROP COLUMN IF EXISTS `hire_date`,
            DROP COLUMN IF EXISTS `salary_structure`,
            DROP COLUMN IF EXISTS `address`");
    }
}
