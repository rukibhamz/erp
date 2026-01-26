<?php
// Load CodeIgniter framework essentially or just use raw PDO
// Simpler to use raw PDO since we know credentials from Database.php (which I can read)
// Or just reuse Database.php?

// Let's try a standalone script verifying the table columns
define('BASEPATH', __DIR__ . '/application/');
require_once __DIR__ . '/application/config/config.php';
// Need db config
$dbConfig = require __DIR__ . '/application/config/database.php';
// Database.php structure might be different, let's just use what I saw in Database.php or try to connect.

// Actually, easier:
// The user has XAMPP. `php` should be available.
// I will read `application/config/database.php` first to get credentials?
// Or just try root/empty which is standard XAMPP.

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP
$db = 'rukibhamz_erp'; // inferred from path? No, user metadata says `rukibhamz/erp`.
// Wait, Step 1870 metadata says: `c:\xampp\htdocs\erp -> rukibhamz/erp`.
// The DB name is likely in `application/config/database.php`.

// Let's try to include the app's Database class? No, too many dependencies.
// I'll read the config file first in the next step if this fails.
// But I'll write a script that attempts to connect using standard XAMPP defaults + common DB names or $argv.

// Smart approach:
// Read `application/config/database.php` content in the script?
// No, I can view it.

// Let's just view `database.php` first to be sure of credentials.
// THEN write the script.
?>
