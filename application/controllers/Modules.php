<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Modules extends Base_Controller {
    private $moduleModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        
        // Only super admin can access module management
        if ($this->session['role'] !== 'super_admin') {
            $this->setFlashMessage('danger', 'Access denied. Only super administrators can manage modules.');
            redirect('dashboard');
        }
        
        $this->moduleModel = $this->loadModel('Module_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    /**
     * List all modules
     */
    public function index() {
        $modules = $this->moduleModel->getAll(null, 0, null, false);
        
        $data = [
            'page_title' => 'Module Management',
            'modules' => $modules,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('modules/index', $data);
    }
    
    /**
     * Toggle module activation
     */
    public function toggle() {
        check_csrf();
        
        $moduleKey = sanitize_input($_POST['module_key'] ?? '');
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
        
        if (empty($moduleKey)) {
            $this->setFlashMessage('danger', 'Invalid module key.');
            redirect('modules');
        }
        
        $module = $this->moduleModel->getByKey($moduleKey);
        if (!$module) {
            $this->setFlashMessage('danger', 'Module not found.');
            redirect('modules');
        }
        
        $result = $this->moduleModel->setActive($moduleKey, $isActive);
        
        if ($result) {
            $status = $isActive ? 'activated' : 'deactivated';
            $this->activityModel->log(
                $this->session['user_id'],
                'update',
                'Modules',
                "Module '{$module['display_name']}' {$status}"
            );
            $this->setFlashMessage('success', "Module '{$module['display_name']}' has been {$status}.");
        } else {
            $this->setFlashMessage('danger', 'Failed to update module status.');
        }
        
        redirect('modules');
    }
    
    /**
     * Update module display name
     */
    public function updateName() {
        check_csrf();
        
        $moduleKey = sanitize_input($_POST['module_key'] ?? '');
        $displayName = sanitize_input($_POST['display_name'] ?? '');
        
        if (empty($moduleKey) || empty($displayName)) {
            $this->setFlashMessage('danger', 'Module key and display name are required.');
            redirect('modules');
        }
        
        // Validate display name length
        if (strlen($displayName) > 100) {
            $this->setFlashMessage('danger', 'Display name must be 100 characters or less.');
            redirect('modules');
        }
        
        $module = $this->moduleModel->getByKey($moduleKey);
        if (!$module) {
            $this->setFlashMessage('danger', 'Module not found.');
            redirect('modules');
        }
        
        $oldName = $module['display_name'];
        $result = $this->moduleModel->updateDisplayName($moduleKey, $displayName);
        
        if ($result) {
            $this->activityModel->log(
                $this->session['user_id'],
                'update',
                'Modules',
                "Renamed module from '{$oldName}' to '{$displayName}'"
            );
            $this->setFlashMessage('success', "Module renamed from '{$oldName}' to '{$displayName}' successfully.");
        } else {
            $this->setFlashMessage('danger', 'Failed to update module name.');
        }
        
        redirect('modules');
    }
    
    /**
     * Update module details (name, description, icon, sort order)
     */
    public function update() {
        check_csrf();
        
        $moduleKey = sanitize_input($_POST['module_key'] ?? '');
        
        if (empty($moduleKey)) {
            $this->setFlashMessage('danger', 'Module key is required.');
            redirect('modules');
        }
        
        $module = $this->moduleModel->getByKey($moduleKey);
        if (!$module) {
            $this->setFlashMessage('danger', 'Module not found.');
            redirect('modules');
        }
        
        $updateData = [];
        
        if (isset($_POST['display_name']) && !empty($_POST['display_name'])) {
            $updateData['display_name'] = sanitize_input($_POST['display_name']);
        }
        
        if (isset($_POST['description'])) {
            $updateData['description'] = sanitize_input($_POST['description']);
        }
        
        if (isset($_POST['icon'])) {
            $updateData['icon'] = sanitize_input($_POST['icon']);
        }
        
        if (isset($_POST['sort_order'])) {
            $updateData['sort_order'] = intval($_POST['sort_order']);
        }
        
        if (empty($updateData)) {
            $this->setFlashMessage('danger', 'No changes to update.');
            redirect('modules');
        }
        
        $result = $this->moduleModel->updateModule($moduleKey, $updateData);
        
        if ($result) {
            $this->activityModel->log(
                $this->session['user_id'],
                'update',
                'Modules',
                "Updated module '{$module['display_name']}' settings"
            );
            $this->setFlashMessage('success', 'Module updated successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to update module.');
        }
        
        redirect('modules');
    }
}



