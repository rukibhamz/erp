<?php
/**
 * Module Label Model
 * Manages custom module labels for navigation
 */

class Module_label_model extends Base_Model {
    protected $table = 'module_labels';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get all module labels (custom or default)
     * @param bool $activeOnly - Only return active modules
     * @return array
     */
    public function getAllLabels($activeOnly = true) {
        try {
            $sql = "SELECT 
                        module_code,
                        default_label,
                        custom_label,
                        COALESCE(custom_label, default_label) as display_label,
                        icon_class,
                        display_order,
                        is_active
                    FROM `{$this->db->getPrefix()}module_labels`";
            
            if ($activeOnly) {
                $sql .= " WHERE is_active = 1";
            }
            
            $sql .= " ORDER BY display_order ASC";
            
            $result = $this->db->fetchAll($sql);
            
            // Return as associative array keyed by module_code
            $labels = [];
            foreach ($result as $row) {
                $labels[$row['module_code']] = $row;
            }
            
            return $labels;
        } catch (Exception $e) {
            error_log("Error getting module labels: " . $e->getMessage());
            return $this->getDefaultLabels();
        }
    }
    
    /**
     * Get display label for a specific module
     * @param string $moduleCode
     * @return string
     */
    public function getLabel($moduleCode) {
        try {
            $sql = "SELECT COALESCE(custom_label, default_label) as display_label
                    FROM `{$this->db->getPrefix()}module_labels`
                    WHERE module_code = ?";
            
            $result = $this->db->fetchOne($sql, [$moduleCode]);
            
            return $result['display_label'] ?? ucfirst($moduleCode);
        } catch (Exception $e) {
            error_log("Error getting label for {$moduleCode}: " . $e->getMessage());
            return ucfirst($moduleCode);
        }
    }
    
    /**
     * Update custom label for a module
     * @param string $moduleCode
     * @param string $customLabel
     * @param int $userId - User making the change
     * @return bool
     */
    public function updateLabel($moduleCode, $customLabel, $userId) {
        try {
            // Trim and validate
            $customLabel = trim($customLabel);
            
            if (empty($customLabel)) {
                throw new Exception("Custom label cannot be empty");
            }
            
            if (strlen($customLabel) > 100) {
                throw new Exception("Custom label too long (max 100 characters)");
            }
            
            $sql = "UPDATE `{$this->db->getPrefix()}module_labels`
                    SET custom_label = ?,
                        updated_by = ?,
                        updated_at = NOW()
                    WHERE module_code = ?";
            
            $stmt = $this->db->query($sql, [$customLabel, $userId, $moduleCode]);
            $result = $stmt ? true : false;
            
            if ($result) {
                error_log("Module label updated: {$moduleCode} -> {$customLabel} by user {$userId}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error updating module label: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset a module label to default
     * @param string $moduleCode
     * @param int $userId
     * @return bool
     */
    public function resetLabel($moduleCode, $userId) {
        try {
            $sql = "UPDATE `{$this->db->getPrefix()}module_labels`
                    SET custom_label = NULL,
                        updated_by = ?,
                        updated_at = NOW()
                    WHERE module_code = ?";
            
            $stmt = $this->db->query($sql, [$userId, $moduleCode]);
            $result = $stmt ? true : false;
            
            if ($result) {
                error_log("Module label reset to default: {$moduleCode} by user {$userId}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error resetting module label: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update module display order
     * @param string $moduleCode
     * @param int $order
     * @param int $userId
     * @return bool
     */
    public function updateOrder($moduleCode, $order, $userId) {
        try {
            $sql = "UPDATE `{$this->db->getPrefix()}module_labels`
                    SET display_order = ?,
                        updated_by = ?,
                        updated_at = NOW()
                    WHERE module_code = ?";
            
            $stmt = $this->db->query($sql, [$order, $userId, $moduleCode]);
            return $stmt ? true : false;
        } catch (Exception $e) {
            error_log("Error updating module order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Toggle module visibility
     * @param string $moduleCode
     * @param bool $isActive
     * @param int $userId
     * @return bool
     */
    public function toggleVisibility($moduleCode, $isActive, $userId) {
        try {
            // First, check if the module exists in the table
            $existing = $this->db->fetchOne(
                "SELECT id, is_active FROM `{$this->db->getPrefix()}module_labels` WHERE module_code = ?",
                [$moduleCode]
            );
            
            if ($existing) {
                // Module exists, update it
                $sql = "UPDATE `{$this->db->getPrefix()}module_labels`
                        SET is_active = ?,
                            updated_by = ?,
                            updated_at = NOW()
                        WHERE module_code = ?";
                
                $stmt = $this->db->query($sql, [$isActive ? 1 : 0, $userId, $moduleCode]);
                
                // Check if update actually affected a row
                if ($stmt && $stmt->rowCount() > 0) {
                    return true;
                }
                
                // If no rows affected but module exists, it might already have that value
                // Check if the value is already what we want
                if (intval($existing['is_active']) == ($isActive ? 1 : 0)) {
                    return true; // Already set to desired value
                }
                
                // Something went wrong
                error_log("Toggle visibility: Update query executed but no rows affected for module: {$moduleCode}");
                return false;
            } else {
                // Module doesn't exist, create it with default values
                $defaultLabel = ucfirst(str_replace('_', ' ', $moduleCode));
                $defaultIcon = 'bi-circle';
                
                // Try to get default values from getDefaultLabels if available
                $defaults = $this->getDefaultLabels();
                if (isset($defaults[$moduleCode])) {
                    $defaultLabel = $defaults[$moduleCode]['display_label'] ?? $defaultLabel;
                    $defaultIcon = $defaults[$moduleCode]['icon_class'] ?? $defaultIcon;
                }
                
                $sql = "INSERT INTO `{$this->db->getPrefix()}module_labels`
                        (module_code, default_label, custom_label, icon_class, display_order, is_active, updated_by, created_at, updated_at)
                        VALUES (?, ?, NULL, ?, 999, ?, ?, NOW(), NOW())";
                
                $stmt = $this->db->query($sql, [
                    $moduleCode,
                    $defaultLabel,
                    $defaultIcon,
                    $isActive ? 1 : 0,
                    $userId
                ]);
                
                if ($stmt) {
                    error_log("Created module label entry for {$moduleCode} with is_active = " . ($isActive ? 1 : 0));
                    return true;
                }
                
                error_log("Failed to create module label entry for {$moduleCode}");
                return false;
            }
        } catch (Exception $e) {
            error_log("Error toggling module visibility: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update module icon
     * @param string $moduleCode
     * @param string $iconClass
     * @param int $userId
     * @return bool
     */
    public function updateIcon($moduleCode, $iconClass, $userId) {
        try {
            $sql = "UPDATE `{$this->db->getPrefix()}module_labels`
                    SET icon_class = ?,
                        updated_by = ?,
                        updated_at = NOW()
                    WHERE module_code = ?";
            
            $stmt = $this->db->query($sql, [$iconClass, $userId, $moduleCode]);
            return $stmt ? true : false;
        } catch (Exception $e) {
            error_log("Error updating module icon: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bulk update module orders (for drag-and-drop reordering)
     * @param array $orders - ['module_code' => order, ...]
     * @param int $userId
     * @return bool
     */
    public function bulkUpdateOrders($orders, $userId) {
        try {
            $this->db->beginTransaction();
            
            foreach ($orders as $moduleCode => $order) {
                $sql = "UPDATE `{$this->db->getPrefix()}module_labels`
                        SET display_order = ?,
                            updated_by = ?,
                            updated_at = NOW()
                        WHERE module_code = ?";
                
                $this->db->query($sql, [$order, $userId, $moduleCode]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error bulk updating module orders: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get default labels for modules
     * Used as fallback if database is unavailable or when creating new entries
     * @return array
     */
    protected function getDefaultLabels() {
        return [
            'dashboard' => ['module_code' => 'dashboard', 'display_label' => 'Dashboard', 'icon_class' => 'bi-speedometer2', 'is_active' => 1, 'display_order' => 1],
            'accounting' => ['module_code' => 'accounting', 'display_label' => 'Accounting', 'icon_class' => 'bi-calculator', 'is_active' => 1, 'display_order' => 2],
            'bookings' => ['module_code' => 'bookings', 'display_label' => 'Bookings', 'icon_class' => 'bi-calendar', 'is_active' => 1, 'display_order' => 3],
            'locations' => ['module_code' => 'locations', 'display_label' => 'Locations', 'icon_class' => 'bi-building', 'is_active' => 1, 'display_order' => 4], // Locations (formerly Properties)
            'inventory' => ['module_code' => 'inventory', 'display_label' => 'Inventory', 'icon_class' => 'bi-box-seam', 'is_active' => 1, 'display_order' => 5],
            'utilities' => ['module_code' => 'utilities', 'display_label' => 'Utilities', 'icon_class' => 'bi-lightning', 'is_active' => 1, 'display_order' => 6],
            'reports' => ['module_code' => 'reports', 'display_label' => 'Reports', 'icon_class' => 'bi-bar-chart', 'is_active' => 1, 'display_order' => 7],
            'settings' => ['module_code' => 'settings', 'display_label' => 'Settings', 'icon_class' => 'bi-gear', 'is_active' => 1, 'display_order' => 8],
            'users' => ['module_code' => 'users', 'display_label' => 'User Management', 'icon_class' => 'bi-people', 'is_active' => 1, 'display_order' => 9],
            'notifications' => ['module_code' => 'notifications', 'display_label' => 'Notifications', 'icon_class' => 'bi-bell', 'is_active' => 1, 'display_order' => 10],
            'pos' => ['module_code' => 'pos', 'display_label' => 'Point of Sale', 'icon_class' => 'bi-cart', 'is_active' => 1, 'display_order' => 11],
            'tax' => ['module_code' => 'tax', 'display_label' => 'Tax Management', 'icon_class' => 'bi-file-text', 'is_active' => 1, 'display_order' => 12],
            'entities' => ['module_code' => 'entities', 'display_label' => 'Entities', 'icon_class' => 'bi-diagram-3', 'is_active' => 1, 'display_order' => 13] // Entities (formerly Companies)
        ];
    }
    
    /**
     * Get audit log of label changes
     * @param int $limit
     * @return array
     */
    public function getChangeHistory($limit = 50) {
        try {
            $sql = "SELECT 
                        ml.module_code,
                        ml.default_label,
                        ml.custom_label,
                        ml.updated_at,
                        u.name as updated_by_name
                    FROM `{$this->db->getPrefix()}module_labels` ml
                    LEFT JOIN `{$this->db->getPrefix()}users` u ON ml.updated_by = u.id
                    WHERE ml.custom_label IS NOT NULL
                    ORDER BY ml.updated_at DESC
                    LIMIT ?";
            
            return $this->db->fetchAll($sql, [$limit]);
        } catch (Exception $e) {
            error_log("Error getting module label history: " . $e->getMessage());
            return [];
        }
    }
}

