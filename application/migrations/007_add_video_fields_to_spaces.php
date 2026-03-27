<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_007_add_video_fields_to_spaces {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        $prefix = $this->db->getPrefix();
        
        if (!$this->columnExists($prefix . 'spaces', 'video_url')) {
            $this->db->query("ALTER TABLE `{$prefix}spaces` 
                ADD COLUMN `video_url` VARCHAR(500) NULL DEFAULT NULL AFTER `description` ");
        }

        if (!$this->columnExists($prefix . 'spaces', 'detailed_description')) {
            $this->db->query("ALTER TABLE `{$prefix}spaces` 
                ADD COLUMN `detailed_description` TEXT NULL DEFAULT NULL AFTER `video_url` ");
        }
    }
    
    private function columnExists($table, $column) {
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    }
}
