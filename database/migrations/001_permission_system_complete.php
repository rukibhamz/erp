<?php
/**
 * COMPLETE PERMISSION SYSTEM MIGRATION
 * 
 * This is the COMPLETE PHP migration for the permission system
 * Creates all tables, seeds all data, and assigns permissions to all roles
 * IDEMPOTENT - Safe to run multiple times
 * 
 * Usage: php database/migrations/001_permission_system_complete.php
 */

defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../../application/core/Database.php';
require_once __DIR__ . '/../../application/config/config.php';

function runCompletePermissionMigration() {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $prefix = $db->getPrefix();
        
        if (!$pdo) {
            throw new Exception("Database connection not available");
        }
        
        echo "========================================\n";
        echo "COMPLETE PERMISSION SYSTEM MIGRATION\n";
        echo "========================================\n\n";
        
        // Disable foreign key checks temporarily
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Step 1: Create erp_permissions table
        echo "Step 1: Creating erp_permissions table...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}permissions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `module` VARCHAR(100) NOT NULL,
            `permission` VARCHAR(50) NOT NULL COMMENT 'read, write, delete, create, update',
            `description` VARCHAR(255) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_module_permission` (`module`, `permission`),
            KEY `idx_module` (`module`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✓ erp_permissions table created/verified\n\n";
        
        // Step 2: Create erp_roles table
        echo "Step 2: Creating erp_roles table...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}roles` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `role_name` VARCHAR(100) NOT NULL,
            `role_code` VARCHAR(50) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `is_system` TINYINT(1) DEFAULT 0 COMMENT 'System roles cannot be deleted',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_role_code` (`role_code`),
            KEY `idx_role_name` (`role_name`),
            KEY `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✓ erp_roles table created/verified\n\n";
        
        // Step 3: Create erp_role_permissions table
        echo "Step 3: Creating erp_role_permissions table (CRITICAL)...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}role_permissions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `role_id` INT(11) NOT NULL,
            `permission_id` INT(11) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
            KEY `idx_role_id` (`role_id`),
            KEY `idx_permission_id` (`permission_id`),
            CONSTRAINT `fk_role_permissions_role` 
                FOREIGN KEY (`role_id`) REFERENCES `{$prefix}roles` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_role_permissions_permission` 
                FOREIGN KEY (`permission_id`) REFERENCES `{$prefix}permissions` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✓ erp_role_permissions table created/verified\n\n";
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Step 4: Insert default roles
        echo "Step 4: Inserting system roles...\n";
        $roles = [
            ['role_name' => 'Super Admin', 'role_code' => 'super_admin', 'description' => 'Full system access with all permissions', 'is_system' => 1],
            ['role_name' => 'Admin', 'role_code' => 'admin', 'description' => 'Administrator with system access', 'is_system' => 1],
            ['role_name' => 'Manager', 'role_code' => 'manager', 'description' => 'Management role with full business module access', 'is_system' => 1],
            ['role_name' => 'Staff', 'role_code' => 'staff', 'description' => 'Staff level access', 'is_system' => 1],
            ['role_name' => 'User', 'role_code' => 'user', 'description' => 'Basic user role', 'is_system' => 1],
            ['role_name' => 'Accountant', 'role_code' => 'accountant', 'description' => 'Accounting focused role', 'is_system' => 0],
        ];
        
        $rolesInserted = 0;
        foreach ($roles as $role) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}roles` 
                (role_name, role_code, description, is_system, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())");
            $stmt->execute([
                $role['role_name'],
                $role['role_code'],
                $role['description'],
                $role['is_system']
            ]);
            if ($stmt->rowCount() > 0) {
                $rolesInserted++;
            }
        }
        echo "✓ Inserted {$rolesInserted} new roles\n\n";
        
        // Step 5: Insert all permissions
        echo "Step 5: Inserting permissions...\n";
        $permissions = [
            // Accounting
            ['accounting', 'read', 'View accounting data'],
            ['accounting', 'write', 'Create/edit accounting entries'],
            ['accounting', 'delete', 'Delete accounting entries'],
            ['accounting', 'create', 'Create accounting entries'],
            ['accounting', 'update', 'Update accounting entries'],
            // Bookings
            ['bookings', 'read', 'View bookings'],
            ['bookings', 'write', 'Create/edit bookings'],
            ['bookings', 'delete', 'Delete bookings'],
            ['bookings', 'create', 'Create bookings'],
            ['bookings', 'update', 'Update bookings'],
            // Properties
            ['properties', 'read', 'View properties'],
            ['properties', 'write', 'Create/edit properties'],
            ['properties', 'delete', 'Delete properties'],
            ['properties', 'create', 'Create properties'],
            ['properties', 'update', 'Update properties'],
            // Inventory
            ['inventory', 'read', 'View inventory'],
            ['inventory', 'write', 'Create/edit inventory'],
            ['inventory', 'delete', 'Delete inventory'],
            ['inventory', 'create', 'Create inventory'],
            ['inventory', 'update', 'Update inventory'],
            // Utilities
            ['utilities', 'read', 'View utilities'],
            ['utilities', 'write', 'Create/edit utilities'],
            ['utilities', 'delete', 'Delete utilities'],
            ['utilities', 'create', 'Create utilities'],
            ['utilities', 'update', 'Update utilities'],
            // Settings
            ['settings', 'read', 'View settings'],
            ['settings', 'write', 'Create/edit settings'],
            ['settings', 'delete', 'Delete settings'],
            ['settings', 'create', 'Create settings'],
            ['settings', 'update', 'Update settings'],
            // Dashboard
            ['dashboard', 'read', 'View dashboard'],
            // Notifications
            ['notifications', 'read', 'View notifications'],
            ['notifications', 'write', 'Create/edit notifications'],
            ['notifications', 'delete', 'Delete notifications'],
            // Users
            ['users', 'read', 'View users'],
            ['users', 'write', 'Create/edit users'],
            ['users', 'delete', 'Delete users'],
            ['users', 'create', 'Create users'],
            ['users', 'update', 'Update users'],
            // Companies
            ['companies', 'read', 'View companies'],
            ['companies', 'write', 'Create/edit companies'],
            ['companies', 'delete', 'Delete companies'],
            // Reports
            ['reports', 'read', 'View reports'],
            ['reports', 'write', 'Create/edit reports'],
            // Modules
            ['modules', 'read', 'View modules'],
            ['modules', 'write', 'Create/edit modules'],
            // Accounting Sub-modules
            ['accounts', 'read', 'View chart of accounts'],
            ['accounts', 'write', 'Create/edit accounts'],
            ['accounts', 'delete', 'Delete accounts'],
            ['accounts', 'create', 'Create accounts'],
            ['accounts', 'update', 'Update accounts'],
            ['cash', 'read', 'View cash management'],
            ['cash', 'write', 'Create/edit cash transactions'],
            ['cash', 'delete', 'Delete cash transactions'],
            ['cash', 'create', 'Create cash transactions'],
            ['cash', 'update', 'Update cash transactions'],
            ['receivables', 'read', 'View receivables'],
            ['receivables', 'write', 'Create/edit receivables'],
            ['receivables', 'delete', 'Delete receivables'],
            ['receivables', 'create', 'Create receivables'],
            ['receivables', 'update', 'Update receivables'],
            ['payables', 'read', 'View payables'],
            ['payables', 'write', 'Create/edit payables'],
            ['payables', 'delete', 'Delete payables'],
            ['payables', 'create', 'Create payables'],
            ['payables', 'update', 'Update payables'],
            ['ledger', 'read', 'View general ledger'],
            ['ledger', 'write', 'Create/edit ledger entries'],
            ['ledger', 'delete', 'Delete ledger entries'],
            ['ledger', 'create', 'Create ledger entries'],
            ['ledger', 'update', 'Update ledger entries'],
            ['estimates', 'read', 'View estimates'],
            ['estimates', 'write', 'Create/edit estimates'],
            ['estimates', 'delete', 'Delete estimates'],
            ['estimates', 'create', 'Create estimates'],
            ['estimates', 'update', 'Update estimates'],
            // POS Module
            ['pos', 'read', 'View POS'],
            ['pos', 'write', 'Create/edit POS transactions'],
            ['pos', 'delete', 'Delete POS transactions'],
            ['pos', 'create', 'Create POS transactions'],
            ['pos', 'update', 'Update POS transactions'],
        ];
        
        $permissionsInserted = 0;
        foreach ($permissions as $perm) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}permissions` 
                (module, permission, description, created_at) 
                VALUES (?, ?, ?, NOW())");
            $stmt->execute($perm);
            if ($stmt->rowCount() > 0) {
                $permissionsInserted++;
            }
        }
        echo "✓ Inserted {$permissionsInserted} new permissions\n\n";
        
        // Step 6: Assign all permissions to super_admin
        echo "Step 6: Assigning all permissions to super_admin role...\n";
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
            SELECT r.id, p.id, NOW()
            FROM `{$prefix}roles` r
            CROSS JOIN `{$prefix}permissions` p
            WHERE r.role_code = 'super_admin'
            AND NOT EXISTS (
                SELECT 1 FROM `{$prefix}role_permissions` rp
                WHERE rp.role_id = r.id AND rp.permission_id = p.id
            )");
        $stmt->execute();
        $superAdminPerms = $stmt->rowCount();
        echo "✓ Assigned {$superAdminPerms} permissions to super_admin role\n\n";
        
        // Step 7: Assign all permissions to admin
        echo "Step 7: Assigning all permissions to admin role...\n";
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
            SELECT r.id, p.id, NOW()
            FROM `{$prefix}roles` r
            CROSS JOIN `{$prefix}permissions` p
            WHERE r.role_code = 'admin'
            AND NOT EXISTS (
                SELECT 1 FROM `{$prefix}role_permissions` rp
                WHERE rp.role_id = r.id AND rp.permission_id = p.id
            )");
        $stmt->execute();
        $adminPerms = $stmt->rowCount();
        echo "✓ Assigned {$adminPerms} permissions to admin role\n\n";
        
        // Step 8: Assign business module permissions to manager (including accounting sub-modules and POS, excluding tax)
        echo "Step 8: Assigning business module permissions to manager role...\n";
        // First, remove tax permissions from manager (if any exist)
        $stmt = $pdo->prepare("DELETE rp FROM `{$prefix}role_permissions` rp
            JOIN `{$prefix}roles` r ON rp.role_id = r.id
            JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
            WHERE r.role_code = 'manager'
            AND p.module = 'tax'");
        $stmt->execute();
        $deletedTax = $stmt->rowCount();
        if ($deletedTax > 0) {
            echo "✓ Removed {$deletedTax} tax permissions from manager role\n";
        }
        
        // Assign all business module permissions (excluding tax)
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
            SELECT r.id, p.id, NOW()
            FROM `{$prefix}roles` r
            CROSS JOIN `{$prefix}permissions` p
            WHERE r.role_code = 'manager'
            AND p.module IN ('accounting', 'accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates', 'pos', 'bookings', 'properties', 'inventory', 'utilities', 'settings', 'dashboard', 'notifications')
            AND NOT EXISTS (
                SELECT 1 FROM `{$prefix}role_permissions` rp
                WHERE rp.role_id = r.id AND rp.permission_id = p.id
            )");
        $stmt->execute();
        $managerPerms = $stmt->rowCount();
        echo "✓ Assigned {$managerPerms} permissions to manager role\n\n";
        
        // Step 9: Assign permissions to staff (POS, Bookings, Inventory, Utilities with read, update, create)
        echo "Step 9: Assigning permissions to staff role...\n";
        // Remove any existing staff permissions first (clean slate)
        $stmt = $pdo->prepare("DELETE rp FROM `{$prefix}role_permissions` rp
            JOIN `{$prefix}roles` r ON rp.role_id = r.id
            WHERE r.role_code = 'staff'");
        $stmt->execute();
        
        // Grant read and update permissions for POS, Bookings, Inventory, Utilities, Dashboard, Notifications
        $modules = ['pos', 'bookings', 'inventory', 'utilities', 'dashboard', 'notifications'];
        $permissions = ['read', 'update'];
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
            SELECT r.id, p.id, NOW()
            FROM `{$prefix}roles` r
            CROSS JOIN `{$prefix}permissions` p
            WHERE r.role_code = 'staff'
            AND p.module = ?
            AND p.permission = ?
            AND NOT EXISTS (
                SELECT 1 FROM `{$prefix}role_permissions` rp
                WHERE rp.role_id = r.id AND rp.permission_id = p.id
            )");
        
        $added = 0;
        foreach ($modules as $module) {
            foreach ($permissions as $permission) {
                $stmt->execute([$module, $permission]);
                $added += $stmt->rowCount();
            }
        }
        echo "✓ Added {$added} read/update permissions to staff role\n";
        
        // Grant create permissions for POS, Bookings, Inventory, Utilities
        $createModules = ['pos', 'bookings', 'inventory', 'utilities'];
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
            SELECT r.id, p.id, NOW()
            FROM `{$prefix}roles` r
            CROSS JOIN `{$prefix}permissions` p
            WHERE r.role_code = 'staff'
            AND p.module = ?
            AND p.permission = 'create'
            AND NOT EXISTS (
                SELECT 1 FROM `{$prefix}role_permissions` rp
                WHERE rp.role_id = r.id AND rp.permission_id = p.id
            )");
        
        $createAdded = 0;
        foreach ($createModules as $module) {
            $stmt->execute([$module]);
            $createAdded += $stmt->rowCount();
        }
        echo "✓ Added {$createAdded} create permissions to staff role\n\n";
        
        // Step 10: Assign accounting permissions to accountant
        echo "Step 10: Assigning accounting permissions to accountant role...\n";
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
            SELECT r.id, p.id, NOW()
            FROM `{$prefix}roles` r
            CROSS JOIN `{$prefix}permissions` p
            WHERE r.role_code = 'accountant'
            AND p.module = 'accounting'
            AND NOT EXISTS (
                SELECT 1 FROM `{$prefix}role_permissions` rp
                WHERE rp.role_id = r.id AND rp.permission_id = p.id
            )");
        $stmt->execute();
        $accountantPerms = $stmt->rowCount();
        echo "✓ Assigned {$accountantPerms} permissions to accountant role\n\n";
        
        // Verification
        echo "========================================\n";
        echo "VERIFICATION\n";
        echo "========================================\n";
        
        $roles = $pdo->query("SELECT role_code, role_name FROM `{$prefix}roles` ORDER BY role_code")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($roles as $role) {
            $permCount = $pdo->query("SELECT COUNT(*) as count FROM `{$prefix}role_permissions` rp
                JOIN `{$prefix}roles` r ON rp.role_id = r.id
                WHERE r.role_code = '{$role['role_code']}'")->fetch(PDO::FETCH_ASSOC);
            echo "✓ {$role['role_name']} ({$role['role_code']}): {$permCount['count']} permissions\n";
        }
        
        $tableCheck = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name IN ('{$prefix}permissions', '{$prefix}roles', '{$prefix}role_permissions')")->fetch(PDO::FETCH_ASSOC);
        echo "\n✓ {$tableCheck['count']} permission tables exist\n";
        
        echo "\n========================================\n";
        echo "MIGRATION COMPLETE!\n";
        echo "========================================\n";
        echo "All permission system tables created and populated.\n";
        echo "All roles have been assigned appropriate permissions.\n";
        echo "\nNext step: Run test_permission_system.php to verify all roles.\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "\n✗ ERROR: " . $e->getMessage() . "\n";
        error_log("Permission migration error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    runCompletePermissionMigration();
}

