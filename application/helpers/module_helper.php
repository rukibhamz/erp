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



