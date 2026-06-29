<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_is_bookable_to_properties {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        $prefix = $this->db->getPrefix();
        $table = "`{$prefix}properties`";

        if (!$this->columnExists($prefix . 'properties', 'is_bookable')) {
            $this->db->query("ALTER TABLE {$table}
                ADD COLUMN `is_bookable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");
        }

        if (!$this->columnExists($prefix . 'properties', 'facility_id')) {
            $this->db->query("ALTER TABLE {$table}
                ADD COLUMN `facility_id` INT(11) UNSIGNED NULL AFTER `is_bookable`");
        }

        try {
            $this->db->query("ALTER TABLE {$table}
                ADD CONSTRAINT `{$prefix}properties_facility_fk`
                FOREIGN KEY (`facility_id`) REFERENCES `{$prefix}facilities`(`id`) ON DELETE SET NULL");
        } catch (Exception $e) {
            // Constraint may already exist on upgraded databases
        }
    }

    private function columnExists($table, $column) {
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    }

    public function down() {
        $prefix = $this->db->getPrefix();
        try {
            $this->db->query("ALTER TABLE `{$prefix}properties` DROP FOREIGN KEY `{$prefix}properties_facility_fk`");
        } catch (Exception $e) {
        }
        try {
            $this->db->query("ALTER TABLE `{$prefix}properties` DROP COLUMN `facility_id`, DROP COLUMN `is_bookable`");
        } catch (Exception $e) {
        }
    }
}
