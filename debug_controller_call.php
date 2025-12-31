<?php
// Debug Controller Call
// Usage: http://localhost/erp/debug_controller_call.php?space_id=1&date=2025-12-31

// Define BASEPATH to satisfy checks
define('BASEPATH', 'system');

// Mock CodeIgniter Framework Components to load Controller
// This is tricky, simpler to just include the files and instantiate?
// No, too many dependencies (Loader, Config, Session).
// Better approach: Use curl to hit the actual endpoint and capture output?
// OR: Create a new controller inside the app structure that calls the other controller?

// Let's creating a new Controller inside the app is safer.
?>
