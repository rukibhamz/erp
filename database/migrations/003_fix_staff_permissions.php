<?php
/**
 * Fix Staff Permissions - POS, Bookings, Inventory, Utilities
 * Grants staff read, update, and create permissions for specified modules
 */

require_once __DIR__ . '/../../application/config/database.php';

try {
    $dbConfig = $db['default'];
    $host = $dbConfig['hostname'];
    $dbname = $dbConfig['database'];
    $username = $dbConfig['username'];
    $password = $dbConfig['password'];
    $prefix = $dbConfig['dbprefix'] ?? 'erp_';
    
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "========================================\n";
    echo "FIX STAFF PERMISSIONS\n";
    echo "========================================\n\n";
    
    // Step 1: Ensure POS permissions exist
    echo "Step 1: Ensuring POS permissions exist...\n";
    $posPermissions = [
        ['pos', 'read', 'View POS'],
        ['pos', 'write', 'Create/edit POS transactions'],
        ['pos', 'delete', 'Delete POS transactions'],
        ['pos', 'create', 'Create POS transactions'],
        ['pos', 'update', 'Update POS transactions'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO `{$prefix}permissions` (module, permission, description, created_at) VALUES (?, ?, ?, NOW())");
    foreach ($posPermissions as $perm) {
        $stmt->execute($perm);
    }
    echo "✓ POS permissions verified\n\n";
    
    // Step 2: Remove existing staff permissions
    echo "Step 2: Removing existing staff permissions...\n";
    $stmt = $pdo->prepare("DELETE rp FROM `{$prefix}role_permissions` rp
        JOIN `{$prefix}roles` r ON rp.role_id = r.id
        WHERE r.role_code = 'staff'");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    echo "✓ Removed {$deleted} existing staff permissions\n\n";
    
    // Step 3: Grant read and update permissions
    echo "Step 3: Granting read and update permissions to staff...\n";
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
    echo "✓ Added {$added} read/update permissions\n\n";
    
    // Step 4: Grant create permissions
    echo "Step 4: Granting create permissions to staff...\n";
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
    echo "✓ Added {$createAdded} create permissions\n\n";
    
    // Verification
    echo "========================================\n";
    echo "VERIFICATION\n";
    echo "========================================\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM `{$prefix}role_permissions` rp
        JOIN `{$prefix}roles` r ON rp.role_id = r.id
        WHERE r.role_code = 'staff'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Total staff permissions: {$result['total']}\n\n";
    
    $stmt = $pdo->prepare("SELECT p.module, p.permission
        FROM `{$prefix}role_permissions` rp
        JOIN `{$prefix}permissions` p ON rp.permission_id = p.id
        JOIN `{$prefix}roles` r ON rp.role_id = r.id
        WHERE r.role_code = 'staff'
        ORDER BY p.module, p.permission");
    $stmt->execute();
    $perms = $stmt->fetchAll();
    
    echo "Staff permissions:\n";
    foreach ($perms as $perm) {
        echo "  - {$perm['module']}.{$perm['permission']}\n";
    }
    
    echo "\n========================================\n";
    echo "MIGRATION COMPLETE\n";
    echo "========================================\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

