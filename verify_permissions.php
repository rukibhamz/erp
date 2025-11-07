<?php
/**
 * Permission Verification Script
 * 
 * Run this script to verify that the permission fix is working correctly
 * Usage: php verify_permissions.php
 */

defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/application/core/Database.php';
require_once __DIR__ . '/application/config/config.php';

$db = Database::getInstance();
$prefix = $db->getPrefix();

echo "========================================\n";
echo "PERMISSION FIX VERIFICATION\n";
echo "========================================\n\n";

// Test 1: Check if manager role exists
echo "Test 1: Checking if manager role exists...\n";
$managerRole = $db->fetchOne(
    "SELECT * FROM `{$prefix}roles` WHERE role_code = 'manager'"
);

if ($managerRole) {
    echo "✓ Manager role found (ID: {$managerRole['id']}, Name: {$managerRole['role_name']})\n\n";
} else {
    echo "✗ ERROR: Manager role not found!\n\n";
    exit(1);
}

// Test 2: Check required modules have permissions
echo "Test 2: Checking if required permissions exist...\n";
$requiredModules = ['accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings'];
$requiredPermissions = ['read', 'write', 'delete', 'create', 'update'];
$missingPermissions = [];

foreach ($requiredModules as $module) {
    foreach ($requiredPermissions as $perm) {
        $exists = $db->fetchOne(
            "SELECT id FROM `{$prefix}permissions` WHERE module = ? AND permission = ?",
            [$module, $perm]
        );
        if (!$exists) {
            $missingPermissions[] = "{$module}.{$perm}";
        }
    }
}

if (empty($missingPermissions)) {
    echo "✓ All required permissions exist\n\n";
} else {
    echo "✗ Missing permissions:\n";
    foreach ($missingPermissions as $perm) {
        echo "  - {$perm}\n";
    }
    echo "\n";
}

// Test 3: Check manager role has permissions assigned
echo "Test 3: Checking manager role permissions...\n";
$managerPermissions = $db->fetchAll(
    "SELECT p.module, p.permission
     FROM `{$prefix}role_permissions` rp
     JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
     JOIN `{$prefix}roles` r ON rp.role_id = r.id
     WHERE r.role_code = 'manager'
     AND p.module IN ('accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings')
     ORDER BY p.module, p.permission"
);

$permissionCount = count($managerPermissions);
$expectedCount = count($requiredModules) * count($requiredPermissions); // 6 * 5 = 30

echo "Found {$permissionCount} permissions assigned to manager role\n";
echo "Expected: {$expectedCount} permissions\n";

if ($permissionCount >= $expectedCount) {
    echo "✓ Manager role has all required permissions\n\n";
} else {
    echo "⚠ WARNING: Manager role may be missing some permissions\n";
    echo "Missing permissions:\n";
    
    // Find missing
    $assigned = [];
    foreach ($managerPermissions as $perm) {
        $assigned["{$perm['module']}.{$perm['permission']}"] = true;
    }
    
    foreach ($requiredModules as $module) {
        foreach ($requiredPermissions as $perm) {
            if (!isset($assigned["{$module}.{$perm}"])) {
                echo "  - {$module}.{$perm}\n";
            }
        }
    }
    echo "\n";
}

// Test 4: Check User ID 4
echo "Test 4: Checking User ID 4...\n";
$user4 = $db->fetchOne(
    "SELECT u.id, u.username, u.email, u.role as user_role, r.role_code, r.role_name
     FROM `{$prefix}users` u
     LEFT JOIN `{$prefix}roles` r ON u.role = r.role_code
     WHERE u.id = 4"
);

if ($user4) {
    echo "✓ User ID 4 found:\n";
    echo "  - Username: {$user4['username']}\n";
    echo "  - Email: {$user4['email']}\n";
    echo "  - Role: {$user4['user_role']}\n";
    if ($user4['role_code'] === 'manager') {
        echo "  - Role verified: {$user4['role_name']}\n\n";
    } else {
        echo "  - ⚠ WARNING: User role '{$user4['user_role']}' does not match manager role code\n\n";
    }
} else {
    echo "✗ User ID 4 not found\n\n";
}

// Test 5: Test permission check via role
if ($user4 && $user4['user_role'] === 'manager') {
    echo "Test 5: Testing permission check for User ID 4 via role...\n";
    
    $testModules = ['settings', 'accounting', 'bookings'];
    $testPermission = 'read';
    
    foreach ($testModules as $module) {
        $hasPermission = $db->fetchOne(
            "SELECT COUNT(*) as count
             FROM `{$prefix}role_permissions` rp
             INNER JOIN `{$prefix}roles` r ON rp.role_id = r.id
             INNER JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
             WHERE r.role_code = ? AND p.module = ? AND p.permission = ?",
            [$user4['user_role'], $module, $testPermission]
        );
        
        if (($hasPermission['count'] ?? 0) > 0) {
            echo "  ✓ User 4 has '{$module}.{$testPermission}' permission via role\n";
        } else {
            echo "  ✗ User 4 does NOT have '{$module}.{$testPermission}' permission via role\n";
        }
    }
    echo "\n";
}

// Test 6: Load User_permission_model and test hasPermission()
echo "Test 6: Testing User_permission_model->hasPermission()...\n";
require_once __DIR__ . '/application/models/User_permission_model.php';
require_once __DIR__ . '/application/core/Base_Model.php';

if ($user4) {
    $permissionModel = new User_permission_model();
    
    $testCases = [
        ['module' => 'settings', 'permission' => 'read'],
        ['module' => 'accounting', 'permission' => 'read'],
        ['module' => 'bookings', 'permission' => 'read'],
    ];
    
    foreach ($testCases as $test) {
        $result = $permissionModel->hasPermission(4, $test['module'], $test['permission']);
        if ($result) {
            echo "  ✓ hasPermission(4, '{$test['module']}', '{$test['permission']}') = TRUE\n";
        } else {
            echo "  ✗ hasPermission(4, '{$test['module']}', '{$test['permission']}') = FALSE\n";
        }
    }
    echo "\n";
}

// Summary
echo "========================================\n";
echo "VERIFICATION SUMMARY\n";
echo "========================================\n";

$allTestsPassed = true;

if (!$managerRole) {
    echo "✗ Manager role not found\n";
    $allTestsPassed = false;
}

if (!empty($missingPermissions)) {
    echo "✗ Missing permissions in database\n";
    $allTestsPassed = false;
}

if ($permissionCount < $expectedCount) {
    echo "⚠ Manager role missing some permissions\n";
    $allTestsPassed = false;
}

if (!$user4) {
    echo "✗ User ID 4 not found\n";
    $allTestsPassed = false;
}

if ($allTestsPassed && $permissionCount >= $expectedCount) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "The permission fix appears to be working correctly.\n";
} else {
    echo "⚠ Some tests failed. Please review the output above.\n";
    echo "Run fix_manager_permissions.sql or database/fix_manager_permissions.php to fix issues.\n";
}

echo "========================================\n";

