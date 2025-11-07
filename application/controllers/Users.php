<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends Base_Controller {
    private $userModel;
    private $permissionModel;
    private $userPermissionModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('users', 'read');
        $this->userModel = $this->loadModel('User_model');
        $this->permissionModel = $this->loadModel('Permission_model');
        $this->userPermissionModel = $this->loadModel('User_permission_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Users',
            'users' => $this->userModel->getAll(null, 0, 'created_at DESC'),
            'flash' => $this->getFlashMessage(),
            'canCreate' => $this->userPermissionModel->hasPermission($this->session['user_id'], 'users', 'create') || ($this->session['role'] === 'super_admin' || $this->session['role'] === 'admin'),
            'canUpdate' => $this->userPermissionModel->hasPermission($this->session['user_id'], 'users', 'update') || ($this->session['role'] === 'super_admin' || $this->session['role'] === 'admin'),
            'canDelete' => $this->userPermissionModel->hasPermission($this->session['user_id'], 'users', 'delete') || ($this->session['role'] === 'super_admin' || $this->session['role'] === 'admin')
        ];
        
        $this->loadView('users/index', $data);
    }
    
    public function create() {
        $this->requirePermission('users', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => sanitize_input($_POST['username'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'first_name' => sanitize_input($_POST['first_name'] ?? ''),
                'last_name' => sanitize_input($_POST['last_name'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'role' => sanitize_input($_POST['role'] ?? 'user'),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];
            
            // Validate
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                $this->setFlashMessage('danger', 'Username, email, and password are required.');
                redirect('users/create');
            }
            
            // Check if username or email exists
            if ($this->userModel->getByUsername($data['username'])) {
                $this->setFlashMessage('danger', 'Username already exists.');
                redirect('users/create');
            }
            
            if ($this->userModel->getByEmail($data['email'])) {
                $this->setFlashMessage('danger', 'Email already exists.');
                redirect('users/create');
            }
            
            try {
                $userId = $this->userModel->create($data);
                
                // Assign permissions
                if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                    // If permissions are explicitly provided, use them
                    $permissionIds = array_map('intval', $_POST['permissions']);
                    $this->userPermissionModel->assignPermissions($userId, $permissionIds);
                } elseif ($data['role'] === 'admin') {
                    // For admin role, assign all permissions by default
                    $allPermissions = $this->permissionModel->getAllPermissions();
                    $permissionIds = array_column($allPermissions, 'id');
                    $this->userPermissionModel->assignPermissions($userId, $permissionIds);
                }
                
                // Log activity
                $this->activityModel->log($this->session['user_id'], 'user_created', 'Users', 'Created user: ' . $data['username']);
                
                $this->setFlashMessage('success', 'User created successfully.');
                redirect('users');
            } catch (Exception $e) {
                $this->setFlashMessage('danger', $e->getMessage());
                redirect('users/create');
            }
        }
        
        $data = [
            'page_title' => 'Create User',
            'flash' => $this->getFlashMessage(),
            'permissions' => $this->permissionModel->getAllByModule(),
            'modules' => $this->permissionModel->getAllModules()
        ];
        $this->loadView('users/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('users', 'update');
        
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $this->setFlashMessage('danger', 'User not found.');
            redirect('users');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // Validate CSRF token
            
            $data = [
                'username' => sanitize_input($_POST['username'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'first_name' => sanitize_input($_POST['first_name'] ?? ''),
                'last_name' => sanitize_input($_POST['last_name'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'role' => sanitize_input($_POST['role'] ?? 'user'),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];
            
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            
            try {
                $this->userModel->update($id, $data);
                
                // Update permissions
                if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                    // If permissions are explicitly provided, use them
                    $permissionIds = array_map('intval', $_POST['permissions']);
                    $this->userPermissionModel->assignPermissions($id, $permissionIds);
                } elseif ($data['role'] === 'admin') {
                    // If role is changed to admin, assign all permissions
                    $allPermissions = $this->permissionModel->getAllPermissions();
                    $permissionIds = array_column($allPermissions, 'id');
                    $this->userPermissionModel->assignPermissions($id, $permissionIds);
                }
                
                // Log activity
                $this->activityModel->log($this->session['user_id'], 'user_updated', 'Users', 'Updated user: ' . $user['username']);
                
                $this->setFlashMessage('success', 'User updated successfully.');
                redirect('users');
            } catch (Exception $e) {
                $this->setFlashMessage('danger', $e->getMessage());
                redirect('users/edit/' . $id);
            }
        }
        
        $userPermissions = $this->userPermissionModel->getUserPermissionIds($id);
        
        $data = [
            'page_title' => 'Edit User',
            'user' => $user,
            'flash' => $this->getFlashMessage(),
            'permissions' => $this->permissionModel->getAllByModule(),
            'modules' => $this->permissionModel->getAllModules(),
            'userPermissions' => $userPermissions
        ];
        $this->loadView('users/edit', $data);
    }
    
    public function permissions($id) {
        $this->requirePermission('users', 'update');
        
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $this->setFlashMessage('danger', 'User not found.');
            redirect('users');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // Validate CSRF token
            
            $permissionIds = isset($_POST['permissions']) ? array_map('intval', $_POST['permissions']) : [];
            $this->userPermissionModel->assignPermissions($id, $permissionIds);
            
            // Log activity
            $this->activityModel->log($this->session['user_id'], 'permissions_updated', 'Users', 'Updated permissions for user: ' . $user['username']);
            
            $this->setFlashMessage('success', 'Permissions updated successfully.');
            redirect('users/edit/' . $id);
        }
        
        $userPermissions = $this->userPermissionModel->getUserPermissionIds($id);
        
        $data = [
            'page_title' => 'Manage Permissions - ' . ($user['first_name'] . ' ' . $user['last_name'] ?: $user['username']),
            'user' => $user,
            'flash' => $this->getFlashMessage(),
            'permissions' => $this->permissionModel->getAllByModule(),
            'modules' => $this->permissionModel->getAllModules(),
            'userPermissions' => $userPermissions
        ];
        $this->loadView('users/permissions', $data);
    }
    
    /**
     * Fix admin permissions - assign all permissions to all admin users
     * This is a utility method to fix existing admin users
     */
    public function fixAdminPermissions() {
        // Only super admin can run this
        if ($this->session['role'] !== 'super_admin') {
            $this->setFlashMessage('danger', 'Only super administrators can run this utility.');
            redirect('users');
        }
        
        try {
            // Get all admin users using the model
            $allUsers = $this->userModel->getAll();
            $adminUsers = array_filter($allUsers, function($user) {
                return $user['role'] === 'admin';
            });
            
            // Get all permissions
            $allPermissions = $this->permissionModel->getAllPermissions();
            $permissionIds = array_column($allPermissions, 'id');
            
            $fixed = 0;
            foreach ($adminUsers as $admin) {
                $this->userPermissionModel->assignPermissions($admin['id'], $permissionIds);
                $fixed++;
            }
            
            $this->setFlashMessage('success', "Fixed permissions for {$fixed} admin user(s).");
            redirect('users');
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error fixing admin permissions: ' . $e->getMessage());
            redirect('users');
        }
    }
    
    public function delete($id) {
        $this->requirePermission('users', 'delete');
        
        // Don't allow deleting yourself
        if ($id == $this->session['user_id']) {
            $this->setFlashMessage('danger', 'You cannot delete your own account.');
            redirect('users');
        }
        
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $this->setFlashMessage('danger', 'User not found.');
            redirect('users');
        }
        
        $this->userModel->delete($id);
        
        // Log activity
        $this->activityModel->log($this->session['user_id'], 'user_deleted', 'Users', 'Deleted user: ' . $user['username']);
        
        $this->setFlashMessage('success', 'User deleted successfully.');
        redirect('users');
    }
}
