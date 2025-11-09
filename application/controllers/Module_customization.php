<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Module Customization Controller
 * Allows Super Admin to customize module labels and appearance
 */

class Module_customization extends Base_Controller {
    private $moduleLabelModel;
    
    public function __construct() {
        parent::__construct();
        
        // Only super admin can access this
        $this->requireRole('super_admin');
        
        $this->moduleLabelModel = $this->loadModel('Module_label_model');
    }
    
    /**
     * Display module customization page
     */
    public function index() {
        // Get all labels (keyed by module_code)
        $labels = $this->moduleLabelModel->getAllLabels(false); // Get all, including inactive
        
        // Filter out legacy duplicates (keep only 'locations' and 'entities', exclude 'properties' and 'companies')
        $legacyModules = ['properties', 'companies']; // Legacy modules to exclude
        
        // Convert to indexed array for the view
        $modules = [];
        $seenModules = []; // Track to prevent duplicates
        
        foreach ($labels as $moduleCode => $label) {
            // Skip legacy modules if we have the new ones
            if (in_array($moduleCode, $legacyModules)) {
                // Only include if the new equivalent doesn't exist
                if ($moduleCode === 'properties' && isset($labels['locations'])) {
                    continue; // Skip 'properties' if 'locations' exists
                }
                if ($moduleCode === 'companies' && isset($labels['entities'])) {
                    continue; // Skip 'companies' if 'entities' exists
                }
            }
            
            // Prevent duplicates
            if (isset($seenModules[$moduleCode])) {
                continue;
            }
            $seenModules[$moduleCode] = true;
            
            $modules[] = [
                'module_code' => $moduleCode,
                'default_label' => $label['default_label'] ?? ucfirst($moduleCode),
                'custom_label' => $label['custom_label'] ?? null,
                'display_label' => $label['display_label'] ?? $label['default_label'] ?? ucfirst($moduleCode),
                'icon_class' => $label['icon_class'] ?? 'bi bi-circle',
                'display_order' => $label['display_order'] ?? 999,
                'is_active' => $label['is_active'] ?? 1
            ];
        }
        
        // Sort by display_order
        usort($modules, function($a, $b) {
            return ($a['display_order'] ?? 999) <=> ($b['display_order'] ?? 999);
        });
        
        $data = [
            'title' => 'Module Customization',
            'modules' => $modules,
            'history' => $this->moduleLabelModel->getChangeHistory(20)
        ];
        $this->loadView('module_customization/index', $data);
    }
    
    /**
     * Update a module label via AJAX
     */
    public function updateLabel() {
        header('Content-Type: application/json');
        try {
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $moduleCode = $_POST['module_code'] ?? '';
            $customLabel = $_POST['custom_label'] ?? '';
            
            if (empty($moduleCode)) {
                throw new Exception('Module code is required');
            }
            
            if (empty($customLabel)) {
                throw new Exception('Custom label is required');
            }
            
            // Get current user ID
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            
            // Update the label
            $result = $this->moduleLabelModel->updateLabel($moduleCode, $customLabel, $userId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Module label updated successfully',
                    'data' => [
                        'module_code' => $moduleCode,
                        'custom_label' => $customLabel
                    ]
                ]);
            } else {
                throw new Exception('Failed to update module label');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Reset a module label to default via AJAX
     */
    public function resetLabel() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $moduleCode = $_POST['module_code'] ?? '';
            if (empty($moduleCode)) {
                throw new Exception('Module code is required');
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            
            $result = $this->moduleLabelModel->resetLabel($moduleCode, $userId);
            
            if ($result) {
                // Get the default label
                $label = $this->moduleLabelModel->getLabel($moduleCode);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Module label reset to default',
                    'data' => [
                        'module_code' => $moduleCode,
                        'default_label' => $label
                    ]
                ]);
            } else {
                throw new Exception('Failed to reset module label');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Toggle module visibility via AJAX
     */
    public function toggleVisibility() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $moduleCode = $_POST['module_code'] ?? '';
            $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : null;
            
            if (empty($moduleCode) || $isActive === null) {
                throw new Exception('Missing required parameters');
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            
            $result = $this->moduleLabelModel->toggleVisibility($moduleCode, $isActive, $userId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Module visibility updated',
                    'data' => [
                        'module_code' => $moduleCode,
                        'is_active' => $isActive
                    ]
                ]);
            } else {
                throw new Exception('Failed to update module visibility');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update module display order via AJAX (for drag-and-drop)
     */
    public function updateOrder() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            // Expecting JSON payload with module orders
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['orders']) || !is_array($input['orders'])) {
                throw new Exception('Invalid orders data');
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            
            $result = $this->moduleLabelModel->bulkUpdateOrders($input['orders'], $userId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Module order updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update module order');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update module icon via AJAX
     */
    public function updateIcon() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $moduleCode = $_POST['module_code'] ?? '';
            $iconClass = trim($_POST['icon_class'] ?? '');
            
            if (empty($moduleCode)) {
                throw new Exception('Module code is required');
            }
            
            // Icon class is optional - if not provided, skip update
            if (empty($iconClass)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'No icon class provided, skipping icon update',
                    'data' => [
                        'module_code' => $moduleCode
                    ]
                ]);
                return;
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('User not authenticated');
            }
            
            $result = $this->moduleLabelModel->updateIcon($moduleCode, $iconClass, $userId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Module icon updated',
                    'data' => [
                        'module_code' => $moduleCode,
                        'icon_class' => $iconClass
                    ]
                ]);
            } else {
                throw new Exception('Failed to update module icon');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

