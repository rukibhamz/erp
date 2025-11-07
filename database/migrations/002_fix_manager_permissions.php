<?php
/**
 * FIX MANAGER PERMISSIONS - Accounting Sub-modules, Remove Tax, Add POS
 * 
 * This migration fixes manager permissions:
 * 1. Adds all Accounting sub-module permissions
 * 2. Removes tax module permissions from manager
 * 3. Adds POS module permissions to manager
 * 
 * Usage: php database/migrations/002_fix_manager_permissions.php
 */

defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/../../application/core/Database.php';
require_once __DIR__ . '/../../application/config/config.php';

function fixManagerPermissions() {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $prefix = $db->getPrefix();
        
        if (!$pdo) {
            throw new Exception("Database connection not available");
        }
        
        echo "========================================\n";
        echo "FIXING MANAGER PERMISSIONS\n";
        echo "========================================\n\n";
        
        // Step 1: Insert Accounting sub-module permissions
        echo "Step 1: Inserting Accounting sub-module permissions...\n";
        $subModulePermissions = [
            // Accounts
            ['accounts', 'read', 'View chart of accounts'],
            ['accounts', 'write', 'Create/edit accounts'],
            ['accounts', 'delete', 'Delete accounts'],
            ['accounts', 'create', 'Create accounts'],
            ['accounts', 'update', 'Update accounts'],
            // Cash
            ['cash', 'read', 'View cash management'],
            ['cash', 'write', 'Create/edit cash transactions'],
            ['cash', 'delete', 'Delete cash transactions'],
            ['cash', 'create', 'Create cash transactions'],
            ['cash', 'update', 'Update cash transactions'],
            // Receivables
            ['receivables', 'read', 'View receivables'],
            ['receivables', 'write', 'Create/edit receivables'],
            ['receivables', 'delete', 'Delete receivables'],
            ['receivables', 'create', 'Create receivables'],
            ['receivables', 'update', 'Update receivables'],
            // Payables
            ['payables', 'read', 'View payables'],
            ['payables', 'write', 'Create/edit payables'],
            ['payables', 'delete', 'Delete payables'],
            ['payables', 'create', 'Create payables'],
            ['payables', 'update', 'Update payables'],
            // Ledger
            ['ledger', 'read', 'View general ledger'],
            ['ledger', 'write', 'Create/edit ledger entries'],
            ['ledger', 'delete', 'Delete ledger entries'],
            ['ledger', 'create', 'Create ledger entries'],
            ['ledger', 'update', 'Update ledger entries'],
            // Estimates
            ['estimates', 'read', 'View estimates'],
            ['estimates', 'write', 'Create/edit estimates'],
            ['estimates', 'delete', 'Delete estimates'],
            ['estimates', 'create', 'Create estimates'],
            ['estimates', 'update', 'Update estimates'],
            // POS
            ['pos', 'read', 'View POS'],
            ['pos', 'write', 'Create/edit POS transactions'],
            ['pos', 'delete', 'Delete POS transactions'],
            ['pos', 'create', 'Create POS transactions'],
            ['pos', 'update', 'Update POS transactions'],
        ];
        
        $permsInserted = 0;
        foreach ($subModulePermissions as $perm) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}permissions` 
                (module, permission, description, created_at) 
                VALUES (?, ?, ?, NOW())");
            $stmt->execute($perm);
            if ($stmt->rowCount() > 0) {
                $permsInserted++;
            }
        }
        echo "✓ Inserted {$permsInserted} new permissions\n\n";
        
        // Step 2: Remove tax permissions from manager
        echo "Step 2: Removing tax permissions from manager role...\n";
        $stmt = $pdo->prepare("DELETE rp FROM `{$prefix}role_permissions` rp
            JOIN `{$prefix}roles` r ON rp.role_id = r.id
            JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
            WHERE r.role_code = 'manager'
            AND p.module = 'tax'");
        $stmt->execute();
        $taxRemoved = $stmt->rowCount();
        echo "✓ Removed {$taxRemoved} tax permissions from manager\n\n";
        
        // Step 3: Add Accounting sub-module permissions to manager
        echo "Step 3: Adding Accounting sub-module permissions to manager...\n";
        $accountingSubModules = ['accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates'];
        $accountingPerms = 0;
        foreach ($accountingSubModules as $subModule) {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
                SELECT r.id, p.id, NOW()
                FROM `{$prefix}roles` r
                CROSS JOIN `{$prefix}permissions` p
                WHERE r.role_code = 'manager'
                AND p.module = ?
                AND NOT EXISTS (
                    SELECT 1 FROM `{$prefix}role_permissions` rp
                    WHERE rp.role_id = r.id AND rp.permission_id = p.id
                )");
            $stmt->execute([$subModule]);
            $accountingPerms += $stmt->rowCount();
        }
        echo "✓ Added {$accountingPerms} Accounting sub-module permissions to manager\n\n";
        
        // Step 4: Add POS permissions to manager
        echo "Step 4: Adding POS permissions to manager...\n";
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}role_permissions` (role_id, permission_id, created_at)
            SELECT r.id, p.id, NOW()
            FROM `{$prefix}roles` r
            CROSS JOIN `{$prefix}permissions` p
            WHERE r.role_code = 'manager'
            AND p.module = 'pos'
            AND NOT EXISTS (
                SELECT 1 FROM `{$prefix}role_permissions` rp
                WHERE rp.role_id = r.id AND rp.permission_id = p.id
            )");
        $stmt->execute();
        $posPerms = $stmt->rowCount();
        echo "✓ Added {$posPerms} POS permissions to manager\n\n";
        
        // Verification
        echo "========================================\n";
        echo "VERIFICATION\n";
        echo "========================================\n";
        
        // Check Accounting sub-modules
        $accountingCount = $pdo->query("SELECT COUNT(*) as count FROM `{$prefix}role_permissions` rp
            JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
            JOIN `{$prefix}roles` r ON rp.role_id = r.id
            WHERE r.role_code = 'manager'
            AND p.module IN ('accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates')")->fetch(PDO::FETCH_ASSOC);
        echo "✓ Manager has {$accountingCount['count']} Accounting sub-module permissions\n";
        
        // Check POS
        $posCount = $pdo->query("SELECT COUNT(*) as count FROM `{$prefix}role_permissions` rp
            JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
            JOIN `{$prefix}roles` r ON rp.role_id = r.id
            WHERE r.role_code = 'manager'
            AND p.module = 'pos'")->fetch(PDO::FETCH_ASSOC);
        echo "✓ Manager has {$posCount['count']} POS permissions\n";
        
        // Check tax removed
        $taxCount = $pdo->query("SELECT COUNT(*) as count FROM `{$prefix}role_permissions` rp
            JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
            JOIN `{$prefix}roles` r ON rp.role_id = r.id
            WHERE r.role_code = 'manager'
            AND p.module = 'tax'")->fetch(PDO::FETCH_ASSOC);
        echo "✓ Manager has {$taxCount['count']} tax permissions (should be 0)\n";
        
        echo "\n========================================\n";
        echo "MIGRATION COMPLETE!\n";
        echo "========================================\n";
        echo "Manager permissions updated:\n";
        echo "  ✓ Added Accounting sub-module permissions\n";
        echo "  ✓ Removed tax module permissions\n";
        echo "  ✓ Added POS module permissions\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "\n✗ ERROR: " . $e->getMessage() . "\n";
        error_log("Manager permissions fix error: " . $e->getMessage());
        return false;
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    fixManagerPermissions();
}

