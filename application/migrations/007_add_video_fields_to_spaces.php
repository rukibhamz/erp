<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_007_add_video_fields_to_spaces extends CI_Migration {

    public function up() {
        if (!$this->db->field_exists('video_url', 'spaces')) {
            $this->dbforge->add_column('spaces', array(
                'video_url' => array(
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => TRUE,
                    'default'    => NULL,
                    'after'      => 'description'
                )
            ));
        }

        if (!$this->db->field_exists('detailed_description', 'spaces')) {
            $this->dbforge->add_column('spaces', array(
                'detailed_description' => array(
                    'type'    => 'TEXT',
                    'null'    => TRUE,
                    'default' => NULL,
                    'after'   => 'video_url'
                )
            ));
        }
    }
}
