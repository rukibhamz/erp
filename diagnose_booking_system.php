<?php
/**
 * BMS Booking System Diagnostic Tool v3.0
 * 
 * This script diagnoses issues with the entire Booking Management flow,
 * including Space Sync, Booking Configuration, and Database Integrations.
 * Optimized for cPanel/Linux environments.
 */

// --- INITIALIZATION ---
if (!defined('BASEPATH')) define('BASEPATH', __DIR__ . '/application/');
if (!defined('APPPATH')) define('APPPATH', __DIR__ . '/application/');
if (!defined('ROOTPATH')) define('ROOTPATH', __DIR__ . '/');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set high timeout for heavy diagnostic
set_time_limit(60);

// Global state for report
$results = [];

/**
 * Log a check result
 */
function record($category, $check, $status, $message = '') {
    global $results;
    $results[] = [
        'category' => $category,
        'check' => $check,
        'status' => $status, // SUCCESS, WARNING, FAILED, CRASHED
        'message' => $message
    ];
}

// Custom error/exception catcher
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    record('PHP Engine', "Error {$errno}", 'CRASHED', "{$errstr} in {$errfile} on line {$errline}");
    return false;
});

set_exception_handler(function($e) {
    record('System', 'Uncaught Exception', 'CRASHED', get_class($e) . ": " . $e->getMessage() . "\nTrace: " . substr($e->getTraceAsString(), 0, 200));
    print_report();
    exit;
});

header('Content-Type: text/plain; charset=UTF-8');

echo "========================================================\n";
echo "   BMS BOOKING SYSTEM DIAGNOSTIC TOOL v3.0 (cPanel)     \n";
echo "========================================================\n\n";

// --- PHASE 1: SYSTEM BASICS ---
record('Environment', 'PHP Version', 'SUCCESS', PHP_VERSION);
record('Environment', 'SAPI', 'SUCCESS', php_sapi_name());
record('Environment', 'get_instance exists', function_exists('get_instance') ? 'SUCCESS' : 'WARNING', function_exists('get_instance') ? 'Yes' : 'No (Likely custom framework)');
record('Environment', 'APPPATH readable', is_dir(APPPATH) ? 'SUCCESS' : 'FAILED', APPPATH);

// --- PHASE 2: FRAMEWORK CORE ---
function try_require($file) {
    $path = ROOTPATH . $file;
    if (!file_exists($path)) {
        return ['status' => 'FAILED', 'msg' => "File missing at {$path}"];
    }
    try {
        require_once $path;
        return ['status' => 'SUCCESS', 'msg' => 'Loaded'];
    } catch (Throwable $e) {
        return ['status' => 'CRASHED', 'msg' => $e->getMessage()];
    }
}

$core_files = [
    'application/core/Autoloader.php' => 'Autoloader',
    'application/core/Database.php' => 'Database',
    'application/core/Base_Model.php' => 'Base Model',
    'application/core/Loader.php' => 'Loader',
    'application/core/Base_Controller.php' => 'Base Controller'
];

foreach ($core_files as $file => $label) {
    $res = try_require($file);
    record('Framework', $label, $res['status'], $res['msg']);
}

// --- PHASE 3: DATABASE & SCHEMA ---
try {
    if (class_exists('Database')) {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        if ($conn) {
            record('Database', 'Connection', 'SUCCESS', 'Established');
            $prefix = $db->getPrefix();
            
            // Check Bookable Config Schema
            $t = $prefix . "bookable_config";
            $cols = $db->fetchAll("DESCRIBE `{$t}`");
            if ($cols) {
                record('Schema', 'Table bookable_config', 'SUCCESS', 'Exists');
                $col_names = array_column($cols, 'Field');
                $required = ['space_id', 'pricing_rules', 'availability_rules', 'booking_types'];
                foreach ($required as $r) {
                    record('Schema', "Column {$t}.{$r}", in_array($r, $col_names) ? 'SUCCESS' : 'FAILED', in_array($r, $col_names) ? 'Found' : 'MISSING');
                }
            } else {
                record('Schema', 'Table bookable_config', 'FAILED', 'Missing table!');
            }

            // Check Facilities Schema (Sync Target)
            $t = $prefix . "facilities";
            $cols = $db->fetchAll("DESCRIBE `{$t}`");
            if ($cols) {
                record('Schema', 'Table facilities', 'SUCCESS', 'Exists');
                $col_names = array_column($cols, 'Field');
                $sync_cols = ['hourly_rate', 'daily_rate', 'half_day_rate', 'security_deposit'];
                foreach ($sync_cols as $r) {
                    record('Schema', "Column {$t}.{$r}", in_array($r, $col_names) ? 'SUCCESS' : 'FAILED', in_array($r, $col_names) ? 'Found' : 'MISSING');
                }
            }
        } else {
            record('Database', 'Connection', 'FAILED', 'Check config.installed.php');
        }
    }
} catch (Throwable $e) {
    record('Database', 'Schema Check', 'CRASHED', $e->getMessage());
}

// --- PHASE 4: MODEL & INTEGRITY ---
$models = [
    'application/models/Bookable_config_model.php' => 'Bookable_config_model',
    'application/models/Space_model.php' => 'Space_model',
    'application/models/Facility_model.php' => 'Facility_model'
];

foreach ($models as $file => $class) {
    $res = try_require($file);
    if ($res['status'] === 'SUCCESS') {
        try {
            $instance = new $class();
            record('Models', $class, 'SUCCESS', 'Instantiated');
        } catch (Throwable $e) {
            record('Models', $class, 'CRASHED', "Instantiation failed: " . $e->getMessage());
        }
    } else {
        record('Models', $class, $res['status'], $res['msg']);
    }
}

// --- PHASE 5: CONTROLLER & VIEW ---
$ctrlFile = ROOTPATH . 'application/controllers/Spaces.php';
if (file_exists($ctrlFile)) {
    try {
        $content = file_get_contents($ctrlFile);
        record('Controller', 'Spaces.php file', 'SUCCESS', 'Exists');
        record('Controller', 'Method updateBookableConfig', strpos($content, 'updateBookableConfig') !== false ? 'SUCCESS' : 'FAILED', 'Presence in file');
        record('Controller', 'Method syncToBooking', strpos($content, 'syncToBooking') !== false ? 'SUCCESS' : 'FAILED', 'Presence in file');
        
        // Check for the problematic loadModel call I fixed
        record('Controller', 'Standard model loading', strpos($content, 'loadModel') !== false ? 'SUCCESS' : 'WARNING', 'Uses $this->loadModel()');
    } catch (Throwable $e) {
        record('Controller', 'Analysis', 'CRASHED', $e->getMessage());
    }
} else {
    record('Controller', 'Spaces.php', 'FAILED', 'Missing file!');
}

$viewFile = ROOTPATH . 'application/views/spaces/bookable_config.php';
record('UI', 'Config View File', file_exists($viewFile) ? 'SUCCESS' : 'FAILED', file_exists($viewFile) ? 'Exists' : 'Missing: ' . $viewFile);

// --- PRINT REPORT ---
print_report();

function print_report() {
    global $results;
    $current_cat = '';
    
    foreach ($results as $r) {
        if ($r['category'] !== $current_cat) {
            $current_cat = $r['category'];
            echo "\n--- " . strtoupper($current_cat) . " ---\n";
        }
        
        $status = str_pad($r['status'], 10);
        $check = str_pad($r['check'], 30);
        echo "{$status} | {$check} | {$r['message']}\n";
    }
    
    echo "\n========================================================\n";
    echo "SUMMARY:\n";
    $failed = array_filter($results, function($v) { return $v['status'] === 'FAILED' || $v['status'] === 'CRASHED'; });
    if (empty($failed)) {
        echo "SUCCESS: No critical issues found in synchronization or configuration setup.\n";
    } else {
        echo "FAILED: Found " . count($failed) . " issues. Please address the errors above.\n";
    }
    echo "========================================================\n";
}
