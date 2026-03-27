<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_is_bookable_to_properties {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        $prefix = $this->db->getPrefix();
        
        // Add is_bookable column if not exists
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$prefix}properties` LIKE 'is_bookable'");
        if ($stmt->rowCount() == 0) {
            $this->db->query("ALTER TABLE `{$prefix}properties` 
                ADD COLUMN `is_bookable` TINYINT(1) DEFAULT 0 AFTER `status`,
                ADD COLUMN `facility_id` INT(11) UNSIGNED NULL AFTER `is_bookable` (FOREIGN KEY REFERENCES `{$prefix}facilities`(`id`) ON DELETE SET NULL)");
        }
    }

    public function down() {
        $prefix = $this->db->getPrefix();
        try {
            $this->db->query("ALTER TABLE `{$prefix}properties` DROP COLUMN `is_bookable`, DROP COLUMN `facility_id`");
        } catch (Exception $e) {}
    }
}
