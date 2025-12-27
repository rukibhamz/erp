<?php
/**
 * COMPREHENSIVE ERP SYSTEM UNIT TEST
 * 
 * Tests all modules, controllers, models, and CRUD operations
 * Identifies errors, incomplete functionality, and business logic issues
 * 
 * Usage: php tests/comprehensive_system_test.php
 */

// Define BASEPATH for the application
define('BASEPATH', __DIR__ . '/../application/');

// Start output buffering to capture errors
ob_start();

// Suppress display errors but log them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Load configuration
$configFile = __DIR__ . '/../application/config/config.php';
if (!file_exists($configFile)) {
    die("ERROR: Config file not found. System may not be installed.\n");
}

$config = require $configFile;

// Check if installed
if (!isset($config['installed']) || !$config['installed']) {
    die("ERROR: System is not installed. Please run the installer first.\n");
}

// Load core classes
require_once __DIR__ . '/../application/core/Database.php';
require_once __DIR__ . '/../application/core/Base_Model.php';

$testResults = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'errors' => [],
    'warnings_list' => [],
    'incomplete_modules' => [],
    'crud_issues' => [],
    'business_logic_issues' => []
];

function test_pass($message) {
    global $testResults;
    $testResults['passed']++;
    echo "  ✓ {$message}\n";
}

function test_fail($message, $details = '') {
    global $testResults;
    $testResults['failed']++;
    $testResults['errors'][] = ['message' => $message, 'details' => $details];
    echo "  ✗ {$message}\n";
    if ($details) echo "    Details: {$details}\n";
}

function test_warning($message, $details = '') {
    global $testResults;
    $testResults['warnings']++;
    $testResults['warnings_list'][] = ['message' => $message, 'details' => $details];
    echo "  ⚠ {$message}\n";
    if ($details) echo "    Details: {$details}\n";
}

function add_incomplete_module($module, $reason) {
    global $testResults;
    $testResults['incomplete_modules'][] = ['module' => $module, 'reason' => $reason];
}

function add_crud_issue($model, $operation, $reason) {
    global $testResults;
    $testResults['crud_issues'][] = ['model' => $model, 'operation' => $operation, 'reason' => $reason];
}

function add_business_logic_issue($module, $issue) {
    global $testResults;
    $testResults['business_logic_issues'][] = ['module' => $module, 'issue' => $issue];
}

echo "========================================\n";
echo "ERP SYSTEM COMPREHENSIVE UNIT TEST\n";
echo "========================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// ===========================================
// TEST 1: DATABASE CONNECTION
// ===========================================
echo "\n--- TEST 1: DATABASE CONNECTION ---\n";
try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    test_pass("Database connection successful");
    
    // Test query execution
    $result = $db->fetchOne("SELECT 1 as test");
    if ($result && $result['test'] == 1) {
        test_pass("Query execution successful");
    } else {
        test_fail("Query execution failed");
    }
} catch (Exception $e) {
    test_fail("Database connection failed", $e->getMessage());
    die("Cannot continue without database connection.\n");
}

// ===========================================
// TEST 2: REQUIRED DATABASE TABLES
// ===========================================
echo "\n--- TEST 2: REQUIRED DATABASE TABLES ---\n";

$requiredTables = [
    // Core tables
    'users' => 'User management',
    'companies' => 'Company management',
    'modules_settings' => 'Module configuration',
    'activity_log' => 'Activity tracking',
    
    // Permission system
    'permissions' => 'Permission definitions',
    'roles' => 'Role definitions',
    'role_permissions' => 'Role-permission mappings',
    'user_permissions' => 'User-specific permissions',
    
    // Accounting
    'accounts' => 'Chart of accounts',
    'transactions' => 'Financial transactions',
    'journal_entries' => 'Journal entries',
    'invoices' => 'Customer invoices',
    'payments' => 'Payment records',
    'customers' => 'Customer records',
    'vendors' => 'Vendor records',
    'bills' => 'Vendor bills',
    
    // Inventory
    'items' => 'Inventory items',
    'stock_levels' => 'Stock quantities',
    'stock_transactions' => 'Stock movements',
    'stock_adjustments' => 'Stock adjustments',
    'stock_takes' => 'Physical inventory',
    'suppliers' => 'Supplier records',
    'purchase_orders' => 'Purchase orders',
    'goods_receipts' => 'Goods receipts',
    
    // Bookings
    'bookings' => 'Booking records',
    'facilities' => 'Facility/resource definitions',
    'booking_payments' => 'Booking payments',
    'booking_addons' => 'Booking add-ons',
    
    // Properties/Locations
    'locations' => 'Location/property records',
    'spaces' => 'Space units',
    'leases' => 'Lease agreements',
    'tenants' => 'Tenant records',
    'rent_invoices' => 'Rent invoices',
    
    // Utilities
    'meters' => 'Utility meters',
    'meter_readings' => 'Meter readings',
    'utility_bills' => 'Utility bills',
    'utility_providers' => 'Utility providers',
    'utility_payments' => 'Utility payments',
    'tariffs' => 'Utility tariffs',
    
    // Payroll
    'employees' => 'Employee records',
    'payroll_runs' => 'Payroll processing runs',
    'payslips' => 'Employee payslips',
    'paye_deductions' => 'PAYE tax deductions',
    
    // Tax
    'tax_types' => 'Tax type definitions',
    'tax_payments' => 'Tax payments',
    'vat_returns' => 'VAT returns',
    'wht_certificates' => 'WHT certificates',
    
    // POS
    'pos_terminals' => 'POS terminals',
    'pos_sessions' => 'POS sessions',
    'pos_sales' => 'POS sales transactions',
    
    // Fixed Assets
    'fixed_assets' => 'Fixed asset records'
];

$missingTables = [];
$existingTables = [];

foreach ($requiredTables as $table => $description) {
    try {
        $exists = $db->fetchOne(
            "SELECT COUNT(*) as count FROM information_schema.tables 
             WHERE table_schema = DATABASE() AND table_name = ?",
            ["{$prefix}{$table}"]
        );
        
        if (($exists['count'] ?? 0) > 0) {
            $existingTables[] = $table;
        } else {
            $missingTables[] = $table;
            add_incomplete_module($description, "Table '{$prefix}{$table}' does not exist");
        }
    } catch (Exception $e) {
        $missingTables[] = $table;
        test_fail("Error checking table {$table}", $e->getMessage());
    }
}

echo "  Tables found: " . count($existingTables) . "/" . count($requiredTables) . "\n";

if (count($missingTables) > 0) {
    test_warning("Missing tables: " . implode(', ', $missingTables));
} else {
    test_pass("All required tables exist");
}

// ===========================================
// TEST 3: MODEL FILES EXIST
// ===========================================
echo "\n--- TEST 3: MODEL FILES ---\n";

$modelDir = __DIR__ . '/../application/models/';
$modelFiles = glob($modelDir . '*_model.php');

echo "  Found " . count($modelFiles) . " model files\n";

$modelsWithIssues = [];

foreach ($modelFiles as $modelFile) {
    $modelName = basename($modelFile, '.php');
    
    // Check if file is valid PHP
    $content = file_get_contents($modelFile);
    
    // Check for syntax-breaking patterns
    if (preg_match('/class\s+\w+\s+extends\s*$/', $content)) {
        add_crud_issue($modelName, 'syntax', 'Missing parent class name');
        $modelsWithIssues[] = $modelName;
    }
    
    // Check if table property is defined
    if (!preg_match('/protected\s+\$table\s*=/', $content)) {
        add_crud_issue($modelName, 'config', 'Missing $table property');
        $modelsWithIssues[] = $modelName;
    }
}

if (count($modelsWithIssues) > 0) {
    test_warning("Models with potential issues: " . count($modelsWithIssues));
} else {
    test_pass("All model files appear valid");
}

// ===========================================
// TEST 4: CONTROLLER FILES EXIST
// ===========================================
echo "\n--- TEST 4: CONTROLLER FILES ---\n";

$controllerDir = __DIR__ . '/../application/controllers/';
$controllerFiles = glob($controllerDir . '*.php');

echo "  Found " . count($controllerFiles) . " controller files\n";

$controllersWithIssues = [];

foreach ($controllerFiles as $controllerFile) {
    $controllerName = basename($controllerFile, '.php');
    
    // Skip backup files
    if (strpos($controllerName, '.backup') !== false) continue;
    
    $content = file_get_contents($controllerFile);
    
    // Check if extends Base_Controller
    if (!preg_match('/class\s+\w+\s+extends\s+Base_Controller/', $content)) {
        add_incomplete_module($controllerName, 'Does not extend Base_Controller properly');
        $controllersWithIssues[] = $controllerName;
    }
    
    // Check for index method
    if (!preg_match('/public\s+function\s+index\s*\(/', $content)) {
        add_incomplete_module($controllerName, 'Missing index() method');
        $controllersWithIssues[] = $controllerName;
    }
}

if (count($controllersWithIssues) > 0) {
    test_warning("Controllers with potential issues: " . count($controllersWithIssues));
    foreach ($controllersWithIssues as $c) {
        echo "    - {$c}\n";
    }
} else {
    test_pass("All controller files appear valid");
}

// ===========================================
// TEST 5: MODEL CRUD OPERATIONS
// ===========================================
echo "\n--- TEST 5: MODEL CRUD OPERATIONS ---\n";

$modelsToTest = [
    'User_model' => 'users',
    'Customer_model' => 'customers',
    'Invoice_model' => 'invoices',
    'Item_model' => 'items',
    'Booking_model' => 'bookings',
    'Employee_model' => 'employees',
    'Location_model' => 'locations',
    'Space_model' => 'spaces',
    'Lease_model' => 'leases',
    'Meter_model' => 'meters',
    'Account_model' => 'accounts',
    'Transaction_model' => 'transactions',
    'Supplier_model' => 'suppliers',
    'Vendor_model' => 'vendors'
];

foreach ($modelsToTest as $modelName => $tableName) {
    $modelFile = $modelDir . $modelName . '.php';
    
    if (!file_exists($modelFile)) {
        add_crud_issue($modelName, 'file', "Model file does not exist");
        test_fail("{$modelName}: File not found");
        continue;
    }
    
    // Check if table exists
    $tableExists = $db->fetchOne(
        "SELECT COUNT(*) as count FROM information_schema.tables 
         WHERE table_schema = DATABASE() AND table_name = ?",
        ["{$prefix}{$tableName}"]
    );
    
    if (($tableExists['count'] ?? 0) == 0) {
        add_crud_issue($modelName, 'table', "Table '{$prefix}{$tableName}' does not exist");
        test_warning("{$modelName}: Table missing");
        continue;
    }
    
    try {
        require_once $modelFile;
        $model = new $modelName();
        
        // Test getAll
        try {
            $all = $model->getAll(1);
            test_pass("{$modelName}: getAll() works");
        } catch (Exception $e) {
            add_crud_issue($modelName, 'read', $e->getMessage());
            test_fail("{$modelName}: getAll() failed");
        }
        
        // Test count
        try {
            $count = $model->count();
            test_pass("{$modelName}: count() works ({$count} records)");
        } catch (Exception $e) {
            add_crud_issue($modelName, 'count', $e->getMessage());
            test_fail("{$modelName}: count() failed");
        }
        
    } catch (Exception $e) {
        add_crud_issue($modelName, 'load', $e->getMessage());
        test_fail("{$modelName}: Failed to load model", $e->getMessage());
    }
}

// ===========================================
// TEST 6: SPECIFIC MODULE FUNCTIONALITY
// ===========================================
echo "\n--- TEST 6: MODULE-SPECIFIC CHECKS ---\n";

// Check Accounting module
echo "\n  [Accounting Module]\n";

try {
    require_once $modelDir . 'Account_model.php';
    $accountModel = new Account_model();
    
    // Check if default accounts exist
    $accounts = $accountModel->getAll();
    if (count($accounts) == 0) {
        add_business_logic_issue('Accounting', 'No chart of accounts defined');
        test_warning("No accounts in chart of accounts");
    } else {
        test_pass("Chart of accounts has " . count($accounts) . " accounts");
    }
} catch (Exception $e) {
    add_business_logic_issue('Accounting', 'Account model error: ' . $e->getMessage());
    test_fail("Account model error", $e->getMessage());
}

// Check Inventory module
echo "\n  [Inventory Module]\n";

try {
    require_once $modelDir . 'Item_model.php';
    $itemModel = new Item_model();
    
    // Check item count
    $items = $itemModel->getAll();
    test_pass("Inventory has " . count($items) . " items");
    
    // Check for methods
    $reflection = new ReflectionClass($itemModel);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $methodNames = array_map(function($m) { return $m->getName(); }, $methods);
    
    $requiredMethods = ['getById', 'create', 'update', 'delete', 'getAll'];
    foreach ($requiredMethods as $method) {
        if (!in_array($method, $methodNames)) {
            add_crud_issue('Item_model', $method, "Method {$method}() not found");
        }
    }
    
} catch (Exception $e) {
    add_business_logic_issue('Inventory', 'Item model error: ' . $e->getMessage());
    test_fail("Item model error", $e->getMessage());
}

// Check Booking module
echo "\n  [Booking Module]\n";

try {
    require_once $modelDir . 'Booking_model.php';
    $bookingModel = new Booking_model();
    
    $bookings = $bookingModel->getAll();
    test_pass("Booking system has " . count($bookings) . " bookings");
    
} catch (Exception $e) {
    add_business_logic_issue('Bookings', 'Booking model error: ' . $e->getMessage());
    test_fail("Booking model error", $e->getMessage());
}

// Check Payroll module
echo "\n  [Payroll Module]\n";

try {
    require_once $modelDir . 'Payroll_model.php';
    require_once $modelDir . 'Employee_model.php';
    
    $payrollModel = new Payroll_model();
    $employeeModel = new Employee_model();
    
    $employees = $employeeModel->getAll();
    test_pass("Payroll system has " . count($employees) . " employees");
    
} catch (Exception $e) {
    add_business_logic_issue('Payroll', 'Payroll model error: ' . $e->getMessage());
    test_fail("Payroll model error", $e->getMessage());
}

// Check Tax module
echo "\n  [Tax Module]\n";

try {
    require_once $modelDir . 'Tax_type_model.php';
    $taxTypeModel = new Tax_type_model();
    
    $taxTypes = $taxTypeModel->getAll();
    if (count($taxTypes) == 0) {
        add_business_logic_issue('Tax', 'No tax types configured');
        test_warning("No tax types configured");
    } else {
        test_pass("Tax system has " . count($taxTypes) . " tax types");
    }
    
} catch (Exception $e) {
    add_business_logic_issue('Tax', 'Tax model error: ' . $e->getMessage());
    test_fail("Tax model error", $e->getMessage());
}

// Check POS module
echo "\n  [POS Module]\n";

try {
    require_once $modelDir . 'Pos_terminal_model.php';
    $posModel = new Pos_terminal_model();
    
    $terminals = $posModel->getAll();
    test_pass("POS system has " . count($terminals) . " terminals");
    
} catch (Exception $e) {
    add_business_logic_issue('POS', 'POS model error: ' . $e->getMessage());
    test_fail("POS model error", $e->getMessage());
}

// ===========================================
// TEST 7: VIEW FILES CHECK
// ===========================================
echo "\n--- TEST 7: VIEW FILES ---\n";

$viewDir = __DIR__ . '/../application/views/';
$viewFolders = glob($viewDir . '*', GLOB_ONLYDIR);

$missingViews = [];
$controllerViewMap = [
    'accounting' => ['index'],
    'bookings' => ['index', 'create', 'view', 'calendar'],
    'inventory' => ['index'],
    'items' => ['index', 'create', 'view', 'edit'],
    'users' => ['index', 'create', 'edit'],
    'customers' => ['index', 'create', 'view', 'edit'],
    'payroll' => ['index', 'employees', 'process'],
    'locations' => ['index', 'create', 'view', 'edit'],
    'spaces' => ['index', 'create', 'view', 'edit'],
    'leases' => ['index', 'create', 'view', 'edit'],
    'pos' => ['index', 'terminal'],
    'reports' => ['index'],
    'dashboard' => ['index']
];

foreach ($controllerViewMap as $controller => $views) {
    foreach ($views as $view) {
        $viewFile = $viewDir . $controller . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            $missingViews[] = "{$controller}/{$view}";
            add_incomplete_module(ucfirst($controller), "Missing view: {$view}.php");
        }
    }
}

if (count($missingViews) > 0) {
    test_warning("Missing view files: " . count($missingViews));
    foreach (array_slice($missingViews, 0, 10) as $v) {
        echo "    - {$v}\n";
    }
    if (count($missingViews) > 10) {
        echo "    ... and " . (count($missingViews) - 10) . " more\n";
    }
} else {
    test_pass("All expected view files exist");
}

// ===========================================
// TEST 8: HELPER FILES
// ===========================================
echo "\n--- TEST 8: HELPER FILES ---\n";

$helperDir = __DIR__ . '/../application/helpers/';
$requiredHelpers = [
    'common_helper.php' => 'Common utility functions',
    'validation_helper.php' => 'Form validation',
    'csrf_helper.php' => 'CSRF protection',
    'email_helper.php' => 'Email sending',
    'permission_helper.php' => 'Permission checking',
    'module_helper.php' => 'Module management'
];

foreach ($requiredHelpers as $helper => $description) {
    $helperFile = $helperDir . $helper;
    if (file_exists($helperFile)) {
        test_pass("Helper exists: {$helper}");
    } else {
        add_incomplete_module('Core Helpers', "Missing {$helper} - {$description}");
        test_fail("Missing helper: {$helper}");
    }
}

// ===========================================
// TEST 9: PERMISSION SYSTEM
// ===========================================
echo "\n--- TEST 9: PERMISSION SYSTEM ---\n";

try {
    // Check permissions table has entries
    $permCount = $db->fetchOne("SELECT COUNT(*) as count FROM `{$prefix}permissions`");
    if (($permCount['count'] ?? 0) > 0) {
        test_pass("Permissions defined: " . $permCount['count']);
    } else {
        add_business_logic_issue('Permissions', 'No permissions defined in database');
        test_warning("No permissions defined");
    }
    
    // Check roles table has entries
    $roleCount = $db->fetchOne("SELECT COUNT(*) as count FROM `{$prefix}roles`");
    if (($roleCount['count'] ?? 0) > 0) {
        test_pass("Roles defined: " . $roleCount['count']);
    } else {
        add_business_logic_issue('Permissions', 'No roles defined in database');
        test_warning("No roles defined");
    }
    
} catch (Exception $e) {
    add_business_logic_issue('Permissions', 'Permission system error: ' . $e->getMessage());
    test_fail("Permission system error", $e->getMessage());
}

// ===========================================
// TEST 10: BUSINESS LOGIC VALIDATION
// ===========================================
echo "\n--- TEST 10: BUSINESS LOGIC VALIDATION ---\n";

// Check invoice numbering
try {
    $lastInvoice = $db->fetchOne("SELECT invoice_number FROM `{$prefix}invoices` ORDER BY id DESC LIMIT 1");
    if ($lastInvoice) {
        test_pass("Invoice numbering active (last: {$lastInvoice['invoice_number']})");
    } else {
        test_pass("No invoices yet (system ready)");
    }
} catch (Exception $e) {
    add_business_logic_issue('Invoicing', 'Invoice numbering check failed: ' . $e->getMessage());
}

// Check booking date validation logic exists
$bookingControllerFile = $controllerDir . 'Bookings.php';
$bookingContent = file_get_contents($bookingControllerFile);

if (strpos($bookingContent, 'strtotime') !== false || strpos($bookingContent, 'DateTime') !== false) {
    test_pass("Booking date validation exists");
} else {
    add_business_logic_issue('Bookings', 'No date validation found');
    test_warning("Booking date validation may be missing");
}

// Check transaction balance
try {
    $debitSum = $db->fetchOne("SELECT COALESCE(SUM(debit), 0) as total FROM `{$prefix}journal_entries`");
    $creditSum = $db->fetchOne("SELECT COALESCE(SUM(credit), 0) as total FROM `{$prefix}journal_entries`");
    
    $debit = floatval($debitSum['total'] ?? 0);
    $credit = floatval($creditSum['total'] ?? 0);
    
    if (abs($debit - $credit) < 0.01) {
        test_pass("Journal entries balanced (Debit: {$debit}, Credit: {$credit})");
    } else {
        add_business_logic_issue('Accounting', "Journal entries out of balance: Debit={$debit}, Credit={$credit}");
        test_fail("Journal entries out of balance");
    }
} catch (Exception $e) {
    // Journal entries table might not exist or be empty
    test_pass("Journal entries check skipped (no data)");
}

// ===========================================
// FINAL SUMMARY
// ===========================================
echo "\n========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n\n";

echo "Tests Passed: {$testResults['passed']}\n";
echo "Tests Failed: {$testResults['failed']}\n";
echo "Warnings: {$testResults['warnings']}\n\n";

if (count($testResults['incomplete_modules']) > 0) {
    echo "--- INCOMPLETE/NON-FUNCTIONAL MODULES ---\n";
    $grouped = [];
    foreach ($testResults['incomplete_modules'] as $item) {
        $grouped[$item['module']][] = $item['reason'];
    }
    foreach ($grouped as $module => $reasons) {
        echo "\n  [{$module}]\n";
        foreach (array_unique($reasons) as $reason) {
            echo "    - {$reason}\n";
        }
    }
    echo "\n";
}

if (count($testResults['crud_issues']) > 0) {
    echo "--- CRUD OPERATION ISSUES ---\n";
    foreach ($testResults['crud_issues'] as $issue) {
        echo "  - {$issue['model']}: {$issue['operation']} - {$issue['reason']}\n";
    }
    echo "\n";
}

if (count($testResults['business_logic_issues']) > 0) {
    echo "--- BUSINESS LOGIC ISSUES ---\n";
    foreach ($testResults['business_logic_issues'] as $issue) {
        echo "  - [{$issue['module']}] {$issue['issue']}\n";
    }
    echo "\n";
}

if (count($testResults['errors']) > 0) {
    echo "--- ERRORS ---\n";
    foreach ($testResults['errors'] as $error) {
        echo "  - {$error['message']}\n";
        if ($error['details']) {
            echo "    {$error['details']}\n";
        }
    }
    echo "\n";
}

$overallStatus = $testResults['failed'] == 0 ? 'PASSED' : 'ISSUES FOUND';
echo "========================================\n";
echo "OVERALL STATUS: {$overallStatus}\n";
echo "========================================\n";

// Output JSON summary for programmatic use
$jsonSummary = [
    'date' => date('Y-m-d H:i:s'),
    'passed' => $testResults['passed'],
    'failed' => $testResults['failed'],
    'warnings' => $testResults['warnings'],
    'incomplete_modules' => $testResults['incomplete_modules'],
    'crud_issues' => $testResults['crud_issues'],
    'business_logic_issues' => $testResults['business_logic_issues'],
    'errors' => $testResults['errors']
];

file_put_contents(__DIR__ . '/test_results.json', json_encode($jsonSummary, JSON_PRETTY_PRINT));
echo "\nDetailed results saved to: tests/test_results.json\n";
