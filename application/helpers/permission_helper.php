<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permission Helper Functions
 */

function hasPermission($module, $permission) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Super admin has all permissions
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
        return true;
    }
    
    // Load permission model
    require_once BASEPATH . 'models/User_permission_model.php';
    $permissionModel = new User_permission_model();
    return $permissionModel->hasPermission($_SESSION['user_id'], $module, $permission);
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

