<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Module Helper Functions
 */

/**
 * Check if a module is active
 */
function is_module_active($moduleKey) {
    // Super admin can always access (even if module is inactive)
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
        return true;
    }
    
    try {
        require_once BASEPATH . 'models/Module_model.php';
        $moduleModel = new Module_model();
        return $moduleModel->isActive($moduleKey);
    } catch (Exception $e) {
        error_log('Module helper is_module_active error: ' . $e->getMessage());
        // Default to active if check fails (fail open for usability)
        return true;
    }
}

/**
 * Get module display name
 */
function get_module_name($moduleKey) {
    try {
        require_once BASEPATH . 'models/Module_model.php';
        $moduleModel = new Module_model();
        $module = $moduleModel->getByKey($moduleKey);
        return $module ? $module['display_name'] : ucfirst($moduleKey);
    } catch (Exception $e) {
        error_log('Module helper get_module_name error: ' . $e->getMessage());
        return ucfirst($moduleKey);
    }
}

/**
 * Get all active modules for navigation
 */
function get_active_modules() {
    try {
        require_once BASEPATH . 'models/Module_model.php';
        $moduleModel = new Module_model();
        return $moduleModel->getActiveModules();
    } catch (Exception $e) {
        error_log('Module helper get_active_modules error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get modules that user has permission to access
 * Filters modules based on user's permissions
 */
function get_user_accessible_modules() {
    // Super admin and admin can see all active modules
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin')) {
        return get_active_modules();
    }
    
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    try {
        require_once BASEPATH . 'models/Module_model.php';
        require_once BASEPATH . 'models/User_permission_model.php';
        require_once BASEPATH . 'helpers/permission_helper.php';
        
        $moduleModel = new Module_model();
        $permissionModel = new User_permission_model();
        $allModules = $moduleModel->getActiveModules();
        $accessibleModules = [];
        
        // Map module keys to permission module names
        $modulePermissionMap = [
            'accounting' => 'accounting',
            'bookings' => 'bookings',
            'properties' => 'properties',
            'utilities' => 'utilities',
            'inventory' => 'inventory',
            'tax' => 'tax',
            'pos' => 'pos',
            'settings' => 'settings',
        ];
        
        foreach ($allModules as $module) {
            $moduleKey = $module['module_key'];
            
            // Check if user has read permission for this module
            $permissionModule = $modulePermissionMap[$moduleKey] ?? $moduleKey;
            
            // Check permission using the permission model directly to avoid circular dependency
            $hasPermission = $permissionModel->hasPermission($_SESSION['user_id'], $permissionModule, 'read');
            
            if ($hasPermission) {
                $accessibleModules[] = $module;
            }
        }
        
        return $accessibleModules;
    } catch (Exception $e) {
        error_log('Module helper get_user_accessible_modules error: ' . $e->getMessage());
        // Fall back to all active modules if check fails
        return get_active_modules();
    }
}



