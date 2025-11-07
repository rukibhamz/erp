<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permission Helper Functions
 */

function has_permission($module, $permission) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Super admin and admin have all permissions
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin')) {
        return true;
    }
    
    // Load permission model
    require_once BASEPATH . 'models/User_permission_model.php';
    $permissionModel = new User_permission_model();
    return $permissionModel->hasPermission($_SESSION['user_id'], $module, $permission);
}

// Alias for camelCase naming convention
function hasPermission($module, $permission) {
    return has_permission($module, $permission);
}

function canCreate($module) {
    return hasPermission($module, 'create');
}

function canRead($module) {
    return hasPermission($module, 'read');
}

function canUpdate($module) {
    return hasPermission($module, 'update');
}

function canDelete($module) {
    return hasPermission($module, 'delete');
}

function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

function isAdmin() {
    $adminRoles = ['super_admin', 'admin'];
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $adminRoles);
}

function isManager() {
    $managerRoles = ['super_admin', 'admin', 'manager'];
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $managerRoles);
}

function getRoleBadgeClass($role) {
    $classes = [
        'super_admin' => 'danger',
        'admin' => 'dark', // Changed from warning to dark for minimal look
        'manager' => 'info',
        'staff' => 'dark', // Changed from primary to dark
        'user' => 'secondary'
    ];
    return $classes[$role] ?? 'secondary';
}

/**
 * Check field-level permission
 */
function canAccessField($module, $tableName, $fieldName, $action = 'read') {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Super admin and admin have all permissions
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin')) {
        return true;
    }
    
    require_once BASEPATH . 'models/Field_permission_model.php';
    $fieldPermissionModel = new Field_permission_model();
    return $fieldPermissionModel->canAccessField($_SESSION['user_id'], $module, $tableName, $fieldName, $action);
}

/**
 * Check record-level permission
 */
function canAccessRecord($module, $tableName, $recordId, $action = 'read') {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Super admin and admin have all permissions
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin')) {
        return true;
    }
    
    require_once BASEPATH . 'models/Record_permission_model.php';
    $recordPermissionModel = new Record_permission_model();
    return $recordPermissionModel->canAccessRecord($_SESSION['user_id'], $module, $tableName, $recordId, $action);
}

/**
 * Filter data based on field permissions
 */
function filterFieldsByPermission($data, $module, $tableName) {
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    // Super admin and admin see all fields
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin')) {
        return $data;
    }
    
    require_once BASEPATH . 'models/Field_permission_model.php';
    $fieldPermissionModel = new Field_permission_model();
    
    $filtered = [];
    foreach ($data as $field => $value) {
        if ($fieldPermissionModel->canAccessField($_SESSION['user_id'], $module, $tableName, $field, 'read')) {
            $filtered[$field] = $value;
        }
    }
    
    return $filtered;
}

