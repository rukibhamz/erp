<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Schema_fix extends Base_Controller {
    
    public function index() {
        echo "<h1>Schema Fix Tool</h1>";
        
        try {
            $db = $this->db->getConnection();
            
            // Check if column exists
            $stmt = $db->query("SHOW COLUMNS FROM erp_bookings LIKE 'space_id'");
            $exists = $stmt->fetch();
            
            if ($exists) {
                echo "<p style='color:green'>Column 'space_id' already exists.</p>";
            } else {
                echo "<p>Adding 'space_id' column...</p>";
                $sql = "ALTER TABLE erp_bookings ADD COLUMN space_id INT NULL AFTER booking_number";
                $db->exec($sql);
                echo "<p style='color:green'>Column 'space_id' added successfully!</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
        }
        
        echo "<p>You can now try the booking process again.</p>";
    }
}
