<?php
/**
 * BMS Sync Diagnostic Tool v2.0
 * 
 * This script diagnoses issues with the Space-to-Booking sync functionality,
 * specifically targeting "Call to undefined function get_instance()" errors
 * and path-related issues in the cPanel environment.
 */

// --- INITIALIZATION ---
// Define base paths similar to index.php
if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/application/');
if (!defined('APPPATH')) define('APPPATH', __DIR__ . '/application/');
if (!defined('ROOTPATH')) define('ROOTPATH', __DIR__ . '/');

// Set error reporting to catch EVERYTHING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Custom error handler to capture Fatal Errors (PHP 7+)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "\n\n[PHP ERROR] {$errno}: {$errstr} in {$errfile} on line {$errline}\n";
    return false;
});

set_exception_handler(function($e) {
    echo "\n\n[UNCATCHABLE EXCEPTION/ERROR] " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
});

header('Content-Type: text/plain; charset=UTF-8');

echo "====================================================\n";
echo "   BMS SYNC DIAGNOSTIC TOOL v2.0 (cPanel Edition)   \n";
echo "====================================================\n\n";

// --- PHASE 1: ENVIRONMENT ---
echo "--- PHASE 1: SYSTEM ENVIRONMENT ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Current File: " . __FILE__ . "\n";
echo "APPPATH: " . APPPATH . "\n";
echo "get_instance exists: " . (function_exists('get_instance') ? 'YES' : 'NO') . "\n";
echo "session_status: " . session_status() . " (1=none, 2=active)\n";

// --- PHASE 2: CORE LOADS ---
echo "\n--- PHASE 2: CORE FRAMEWORK LOADS ---\n";

function try_load($file, $description) {
    $fullPath = ROOTPATH . $file;
    echo "Loading {$description} ({$file})... ";
    if (file_exists($fullPath)) {
        try {
            require_once $fullPath;
            echo "SUCCESS\n";
            return true;
        } catch (Throwable $e) {
            echo "FAILED: " . $e->getMessage() . "\n";
            return false;
        }
    } else {
        echo "NOT FOUND at {$fullPath}\n";
        return false;
    }
}

$core_ok = true;
$core_ok &= try_load('application/core/Autoloader.php', 'Autoloader');
$core_ok &= try_load('application/core/Database.php', 'Database');
$core_ok &= try_load('application/core/Base_Model.php', 'Base Model');
$core_ok &= try_load('application/core/Loader.php', 'Loader');

if (!$core_ok) {
    echo "!!! CRITICAL: Core framework files are missing or could not be loaded. !!!\n";
}

// --- PHASE 3: DATABASE ---
echo "\n--- PHASE 3: DATABASE CONNECTIVITY ---\n";
try {
    $db = Database::getInstance();
    echo "Database instance: OK\n";
    $conn = $db->getConnection();
    if ($conn) {
        echo "Database connection: OK\n";
        $prefix = $db->getPrefix();
        echo "Table Prefix: '{$prefix}'\n";
        
        // Check critical tables
        $tables = ['spaces', 'facilities', 'bookable_config', 'resource_availability'];
        foreach ($tables as $t) {
            $check = $db->fetchOne("SHOW TABLES LIKE '{$prefix}{$t}'");
            echo "Table '{$prefix}{$t}': " . ($check ? "EXISTS" : "MISSING") . "\n";
        }
    } else {
        echo "Database connection: FAILED (Check application/config/config.installed.php)\n";
    }
} catch (Throwable $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}

// --- PHASE 4: MODEL LOADING (The risky part) ---
echo "\n--- PHASE 4: TARGET MODEL INTEGRITY ---\n";

$target_models = [
    'application/models/Bookable_config_model.php' => 'Bookable_config_model',
    'application/models/Facility_model.php' => 'Facility_model',
    'application/models/Resource_availability_model.php' => 'Resource_availability_model',
    'application/models/Space_model.php' => 'Space_model'
];

foreach ($target_models as $path => $class) {
    echo "Checking Class '{$class}'... ";
    if (class_exists($class)) {
        echo "ALREADY LOADED\n";
    } else {
        $fullPath = ROOTPATH . $path;
        if (file_exists($fullPath)) {
            echo "Trying to load file... ";
            try {
                // We use a check for get_instance before loading to see if it triggers
                // but usually the crash happens inside the file.
                require_once $fullPath;
                echo "SUCCESS\n";
            } catch (Throwable $e) {
                echo "CRASHED while loading: " . $e->getMessage() . "\n";
            }
        } else {
            echo "FILE NOT FOUND at {$fullPath}\n";
        }
    }
}

// --- PHASE 5: SYNC LOGIC SIMULATION ---
echo "\n--- PHASE 5: SYNC LOGIC SIMULATION (DRY RUN) ---\n";

if (class_exists('Space_model')) {
    echo "Instantiating Space_model... ";
    try {
        $spaceModel = new Space_model();
        echo "SUCCESS\n";
        
        // Look for a space to test with
        $testSpace = $db->fetchOne("SELECT id, space_name, is_bookable FROM `{$db->getPrefix()}spaces` LIMIT 1");
        if ($testSpace) {
            echo "Using Space ID {$testSpace['id']} ('{$testSpace['space_name']}') for dry run.\n";
            
            // We won't actually call syncToBookingModule as it writes data, 
            // but we can check if the methods exist and are callable.
            if (method_exists($spaceModel, 'syncToBookingModule')) {
                echo "Method 'syncToBookingModule' exists: YES\n";
                echo "Checking for manual require_once blocks in Space_model.php... ";
                
                $content = file_get_contents(ROOTPATH . 'application/models/Space_model.php');
                $hasRequire = strpos($content, 'require_once') !== false;
                $hasAPPPATH = strpos($content, 'APPPATH') !== false;
                $hasGetInstance = strpos($content, 'get_instance') !== false;
                
                echo "\n  - Contains 'require_once': " . ($hasRequire ? "YES" : "NO");
                echo "\n  - Contains 'APPPATH': " . ($hasAPPPATH ? "YES" : "NO");
                echo "\n  - Contains 'get_instance': " . ($hasGetInstance ? "YES (POTENTIAL PROBLEM)" : "NO (GOOD)");
                echo "\n";
            } else {
                echo "Method 'syncToBookingModule' exists: NO (Update your files!)\n";
            }
        } else {
            echo "No spaces found in database to test with.\n";
        }
    } catch (Throwable $e) {
        echo "FAILED to instantiate or test Space_model: " . $e->getMessage() . "\n";
    }
} else {
    echo "Skipping Phase 5 because Space_model is not available.\n";
}

echo "\n--- DIAGNOSTICS COMPLETE ---\n";
echo "If all phases say SUCCESS and 'get_instance' is NOT found in models, \n";
echo "the sync should work. If you still see a Fatal Error in the browser, \n";
echo "ensure you have uploaded BOTH Spaces.php (controller) and Space_model.php (model).\n";
echo "====================================================\n";
