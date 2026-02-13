<?php
/**
 * CRUD Unit Test Suite
 * Tests CREATE, READ, UPDATE, DELETE operations across all core models
 * Run: php tests/crud_unit_test.php
 */

define('BASEPATH', realpath(__DIR__ . '/../application') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

require_once BASEPATH . 'core/Database.php';
require_once BASEPATH . 'core/Base_Model.php';

// ---------- Test Harness ----------
$results = ['passed' => 0, 'failed' => 0, 'warnings' => 0];
$errors = [];
$warnings = [];
$details = [];

function t_pass($msg, $cat) {
    global $results, $details;
    $results['passed']++;
    $details[] = ['status' => 'PASS', 'msg' => $msg, 'category' => $cat];
}
function t_fail($msg, $detail, $cat) {
    global $results, $errors, $details;
    $results['failed']++;
    $errors[] = ['msg' => $msg, 'details' => $detail, 'category' => $cat];
    $details[] = ['status' => 'FAIL', 'msg' => $msg, 'detail' => $detail, 'category' => $cat];
}
function t_warn($msg, $detail, $cat) {
    global $results, $warnings, $details;
    $results['warnings']++;
    $warnings[] = ['msg' => $msg, 'details' => $detail, 'category' => $cat];
    $details[] = ['status' => 'WARN', 'msg' => $msg, 'detail' => $detail, 'category' => $cat];
}

// ---------- Setup ----------
$db = Database::getInstance();
$prefix = $db->getPrefix();

// Helper: Check if a table exists
function tableExists($db, $prefix, $table) {
    try {
        $result = $db->fetchOne("SELECT 1 FROM `{$prefix}{$table}` LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Helper: Load a model class
function loadModel($modelFile) {
    $path = BASEPATH . 'models/' . $modelFile;
    if (file_exists($path)) {
        require_once $path;
        return true;
    }
    return false;
}

echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  CRUD UNIT TEST SUITE                                        ║\n";
echo "║  Testing all core model CRUD operations                      ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// ---------- Model Definitions ----------
// Each entry: [ModelFile, ClassName, TableName, TestData, UpdateField, UpdateValue, UniqueField]
$modelTests = [
    // Accounting
    [
        'file' => 'Account_model.php',
        'class' => 'Account_model',
        'table' => 'accounts',
        'data' => [
            'account_code' => '1999',
            'account_name' => '_Test Account',
            'account_type' => 'Assets',
            'balance' => 0.00,
            'status' => 'active',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'account_name',
        'update_value' => '_Test Account Updated',
        'unique_field' => 'account_code'
    ],
    // Customer
    [
        'file' => 'Customer_model.php',
        'class' => 'Customer_model',
        'table' => 'customers',
        'data' => [
            'customer_code' => '_TEST_CUST_99',
            'company_name' => '_Test Customer Co',
            'contact_name' => 'Test Contact',
            'email' => '_test_crud@test.com',
            'phone' => '0000000000',
            'status' => 'active',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'company_name',
        'update_value' => '_Test Customer Updated',
        'unique_field' => 'customer_code'
    ],
    // Vendor
    [
        'file' => 'Vendor_model.php',
        'class' => 'Vendor_model',
        'table' => 'vendors',
        'data' => [
            'vendor_code' => '_TEST_VEND_99',
            'company_name' => '_Test Vendor Co',
            'contact_name' => 'Test Vendor Contact',
            'email' => '_test_vendor@test.com',
            'status' => 'active',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'company_name',
        'update_value' => '_Test Vendor Updated',
        'unique_field' => 'vendor_code'
    ],
    // Employee
    [
        'file' => 'Employee_model.php',
        'class' => 'Employee_model',
        'table' => 'employees',
        'data' => [
            'employee_code' => '_TEST_EMP_99',
            'first_name' => '_Test',
            'last_name' => '_Employee',
            'email' => '_test_emp@test.com',
            'date_of_hire' => '2025-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'first_name',
        'update_value' => '_TestUpdated',
        'unique_field' => 'employee_code'
    ],
    // Journal Entry
    [
        'file' => 'Journal_entry_model.php',
        'class' => 'Journal_entry_model',
        'table' => 'journal_entries',
        'data' => [
            'entry_number' => '_TEST_JE_99',
            'entry_date' => '2025-01-01',
            'description' => '_Test Journal Entry',
            'amount' => 1000.00,
            'status' => 'draft',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'description',
        'update_value' => '_Test JE Updated',
        'unique_field' => 'entry_number'
    ],
    // Product
    [
        'file' => 'Product_model.php',
        'class' => 'Product_model',
        'table' => 'products',
        'data' => [
            'product_code' => '_TEST_PROD_99',
            'product_name' => '_Test Product',
            'type' => 'product',
            'unit_price' => 500.00,
            'status' => 'active',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'product_name',
        'update_value' => '_Test Product Updated',
        'unique_field' => 'product_code'
    ],
    // Estimate
    [
        'file' => 'Estimate_model.php',
        'class' => 'Estimate_model',
        'table' => 'estimates',
        'data' => [
            'estimate_number' => '_TEST_EST_99',
            'customer_id' => 0, // Will be replaced dynamically
            'estimate_date' => '2025-06-01',
            'expiry_date' => '2025-07-01',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'status' => 'draft',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'status',
        'update_value' => 'sent',
        'unique_field' => 'estimate_number',
        'needs_customer' => true
    ],
    // Financial Year
    [
        'file' => 'Financial_year_model.php',
        'class' => 'Financial_year_model',
        'table' => 'financial_years',
        'data' => [
            'year_name' => '_Test FY 2099',
            'start_date' => '2099-01-01',
            'end_date' => '2099-12-31',
            'status' => 'open',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'year_name',
        'update_value' => '_Test FY Updated',
        'unique_field' => null
    ],
    // Facility
    [
        'file' => 'Facility_model.php',
        'class' => 'Facility_model',
        'table' => 'facilities',
        'data' => [
            'facility_code' => '_TEST_FAC_99',
            'facility_name' => '_Test Facility',
            'hourly_rate' => 100.00,
            'daily_rate' => 500.00,
            'status' => 'active',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'facility_name',
        'update_value' => '_Test Facility Updated',
        'unique_field' => 'facility_code'
    ],
    // Currency
    [
        'file' => 'Currency_model.php',
        'class' => 'Currency_model',
        'table' => 'currencies',
        'data' => [
            'currency_code' => '_TST',
            'currency_name' => '_Test Currency',
            'symbol' => 'T',
            'exchange_rate' => 1.000000,
            'is_base' => 0,
            'status' => 'active',
        ],
        'update_field' => 'currency_name',
        'update_value' => '_Test Currency Updated',
        'unique_field' => 'currency_code'
    ],
    // Module
    [
        'file' => 'Module_model.php',
        'class' => 'Module_model',
        'table' => 'modules',
        'data' => [
            'module_key' => '_test_crud_module',
            'display_name' => '_Test CRUD Module',
            'is_active' => 1,
            'sort_order' => 999,
            'icon' => 'bi-gear'
        ],
        'update_field' => 'display_name',
        'update_value' => '_Test Module Updated',
        'unique_field' => 'module_key'
    ],
    // Notification
    [
        'file' => 'Notification_model.php',
        'class' => 'Notification_model',
        'table' => 'notifications',
        'data' => [
            'type' => 'test',
            'title' => '_Test Notification',
            'message' => 'CRUD test notification',
            'is_read' => 0,
            'priority' => 'normal',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'is_read',
        'update_value' => 1,
        'unique_field' => null
    ],
    // Item
    [
        'file' => 'Item_model.php',
        'class' => 'Item_model',
        'table' => 'items',
        'data' => [
            'sku' => '_TEST_ITEM_99',
            'item_name' => '_Test Item',
            'item_type' => 'product',
            'item_status' => 'active',
            'created_at' => 'NOW()'
        ],
        'update_field' => 'item_name',
        'update_value' => '_Test Item Updated',
        'unique_field' => 'sku'
    ],
];

// ---------- Pre-cleanup: Remove any leftover test records ----------
echo "Pre-cleanup: removing leftover test records...\n";
foreach ($modelTests as $test) {
    $table = $test['table'];
    if (!tableExists($db, $prefix, $table)) continue;
    if (!empty($test['unique_field'])) {
        try {
            $db->query("DELETE FROM `{$prefix}{$table}` WHERE `{$test['unique_field']}` LIKE '_TEST_%' OR `{$test['unique_field']}` LIKE '_test_%' OR `{$test['unique_field']}` = '_TST'");
        } catch (Exception $e) { /* ignore */ }
    }
}
// Clean test notifications
try { $db->query("DELETE FROM `{$prefix}notifications` WHERE `title` LIKE '_Test%'"); } catch (Exception $e) {}
// Clean test financial years
try { $db->query("DELETE FROM `{$prefix}financial_years` WHERE `year_name` LIKE '_Test%'"); } catch (Exception $e) {}

echo "Pre-cleanup done.\n\n";

// ---------- Run CRUD Tests ----------
$testCustomerId = null; // For FK dependencies

foreach ($modelTests as $test) {
    $cat = $test['class'];
    $table = $test['table'];
    
    echo "── Testing {$cat} ({$table}) ──\n";
    
    // 1. Check table exists
    if (!tableExists($db, $prefix, $table)) {
        t_fail("{$cat}: Table `{$table}` does not exist", "Table missing from database", $cat);
        echo "   ✗ Table missing — skipping\n";
        continue;
    }
    t_pass("{$cat}: Table `{$table}` exists", $cat);
    
    // 2. Load model
    if (!loadModel($test['file'])) {
        t_fail("{$cat}: Model file not found", $test['file'], $cat);
        echo "   ✗ Model file missing — skipping\n";
        continue;
    }
    
    try {
        $model = new $test['class']();
        t_pass("{$cat}: Model instantiated", $cat);
    } catch (Exception $e) {
        t_fail("{$cat}: Model instantiation failed", $e->getMessage(), $cat);
        echo "   ✗ Cannot instantiate — skipping\n";
        continue;
    }
    
    // 3. Prepare test data
    $data = $test['data'];
    // Replace NOW() placeholders
    foreach ($data as $k => $v) {
        if ($v === 'NOW()') {
            $data[$k] = date('Y-m-d H:i:s');
        }
    }
    
    // Handle FK dependencies
    if (!empty($test['needs_customer'])) {
        if (!$testCustomerId) {
            // Create a test customer first
            try {
                $testCustomerId = $db->insert('customers', [
                    'customer_code' => '_TEST_FK_CUST',
                    'company_name' => '_FK Test Customer',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                // Try to find existing
                $existing = $db->fetchOne("SELECT id FROM `{$prefix}customers` WHERE customer_code = '_TEST_FK_CUST'");
                $testCustomerId = $existing ? $existing['id'] : null;
            }
        }
        if ($testCustomerId) {
            $data['customer_id'] = $testCustomerId;
        } else {
            t_warn("{$cat}: Skipping — no test customer available for FK", '', $cat);
            continue;
        }
    }
    
    // 4. CREATE
    $testId = null;
    try {
        $testId = $model->create($data);
        if ($testId) {
            t_pass("{$cat}: CREATE OK (ID: {$testId})", $cat);
        } else {
            t_fail("{$cat}: CREATE returned falsy", "create() returned: " . var_export($testId, true), $cat);
            continue;
        }
    } catch (Exception $e) {
        t_fail("{$cat}: CREATE error", $e->getMessage(), $cat);
        continue;
    }
    
    // 5. READ
    try {
        $record = $model->getById($testId);
        if ($record) {
            // Verify a key field
            $checkField = $test['update_field'];
            if (isset($record[$checkField]) && $record[$checkField] == $data[$checkField]) {
                t_pass("{$cat}: READ OK (verified {$checkField})", $cat);
            } else {
                t_pass("{$cat}: READ OK (record found)", $cat);
            }
        } else {
            t_fail("{$cat}: READ returned null/false", "getById({$testId}) returned empty", $cat);
        }
    } catch (Exception $e) {
        t_fail("{$cat}: READ error", $e->getMessage(), $cat);
    }
    
    // 6. UPDATE
    try {
        $updateResult = $model->update($testId, [$test['update_field'] => $test['update_value']]);
        $updated = $model->getById($testId);
        if ($updated && $updated[$test['update_field']] == $test['update_value']) {
            t_pass("{$cat}: UPDATE OK ({$test['update_field']} changed)", $cat);
        } else {
            t_fail("{$cat}: UPDATE did not persist", "Expected {$test['update_value']}, got " . ($updated[$test['update_field']] ?? 'null'), $cat);
        }
    } catch (Exception $e) {
        t_fail("{$cat}: UPDATE error", $e->getMessage(), $cat);
    }
    
    // 7. getAll (basic)
    try {
        $all = $model->getAll(5);
        if (is_array($all)) {
            t_pass("{$cat}: getAll() returns array (" . count($all) . " records)", $cat);
        } else {
            t_warn("{$cat}: getAll() did not return array", gettype($all), $cat);
        }
    } catch (Exception $e) {
        t_warn("{$cat}: getAll() error", $e->getMessage(), $cat);
    }
    
    // 8. Edge case: Duplicate unique key
    if (!empty($test['unique_field'])) {
        try {
            $dupData = $data;
            $dupData[$test['update_field']] = $test['update_value'] . ' Dup'; // Ensure other fields differ
            $dupId = $model->create($dupData);
            if ($dupId) {
                // Duplicate was allowed (unexpected for unique key)
                t_warn("{$cat}: Duplicate unique key was accepted (ID: {$dupId})", "Unique field: {$test['unique_field']}", $cat);
                // Cleanup the duplicate
                try { $model->delete($dupId); } catch (Exception $ex) {}
            } else {
                t_pass("{$cat}: Duplicate unique key correctly rejected", $cat);
            }
        } catch (Exception $e) {
            t_pass("{$cat}: Duplicate unique key correctly rejected (exception)", $cat);
        }
    }
    
    // 9. DELETE
    try {
        $model->delete($testId);
        $deleted = $model->getById($testId);
        if (!$deleted) {
            t_pass("{$cat}: DELETE OK (record removed)", $cat);
        } else {
            t_fail("{$cat}: DELETE did not remove record", "Record still found after delete", $cat);
        }
    } catch (Exception $e) {
        t_fail("{$cat}: DELETE error", $e->getMessage(), $cat);
    }
    
    echo "\n";
}

// ---------- Special Model Tests ----------

echo "── Special: Booking_model with FK ──\n";
$cat = 'Booking_model';
if (tableExists($db, $prefix, 'bookings') && tableExists($db, $prefix, 'facilities')) {
    loadModel('Booking_model.php');
    try {
        // Create facility first
        $testFacId = $db->insert('facilities', [
            'facility_code' => '_TEST_CRUD_FAC',
            'facility_name' => '_CRUD Test Facility',
            'hourly_rate' => 100,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $bookingModel = new Booking_model();
        $bookingId = $bookingModel->create([
            'booking_number' => '_TEST_CRUD_BK',
            'facility_id' => $testFacId,
            'customer_name' => '_CRUD Test',
            'customer_email' => '_crud@test.com',
            'booking_date' => date('Y-m-d', strtotime('+30 days')),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'booking_type' => 'hourly',
            'total_amount' => 5000.00,
            'paid_amount' => 0,
            'balance_amount' => 5000.00,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($bookingId) {
            t_pass("{$cat}: CREATE OK (ID: {$bookingId})", $cat);
            
            $read = $bookingModel->getById($bookingId);
            if ($read && $read['customer_name'] === '_CRUD Test') {
                t_pass("{$cat}: READ OK", $cat);
            } else {
                t_fail("{$cat}: READ failed", '', $cat);
            }
            
            $bookingModel->update($bookingId, ['status' => 'confirmed']);
            $updated = $bookingModel->getById($bookingId);
            if ($updated && $updated['status'] === 'confirmed') {
                t_pass("{$cat}: UPDATE OK", $cat);
            } else {
                t_fail("{$cat}: UPDATE failed", '', $cat);
            }
            
            // Edge: zero-amount booking
            $zeroBk = $bookingModel->create([
                'booking_number' => '_TEST_ZERO_BK',
                'facility_id' => $testFacId,
                'customer_name' => '_Zero Test',
                'booking_date' => date('Y-m-d', strtotime('+31 days')),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'total_amount' => 0,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            if (!$zeroBk) {
                t_pass("{$cat}: Zero-amount booking correctly rejected", $cat);
            } else {
                t_warn("{$cat}: Zero-amount booking was accepted (ID: {$zeroBk})", "Validation may be missing", $cat);
                try { $bookingModel->delete($zeroBk); } catch (Exception $e) {}
            }
            
            // Cleanup
            $bookingModel->delete($bookingId);
            $del = $bookingModel->getById($bookingId);
            if (!$del) { t_pass("{$cat}: DELETE OK", $cat); }
            else { t_fail("{$cat}: DELETE failed", '', $cat); }
        } else {
            t_fail("{$cat}: CREATE returned no ID", '', $cat);
        }
        
        // Cleanup facility
        try { $db->query("DELETE FROM `{$prefix}facilities` WHERE facility_code='_TEST_CRUD_FAC'"); } catch (Exception $e) {}
    } catch (Exception $e) {
        t_fail("{$cat}: Test error", $e->getMessage(), $cat);
        try { $db->query("DELETE FROM `{$prefix}bookings` WHERE booking_number LIKE '_TEST_%'"); } catch (Exception $ex) {}
        try { $db->query("DELETE FROM `{$prefix}facilities` WHERE facility_code='_TEST_CRUD_FAC'"); } catch (Exception $ex) {}
    }
} else {
    t_warn("{$cat}: Skipped — bookings or facilities table missing", '', $cat);
}

echo "\n── Special: Invoice_model with FK ──\n";
$cat = 'Invoice_model';
if (tableExists($db, $prefix, 'invoices') && tableExists($db, $prefix, 'customers')) {
    loadModel('Invoice_model.php');
    try {
        // Ensure test customer
        $custId = $testCustomerId;
        if (!$custId) {
            $custId = $db->insert('customers', [
                'customer_code' => '_TEST_INV_CUST',
                'company_name' => '_Invoice Test Cust',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        $invoiceModel = new Invoice_model();
        $invId = $invoiceModel->create([
            'invoice_number' => '_TEST_INV_99',
            'customer_id' => $custId,
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 5000.00,
            'total_amount' => 5000.00,
            'balance_amount' => 5000.00,
            'paid_amount' => 0,
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($invId) {
            t_pass("{$cat}: CREATE OK (ID: {$invId})", $cat);
            
            $read = $invoiceModel->getById($invId);
            if ($read && $read['invoice_number'] === '_TEST_INV_99') {
                t_pass("{$cat}: READ OK", $cat);
            } else {
                t_fail("{$cat}: READ failed", '', $cat);
            }
            
            // Test addPayment
            try {
                $payResult = $invoiceModel->addPayment($invId, 2000.00);
                $afterPay = $invoiceModel->getById($invId);
                if ($afterPay && floatval($afterPay['paid_amount']) == 2000.00) {
                    t_pass("{$cat}: addPayment OK (partial: 2000)", $cat);
                } else {
                    t_warn("{$cat}: addPayment amount mismatch", "Expected 2000, got " . ($afterPay['paid_amount'] ?? 'null'), $cat);
                }
                
                if ($afterPay && $afterPay['status'] === 'partially_paid') {
                    t_pass("{$cat}: Partial payment status correct", $cat);
                } else {
                    t_warn("{$cat}: Partial payment status unexpected", "Got: " . ($afterPay['status'] ?? 'null'), $cat);
                }
            } catch (Exception $e) {
                t_warn("{$cat}: addPayment error", $e->getMessage(), $cat);
            }
            
            // Test overpayment guard
            try {
                $invoiceModel->addPayment($invId, 5000.00); // 2000 + 5000 = 7000 > 5000
                $afterOver = $invoiceModel->getById($invId);
                if ($afterOver && $afterOver['status'] === 'overpaid') {
                    t_pass("{$cat}: Overpayment guard sets 'overpaid' status", $cat);
                } elseif ($afterOver && floatval($afterOver['paid_amount']) <= floatval($afterOver['total_amount'])) {
                    t_pass("{$cat}: Overpayment was capped at total", $cat);
                } else {
                    t_warn("{$cat}: Overpayment handling unclear", "paid=" . ($afterOver['paid_amount'] ?? '?') . " status=" . ($afterOver['status'] ?? '?'), $cat);
                }
            } catch (Exception $e) {
                t_warn("{$cat}: Overpayment test error", $e->getMessage(), $cat);
            }
            
            // Test addItem with empty description
            try {
                $emptyResult = $invoiceModel->addItem($invId, [
                    'item_description' => '',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'line_total' => 100
                ]);
                if (!$emptyResult) {
                    t_pass("{$cat}: Empty item description correctly rejected", $cat);
                } else {
                    t_warn("{$cat}: Empty item description was accepted", '', $cat);
                }
            } catch (Exception $e) {
                t_pass("{$cat}: Empty item description correctly rejected (exception)", $cat);
            }
            
            // Cleanup
            try { $db->query("DELETE FROM `{$prefix}invoice_items` WHERE invoice_id = ?", [$invId]); } catch (Exception $e) {}
            $invoiceModel->delete($invId);
            $del = $invoiceModel->getById($invId);
            if (!$del) { t_pass("{$cat}: DELETE OK", $cat); }
            else { t_fail("{$cat}: DELETE failed", '', $cat); }
        } else {
            t_fail("{$cat}: CREATE returned no ID", '', $cat);
        }
    } catch (Exception $e) {
        t_fail("{$cat}: Test error", $e->getMessage(), $cat);
        try { $db->query("DELETE FROM `{$prefix}invoices` WHERE invoice_number='_TEST_INV_99'"); } catch (Exception $ex) {}
    }
} else {
    t_warn("{$cat}: Skipped — invoices or customers table missing", '', $cat);
}

echo "\n── Special: Bill_model with FK ──\n";
$cat = 'Bill_model';
if (tableExists($db, $prefix, 'bills') && tableExists($db, $prefix, 'vendors')) {
    loadModel('Bill_model.php');
    try {
        // Create test vendor
        $vendId = $db->insert('vendors', [
            'vendor_code' => '_TEST_BILL_VEND',
            'company_name' => '_Bill Test Vendor',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $billModel = new Bill_model();
        $billId = $billModel->create([
            'bill_number' => '_TEST_BILL_99',
            'vendor_id' => $vendId,
            'bill_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 3000.00,
            'total_amount' => 3000.00,
            'balance_amount' => 3000.00,
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($billId) {
            t_pass("{$cat}: CREATE OK (ID: {$billId})", $cat);
            $read = $billModel->getById($billId);
            if ($read && $read['bill_number'] === '_TEST_BILL_99') { t_pass("{$cat}: READ OK", $cat); }
            else { t_fail("{$cat}: READ failed", '', $cat); }
            
            $billModel->update($billId, ['status' => 'received']);
            $upd = $billModel->getById($billId);
            if ($upd && $upd['status'] === 'received') { t_pass("{$cat}: UPDATE OK", $cat); }
            else { t_fail("{$cat}: UPDATE failed", '', $cat); }
            
            $billModel->delete($billId);
            if (!$billModel->getById($billId)) { t_pass("{$cat}: DELETE OK", $cat); }
            else { t_fail("{$cat}: DELETE failed", '', $cat); }
        } else {
            t_fail("{$cat}: CREATE returned no ID", '', $cat);
        }
        
        // Cleanup vendor
        try { $db->query("DELETE FROM `{$prefix}vendors` WHERE vendor_code='_TEST_BILL_VEND'"); } catch (Exception $e) {}
    } catch (Exception $e) {
        t_fail("{$cat}: Test error", $e->getMessage(), $cat);
        try { $db->query("DELETE FROM `{$prefix}bills` WHERE bill_number='_TEST_BILL_99'"); } catch (Exception $ex) {}
        try { $db->query("DELETE FROM `{$prefix}vendors` WHERE vendor_code='_TEST_BILL_VEND'"); } catch (Exception $ex) {}
    }
} else {
    t_warn("{$cat}: Skipped — bills or vendors table missing", '', $cat);
}

echo "\n── Special: Cash_account_model with FK ──\n";
$cat = 'Cash_account_model';
if (tableExists($db, $prefix, 'cash_accounts') && tableExists($db, $prefix, 'accounts')) {
    loadModel('Cash_account_model.php');
    try {
        // Create test account first
        $acctId = $db->insert('accounts', [
            'account_code' => '_TEST_CASH_ACCT',
            'account_name' => '_Cash Test Account',
            'account_type' => 'Assets',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $cashModel = new Cash_account_model();
        $cashId = $cashModel->create([
            'account_name' => '_Test Cash Account',
            'account_type' => 'bank_account',
            'account_id' => $acctId,
            'opening_balance' => 10000.00,
            'current_balance' => 10000.00,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($cashId) {
            t_pass("{$cat}: CREATE OK (ID: {$cashId})", $cat);
            $read = $cashModel->getById($cashId);
            if ($read) { t_pass("{$cat}: READ OK", $cat); }
            else { t_fail("{$cat}: READ failed", '', $cat); }
            
            $cashModel->update($cashId, ['current_balance' => 15000.00]);
            $upd = $cashModel->getById($cashId);
            if ($upd && floatval($upd['current_balance']) == 15000.00) { t_pass("{$cat}: UPDATE OK", $cat); }
            else { t_fail("{$cat}: UPDATE failed", '', $cat); }
            
            $cashModel->delete($cashId);
            if (!$cashModel->getById($cashId)) { t_pass("{$cat}: DELETE OK", $cat); }
            else { t_fail("{$cat}: DELETE failed", '', $cat); }
        } else {
            t_fail("{$cat}: CREATE returned no ID", '', $cat);
        }
        
        // Cleanup account
        try { $db->query("DELETE FROM `{$prefix}accounts` WHERE account_code='_TEST_CASH_ACCT'"); } catch (Exception $e) {}
    } catch (Exception $e) {
        t_fail("{$cat}: Test error", $e->getMessage(), $cat);
        try { $db->query("DELETE FROM `{$prefix}cash_accounts` WHERE account_name='_Test Cash Account'"); } catch (Exception $ex) {}
        try { $db->query("DELETE FROM `{$prefix}accounts` WHERE account_code='_TEST_CASH_ACCT'"); } catch (Exception $ex) {}
    }
} else {
    t_warn("{$cat}: Skipped — cash_accounts or accounts table missing", '', $cat);
}

// ---------- Final Cleanup ----------
echo "\n── Final Cleanup ──\n";
// Remove FK test customer
if ($testCustomerId) {
    try {
        $db->query("DELETE FROM `{$prefix}customers` WHERE customer_code IN ('_TEST_FK_CUST', '_TEST_INV_CUST')");
        echo "   Cleaned up test customers\n";
    } catch (Exception $e) {
        echo "   Warning: Could not clean test customer: " . $e->getMessage() . "\n";
    }
}

// Safety net: clean any remaining _TEST_ records
$cleanTables = ['accounts', 'customers', 'vendors', 'employees', 'journal_entries', 'products',
                 'estimates', 'financial_years', 'facilities', 'currencies', 'modules_settings',
                 'notifications', 'items', 'bookings', 'invoices', 'bills', 'cash_accounts'];
foreach ($cleanTables as $ct) {
    if (!tableExists($db, $prefix, $ct)) continue;
    try {
        // Use broad pattern to catch any test records
        $cols = $db->fetchAll("SHOW COLUMNS FROM `{$prefix}{$ct}`");
        $colNames = array_column($cols, 'Field');
        $codeFields = array_intersect($colNames, ['account_code','customer_code','vendor_code','employee_code',
            'entry_number','product_code','estimate_number','facility_code','currency_code','module_name',
            'item_code','booking_number','invoice_number','bill_number','account_name','title','year_name']);
        foreach ($codeFields as $cf) {
            $db->query("DELETE FROM `{$prefix}{$ct}` WHERE `{$cf}` LIKE '_TEST_%' OR `{$cf}` LIKE '_CRUD%' OR `{$cf}` = '_TST'");
        }
    } catch (Exception $e) { /* ignore */ }
}
echo "   Final cleanup done.\n";

// ---------- Generate Report ----------
$report = [
    'date' => date('Y-m-d H:i:s'),
    'summary' => $results,
    'errors' => $errors,
    'warnings' => $warnings,
    'details' => $details
];

$jsonPath = __DIR__ . '/crud_test_results.json';
file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));

echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  CRUD TEST RESULTS                                            ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";
printf("║  ✓ Passed:   %-3d                                              ║\n", $results['passed']);
printf("║  ✗ Failed:   %-3d                                              ║\n", $results['failed']);
printf("║  ⚠ Warnings: %-3d                                              ║\n", $results['warnings']);
echo "╠══════════════════════════════════════════════════════════════════╣\n";

if ($results['failed'] === 0) {
    echo "║  OVERALL: ALL CRUD TESTS PASSED ✓                             ║\n";
} else {
    echo "║  OVERALL: ISSUES FOUND — SEE ERRORS BELOW                     ║\n";
}
echo "╚══════════════════════════════════════════════════════════════════╝\n";

if (!empty($errors)) {
    echo "\n── FAILURES ──\n";
    foreach ($errors as $e) {
        echo "  ✗ [{$e['category']}] {$e['msg']}\n";
        if (!empty($e['details'])) echo "    → {$e['details']}\n";
    }
}

if (!empty($warnings)) {
    echo "\n── WARNINGS ──\n";
    foreach ($warnings as $w) {
        echo "  ⚠ [{$w['category']}] {$w['msg']}\n";
        if (!empty($w['details'])) echo "    → {$w['details']}\n";
    }
}

echo "\nResults saved to: {$jsonPath}\n";
exit($results['failed'] > 0 ? 1 : 0);
