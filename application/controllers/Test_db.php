<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test_db extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function describe_table($table = 'erp_journal_entries')
    {
        echo "<h1>Describing table: $table</h1>";
        if (!$this->db->table_exists($table)) {
            echo "Table $table does not exist.";
            return;
        }

        $fields = $this->db->field_data($table);
        echo "<table border='1'><tr><th>Name</th><th>Type</th><th>Max Length</th><th>Primary Key</th></tr>";
        foreach ($fields as $field)
        {
            echo "<tr>";
            echo "<td>" . $field->name . "</td>";
            echo "<td>" . $field->type . "</td>";
            echo "<td>" . $field->max_length . "</td>";
            echo "<td>" . ($field->primary_key ? 'YES' : 'NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
