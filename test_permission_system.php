<?php
/**
 * COMPREHENSIVE PERMISSION SYSTEM TESTING
 * 
 * Tests all roles and their permissions to ensure the system is working correctly
 * Run this after the migration to verify everything works
 * 
 * Usage: php test_permission_system.php
 */

defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/application/core/Database.php';
require_once __DIR__ . '/application/config/config.php';
require_once __DIR__ . '/application/models/User_permission_model.php';
require_once __DIR__ . '/application/core/Base_Model.php';

$db = Database::getInstance();
$prefix = $db->getPrefix();

echo "========================================\n";
echo "PERMISSION SYSTEM COMPREHENSIVE TEST\n";
echo "========================================\n\n";

$allTestsPassed = true;
$testResults = [];

// Test modules and permissions to check
$testModules = [
    'accounting' => ['read', 'write', 'delete', 'create', 'update'],
    'bookings' => ['read', 'write', 'delete', 'create', 'update'],
    'properties' => ['read', 'write', 'delete', 'create', 'update'],
    'inventory' => ['read', 'write', 'delete', 'create', 'update'],
    'utilities' => ['read', 'write', 'delete', 'create', 'update'],
    'settings' => ['read', 'write', 'delete', 'create', 'update'],
    'dashboard' => ['read'],
    'notifications' => ['read', 'write', 'delete'],
];

// Roles to test
$rolesToTest = ['super_admin', 'admin', 'manager', 'staff', 'user', 'accountant'];

// Test 1: Check tables exist
echo "TEST 1: Checking required tables exist...\n";
$tables = ['permissions', 'roles', 'role_permissions'];
$tablesExist = true;
foreach ($tables as $table) {
    $exists = $db->fetchOne(
        "SELECT COUNT(*) as count FROM information_schema.tables 
         WHERE table_schema = DATABASE() 
         AND table_name = ?",
        ["{$prefix}{$table}"]
    );
    if (($exists['count'] ?? 0) > 0) {
        echo "  ✓ {$prefix}{$table} exists\n";
    } else {
        echo "  ✗ {$prefix}{$table} MISSING!\n";
        $tablesExist = false;
        $allTestsPassed = false;
    }
}
echo "\n";

if (!$tablesExist) {
    echo "CRITICAL: Required tables are missing!\n";
    echo "Please run: php database/migrations/001_permission_system_complete.php\n\n";
    exit(1);
}

// Test 2: Check roles exist
echo "TEST 2: Checking roles exist...\n";
foreach ($rolesToTest as $roleCode) {
    $role = $db->fetchOne(
        "SELECT * FROM `{$prefix}roles` WHERE role_code = ?",
        [$roleCode]
    );
    if ($role) {
        echo "  ✓ Role '{$roleCode}' exists (ID: {$role['id']})\n";
    } else {
        echo "  ✗ Role '{$roleCode}' MISSING!\n";
        $allTestsPassed = false;
    }
}
echo "\n";

// Test 3: Check permissions exist
echo "TEST 3: Checking permissions exist...\n";
$totalPerms = 0;
foreach ($testModules as $module => $permissions) {
    foreach ($permissions as $perm) {
        $exists = $db->fetchOne(
            "SELECT id FROM `{$prefix}permissions` WHERE module = ? AND permission = ?",
            [$module, $perm]
        );
        if ($exists) {
            $totalPerms++;
        } else {
            echo "  ✗ Permission '{$module}.{$perm}' MISSING!\n";
            $allTestsPassed = false;
        }
    }
}
echo "  ✓ Found {$totalPerms} permissions\n\n";

// Test 4: Check role permissions assignment
echo "TEST 4: Checking role permissions assignment...\n";
$permissionModel = new User_permission_model();

foreach ($rolesToTest as $roleCode) {
    $role = $db->fetchOne("SELECT * FROM `{$prefix}roles` WHERE role_code = ?", [$roleCode]);
    if (!$role) continue;
    
    $permCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM `{$prefix}role_permissions` WHERE role_id = ?",
        [$role['id']]
    );
    
    echo "  {$role['role_name']} ({$roleCode}): {$permCount['count']} permissions assigned\n";
    
    // Expected permission counts
    $expectedCounts = [
        'super_admin' => 50,  // All permissions
        'admin' => 50,         // All permissions
        'manager' => 40,       // Business modules
        'staff' => 4,          // Read only for 4 modules
        'user' => 0,           // No default permissions
        'accountant' => 5,     // Accounting module only
    ];
    
    $expected = $expectedCounts[$roleCode] ?? 0;
    if ($permCount['count'] >= $expected) {
        echo "    ✓ Has expected permissions (expected: {$expected}+)\n";
    } else {
        echo "    ⚠ May be missing permissions (expected: {$expected}, found: {$permCount['count']})\n";
    }
}
echo "\n";

// Test 5: Test permission checks for each role
echo "TEST 5: Testing permission checks for each role...\n";
echo "Creating test users for each role...\n";

// Create or get test users
$testUsers = [];
foreach ($rolesToTest as $roleCode) {
    // Check if test user exists
    $testUser = $db->fetchOne(
        "SELECT * FROM `{$prefix}users` WHERE username = ?",
        ["test_{$roleCode}"]
    );
    
    if (!$testUser) {
        // Create test user (password: test123)
        $passwordHash = password_hash('test123', PASSWORD_BCRYPT);
        try {
            $db->query(
                "INSERT INTO `{$prefix}users` (username, email, password, role, status, created_at) 
                 VALUES (?, ?, ?, ?, 'active', NOW())",
                ["test_{$roleCode}", "test_{$roleCode}@test.com", $passwordHash, $roleCode]
            );
            $testUser = $db->fetchOne(
                "SELECT * FROM `{$prefix}users` WHERE username = ?",
                ["test_{$roleCode}"]
            );
            echo "  ✓ Created test user: test_{$roleCode} (ID: {$testUser['id']})\n";
        } catch (Exception $e) {
            echo "  ✗ Failed to create test user for {$roleCode}: " . $e->getMessage() . "\n";
            continue;
        }
    } else {
        echo "  ✓ Using existing test user: test_{$roleCode} (ID: {$testUser['id']})\n";
    }
    
    $testUsers[$roleCode] = $testUser;
}
echo "\n";

// Test permission checks
echo "TEST 6: Testing permission checks...\n";
$permissionModel = new User_permission_model();

foreach ($testUsers as $roleCode => $user) {
    echo "\n  Testing role: {$roleCode} (User ID: {$user['id']})\n";
    
    $roleResults = [];
    foreach ($testModules as $module => $permissions) {
        foreach ($permissions as $perm) {
            try {
                $hasPermission = $permissionModel->hasPermission($user['id'], $module, $perm);
                $roleResults["{$module}.{$perm}"] = $hasPermission;
                
                // Determine if this should have permission
                $shouldHave = false;
                if ($roleCode === 'super_admin' || $roleCode === 'admin') {
                    $shouldHave = true; // All permissions
                } elseif ($roleCode === 'manager') {
                    $shouldHave = in_array($module, ['accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings', 'dashboard', 'notifications']);
                } elseif ($roleCode === 'staff') {
                    $shouldHave = ($perm === 'read' && in_array($module, ['dashboard', 'notifications', 'bookings', 'properties']));
                } elseif ($roleCode === 'accountant') {
                    $shouldHave = ($module === 'accounting');
                }
                // 'user' role should have no permissions
                
                if ($hasPermission === $shouldHave) {
                    // echo "    ✓ {$module}.{$perm}: " . ($hasPermission ? 'YES' : 'NO') . " (correct)\n";
                } else {
                    echo "    ✗ {$module}.{$perm}: Expected " . ($shouldHave ? 'YES' : 'NO') . ", got " . ($hasPermission ? 'YES' : 'NO') . "\n";
                    $allTestsPassed = false;
                }
            } catch (Exception $e) {
                echo "    ✗ {$module}.{$perm}: ERROR - " . $e->getMessage() . "\n";
                $allTestsPassed = false;
            }
        }
    }
    
    $testResults[$roleCode] = $roleResults;
    
    // Summary for this role
    $granted = count(array_filter($roleResults));
    $total = count($roleResults);
    echo "    Summary: {$granted}/{$total} permissions granted\n";
}

// Final summary
echo "\n========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";

if ($allTestsPassed) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "\nThe permission system is working correctly.\n";
    echo "All roles have the expected permissions.\n";
    echo "\nReady for production deployment!\n";
} else {
    echo "⚠ SOME TESTS FAILED\n";
    echo "\nPlease review the errors above.\n";
    echo "If tables are missing, run: php database/migrations/001_permission_system_complete.php\n";
}

echo "\n========================================\n";
echo "ROLE PERMISSION SUMMARY\n";
echo "========================================\n";
foreach ($rolesToTest as $roleCode) {
    if (isset($testResults[$roleCode])) {
        $granted = count(array_filter($testResults[$roleCode]));
        $total = count($testResults[$roleCode]);
        echo "{$roleCode}: {$granted}/{$total} permissions\n";
    }
}

echo "\n";

