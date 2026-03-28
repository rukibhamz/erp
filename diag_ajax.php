<?php
require 'index.php';
$ci =& get_instance();
echo "Base URL: " . base_url() . "\n";
echo "Site URL: " . site_url() . "\n";

// Check permissions
try {
    echo "Testing getSpacesForLocation...\n";
    // Check if we can call it without errors (it might exit since it echoes JSON)
    // $ci->load->library('session');
    // $location_id = 1; // Assuming 1 exists
    // $_GET['location_id'] = $location_id;
    // require_once APPPATH . 'controllers/Bookings.php';
    // $ctrl = new Bookings();
    // $ctrl->getSpacesForLocation();
} catch (Exception $e) {
    echo "Error calling controller: " . $e->getMessage() . "\n";
}
