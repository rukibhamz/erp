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
 * Get module display name (uses custom labels if available)
 */
function get_module_name($moduleKey) {
    try {
        // First try to get from module_labels table (custom labels)
        require_once BASEPATH . 'models/Module_label_model.php';
        $moduleLabelModel = new Module_label_model();
        $label = $moduleLabelModel->getLabel($moduleKey);
        if ($label && $label !== ucfirst($moduleKey)) {
            return $label;
        }
        
        // Fallback to Module_model
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
 * Get module icon class (uses custom icons if available)
 */
function get_module_icon($moduleKey) {
    try {
        require_once BASEPATH . 'models/Module_label_model.php';
        $moduleLabelModel = new Module_label_model();
        $labels = $moduleLabelModel->getAllLabels(true);
        if (isset($labels[$moduleKey]['icon_class']) && !empty($labels[$moduleKey]['icon_class'])) {
            return $labels[$moduleKey]['icon_class'];
        }
        
        // Fallback to Module_model
        require_once BASEPATH . 'models/Module_model.php';
        $moduleModel = new Module_model();
        $module = $moduleModel->getByKey($moduleKey);
        return $module && isset($module['icon']) ? $module['icon'] : 'bi-puzzle';
    } catch (Exception $e) {
        error_log('Module helper get_module_icon error: ' . $e->getMessage());
        return 'bi-puzzle';
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
 * Filters modules based on user's permissions and module_labels visibility
 */
function get_user_accessible_modules() {
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    try {
        require_once BASEPATH . 'models/Module_model.php';
        require_once BASEPATH . 'models/User_permission_model.php';
        require_once BASEPATH . 'models/Module_label_model.php';
        require_once BASEPATH . 'helpers/permission_helper.php';
        
        $moduleModel = new Module_model();
        $permissionModel = new User_permission_model();
        $moduleLabelModel = new Module_label_model();
        
        // Get all module labels (includes visibility settings)
        $moduleLabels = $moduleLabelModel->getAllLabels(true); // Only active modules
        
        // Get all modules from Module_model
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
            
            // Check if module is visible in module_labels (is_active = 1)
            if (isset($moduleLabels[$moduleKey]) && isset($moduleLabels[$moduleKey]['is_active']) && !$moduleLabels[$moduleKey]['is_active']) {
                continue; // Skip hidden modules
            }
            
            // Super admin and admin can see all visible modules
            if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin')) {
                // Use custom label and icon if available
                if (isset($moduleLabels[$moduleKey])) {
                    $module['display_name'] = $moduleLabels[$moduleKey]['display_label'];
                    $module['icon'] = $moduleLabels[$moduleKey]['icon_class'] ?? $module['icon'] ?? 'bi-puzzle';
                }
                $accessibleModules[] = $module;
                continue;
            }
            
            // Check if user has read permission for this module
            $permissionModule = $modulePermissionMap[$moduleKey] ?? $moduleKey;
            $hasPermission = $permissionModel->hasPermission($_SESSION['user_id'], $permissionModule, 'read');
            
            if ($hasPermission) {
                // Use custom label and icon if available
                if (isset($moduleLabels[$moduleKey])) {
                    $module['display_name'] = $moduleLabels[$moduleKey]['display_label'];
                    $module['icon'] = $moduleLabels[$moduleKey]['icon_class'] ?? $module['icon'] ?? 'bi-puzzle';
                }
                $accessibleModules[] = $module;
            }
        }
        
        // Sort by display_order from module_labels
        usort($accessibleModules, function($a, $b) use ($moduleLabels) {
            $orderA = isset($moduleLabels[$a['module_key']]) && isset($moduleLabels[$a['module_key']]['display_order']) 
                ? $moduleLabels[$a['module_key']]['display_order'] : 999;
            $orderB = isset($moduleLabels[$b['module_key']]) && isset($moduleLabels[$b['module_key']]['display_order']) 
                ? $moduleLabels[$b['module_key']]['display_order'] : 999;
            return $orderA <=> $orderB;
        });
        
        return $accessibleModules;
    } catch (Exception $e) {
        error_log('Module helper get_user_accessible_modules error: ' . $e->getMessage());
        // Fall back to all active modules if check fails
        return get_active_modules();
    }
}



