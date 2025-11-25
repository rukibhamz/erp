<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_is_bookable_to_properties extends CI_Migration {

    public function up() {
        $fields = array(
            'is_bookable' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'status'
            ),
            'facility_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
                'after' => 'is_bookable'
            )
        );
        
        // Check if column exists before adding to avoid errors
        if (!$this->db->field_exists('is_bookable', 'properties')) {
            $this->dbforge->add_column('properties', $fields);
        }
    }

    public function down() {
        if ($this->db->field_exists('is_bookable', 'properties')) {
            $this->dbforge->drop_column('properties', 'is_bookable');
        }
        if ($this->db->field_exists('facility_id', 'properties')) {
            $this->dbforge->drop_column('properties', 'facility_id');
        }
    }
}
