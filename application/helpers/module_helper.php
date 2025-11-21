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
            $icon = $labels[$moduleKey]['icon_class'];
            // Ensure icon has 'bi' prefix if not already present
            if (strpos($icon, 'bi-') !== 0 && strpos($icon, 'bi ') !== 0) {
                // If it's an old format like 'icon-home', convert to 'bi-home'
                $icon = str_replace('icon-', 'bi-', $icon);
            }
            return $icon;
        }
        
        // Fallback to Module_model
        require_once BASEPATH . 'models/Module_model.php';
        $moduleModel = new Module_model();
        $module = $moduleModel->getByKey($moduleKey);
        $icon = $module && isset($module['icon']) ? $module['icon'] : 'bi-puzzle';
        // Ensure icon has 'bi' prefix
        if (strpos($icon, 'bi-') !== 0 && strpos($icon, 'bi ') !== 0) {
            $icon = str_replace('icon-', 'bi-', $icon);
        }
        return $icon;
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
            'staff_management' => 'staff_management',
            'bookings' => 'bookings',
            'locations' => 'locations', // Locations (formerly Properties)
            'properties' => 'locations', // Legacy: properties maps to locations permissions
            'utilities' => 'utilities',
            'inventory' => 'inventory',
            'tax' => 'tax',
            'pos' => 'pos',
            'settings' => 'settings',
            'entities' => 'entities', // Entities (formerly Companies)
        ];
        
        // Map legacy module keys to new module keys for label lookup
        $moduleLabelMap = [
            'properties' => 'locations', // Legacy properties -> locations label
            'companies' => 'entities', // Legacy companies -> entities label
        ];
        
        foreach ($allModules as $module) {
            $moduleKey = $module['module_key'];
            
            // Map legacy module keys to new ones for label lookup
            $labelKey = $moduleLabelMap[$moduleKey] ?? $moduleKey;
            
            // Check if module is visible in module_labels (is_active = 1)
            if (isset($moduleLabels[$labelKey]) && isset($moduleLabels[$labelKey]['is_active']) && !$moduleLabels[$labelKey]['is_active']) {
                continue; // Skip hidden modules
            }
            
            // Super admin and admin can see all visible modules
            if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin')) {
                // Use custom label and icon if available (use labelKey for lookup)
                if (isset($moduleLabels[$labelKey])) {
                    $module['display_name'] = $moduleLabels[$labelKey]['display_label'];
                    $icon = $moduleLabels[$labelKey]['icon_class'] ?? $module['icon'] ?? 'bi-puzzle';
                    // Ensure icon has 'bi' prefix
                    if (strpos($icon, 'bi-') !== 0 && strpos($icon, 'bi ') !== 0) {
                        $icon = str_replace('icon-', 'bi-', $icon);
                    }
                    $module['icon'] = $icon;
                } else {
                    // Ensure default icon has 'bi' prefix
                    $icon = $module['icon'] ?? 'bi-puzzle';
                    if (strpos($icon, 'bi-') !== 0 && strpos($icon, 'bi ') !== 0) {
                        $icon = str_replace('icon-', 'bi-', $icon);
                    }
                    $module['icon'] = $icon;
                }
                $accessibleModules[] = $module;
                continue;
            }
            
            // Check if user has read permission for this module
            $permissionModule = $modulePermissionMap[$moduleKey] ?? $moduleKey;
            $hasPermission = $permissionModel->hasPermission($_SESSION['user_id'], $permissionModule, 'read');
            
            if ($hasPermission) {
                // Use custom label and icon if available (use labelKey for lookup)
                if (isset($moduleLabels[$labelKey])) {
                    $module['display_name'] = $moduleLabels[$labelKey]['display_label'];
                    $icon = $moduleLabels[$labelKey]['icon_class'] ?? $module['icon'] ?? 'bi-puzzle';
                    // Ensure icon has 'bi' prefix
                    if (strpos($icon, 'bi-') !== 0 && strpos($icon, 'bi ') !== 0) {
                        $icon = str_replace('icon-', 'bi-', $icon);
                    }
                    $module['icon'] = $icon;
                } else {
                    // Ensure default icon has 'bi' prefix
                    $icon = $module['icon'] ?? 'bi-puzzle';
                    if (strpos($icon, 'bi-') !== 0 && strpos($icon, 'bi ') !== 0) {
                        $icon = str_replace('icon-', 'bi-', $icon);
                    }
                    $module['icon'] = $icon;
                }
                $accessibleModules[] = $module;
            }
        }
        
        // Sort alphabetically by display_name
        usort($accessibleModules, function($a, $b) {
            $nameA = strtolower($a['display_name'] ?? '');
            $nameB = strtolower($b['display_name'] ?? '');
            return strcmp($nameA, $nameB);
        });
        
        return $accessibleModules;
    } catch (Exception $e) {
        error_log('Module helper get_user_accessible_modules error: ' . $e->getMessage());
        // Fall back to all active modules if check fails
        return get_active_modules();
    }
}

/**
 * Generate a back button link
 * 
 * @param string $url The URL to go back to (defaults to module root)
 * @param string $text Button text (defaults to "Back")
 * @param string $class Additional CSS classes
 * @return string HTML for back button
 */
function back_button($url = null, $text = 'Back', $class = 'btn-primary') {
    if ($url === null) {
        // Try to determine module from current URL
        $currentUrl = $_GET['url'] ?? '';
        $segments = explode('/', trim($currentUrl, '/'));
        if (!empty($segments)) {
            $url = base_url($segments[0]);
        } else {
            $url = base_url('dashboard');
        }
    } else {
        $url = base_url($url);
    }
    
    $classes = 'btn ' . ($class ?: 'btn-primary');
    return '<a href="' . htmlspecialchars($url) . '" class="' . trim($classes) . '">
        <i class="bi bi-arrow-left"></i> ' . htmlspecialchars($text) . '
    </a>';
}



