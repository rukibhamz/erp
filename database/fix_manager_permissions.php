<?php
/**
 * Permission Seeder - Fix Manager Role Permissions
 * 
 * This script ensures the manager role has all required permissions
 * for critical business modules: accounting, bookings, properties, inventory, utilities
 * 
 * Run this script via command line or include it in your migration process
 */

defined('BASEPATH') OR exit('No direct script access allowed');

function fixManagerPermissions($db) {
    $prefix = $db->getPrefix();
    
    try {
        echo "Starting manager permissions fix...\n";
        
        // Step 1: Ensure all permissions exist
        $modules = ['accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings'];
        $permissions = ['read', 'write', 'delete', 'create', 'update'];
        
        $permissionsCreated = 0;
        foreach ($modules as $module) {
            foreach ($permissions as $perm) {
                // Check if permission exists
                $existing = $db->fetchOne(
                    "SELECT id FROM `{$prefix}permissions` WHERE module = ? AND permission = ?",
                    [$module, $perm]
                );
                
                if (!$existing) {
                    // Create permission
                    $description = ucfirst($perm) . ' ' . ucfirst($module);
                    try {
                        $db->query(
                            "INSERT INTO `{$prefix}permissions` (module, permission, description, created_at) VALUES (?, ?, ?, NOW())",
                            [$module, $perm, $description]
                        );
                        $permissionsCreated++;
                        echo "Created permission: {$module}.{$perm}\n";
                    } catch (Exception $e) {
                        // Ignore duplicate errors (INSERT IGNORE equivalent)
                        if (strpos($e->getMessage(), 'Duplicate') === false) {
                            echo "Warning creating permission {$module}.{$perm}: " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
        }
        
        echo "Created {$permissionsCreated} new permissions\n";
        
        // Step 2: Get manager role ID
        $managerRole = $db->fetchOne(
            "SELECT id FROM `{$prefix}roles` WHERE role_code = 'manager'"
        );
        
        if (!$managerRole) {
            throw new Exception("Manager role not found in roles table!");
        }
        
        $managerRoleId = $managerRole['id'];
        echo "Found manager role with ID: {$managerRoleId}\n";
        
        // Step 3: Assign permissions to manager role
        $permissionsAssigned = 0;
        foreach ($modules as $module) {
            foreach ($permissions as $perm) {
                // Get permission ID
                $permission = $db->fetchOne(
                    "SELECT id FROM `{$prefix}permissions` WHERE module = ? AND permission = ?",
                    [$module, $perm]
                );
                
                if ($permission) {
                    $permissionId = $permission['id'];
                    
                    // Check if already assigned
                    $existing = $db->fetchOne(
                        "SELECT id FROM `{$prefix}role_permissions` WHERE role_id = ? AND permission_id = ?",
                        [$managerRoleId, $permissionId]
                    );
                    
                    if (!$existing) {
                        // Assign permission
                        try {
                            $db->query(
                                "INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at) VALUES (?, ?, NOW())",
                                [$managerRoleId, $permissionId]
                            );
                            $permissionsAssigned++;
                            echo "Assigned permission: {$module}.{$perm} to manager role\n";
                        } catch (Exception $e) {
                            // Ignore duplicate errors
                            if (strpos($e->getMessage(), 'Duplicate') === false) {
                                echo "Warning assigning permission {$module}.{$perm}: " . $e->getMessage() . "\n";
                            }
                        }
                    }
                }
            }
        }
        
        echo "Assigned {$permissionsAssigned} permissions to manager role\n";
        
        // Step 4: Verification
        $verification = $db->fetchAll(
            "SELECT p.module, p.permission, r.role_code
             FROM `{$prefix}role_permissions` rp
             JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
             JOIN `{$prefix}roles` r ON rp.role_id = r.id
             WHERE r.role_code = 'manager' 
             AND p.module IN ('accounting', 'bookings', 'properties', 'inventory', 'utilities', 'settings')
             ORDER BY p.module, p.permission"
        );
        
        echo "\nVerification: Manager role now has " . count($verification) . " permissions\n";
        echo "Expected: At least 30 permissions (6 modules × 5 permissions each)\n\n";
        
        if (count($verification) >= 30) {
            echo "✓ SUCCESS: Manager role has all required permissions!\n";
            return true;
        } else {
            echo "⚠ WARNING: Manager role may be missing some permissions\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        error_log("Manager permissions fix error: " . $e->getMessage());
        return false;
    }
}

// If running directly (not included)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/../../application/core/Database.php';
    require_once __DIR__ . '/../../application/config/config.php';
    
    $config = require __DIR__ . '/../../application/config/config.php';
    $db = Database::getInstance();
    
    fixManagerPermissions($db);
}

