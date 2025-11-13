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
            
            // Validate required fields
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                $this->setFlashMessage('danger', 'Username, email, and password are required.');
                redirect('users/create');
            }
            
            // Validate email format
            if (!validate_email($data['email'])) {
                $this->setFlashMessage('danger', 'Invalid email address.');
                redirect('users/create');
            }
            
            // Validate phone if provided
            if (!empty($data['phone']) && !validate_phone($data['phone'])) {
                $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
                redirect('users/create');
            }
            
            // Sanitize phone
            if (!empty($data['phone'])) {
                $data['phone'] = sanitize_phone($data['phone']);
            }
            
            // Validate names if provided
            if (!empty($data['first_name']) && !validate_name($data['first_name'])) {
                $this->setFlashMessage('danger', 'Invalid first name.');
                redirect('users/create');
            }
            
            if (!empty($data['last_name']) && !validate_name($data['last_name'])) {
                $this->setFlashMessage('danger', 'Invalid last name.');
                redirect('users/create');
            }
            
            // Validate password strength
            $passwordValidation = validate_password($data['password']);
            if (!$passwordValidation['valid']) {
                $this->setFlashMessage('danger', implode(' ', $passwordValidation['errors']));
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
                
                // Assign permissions using centralized method
                $this->assignPermissionsByRole($userId, $data['role'], $_POST['permissions'] ?? null);
                
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
        
        // SECURITY: Prevent editing own account in this interface
        if ($id == $this->session['user_id']) {
            redirect('profile'); // Redirect to dedicated profile page
        }
        
        $userToEdit = $this->userModel->getById($id);
        
        if (!$userToEdit) {
            $this->setFlashMessage('danger', 'User not found.');
            redirect('users');
        }
        
        // SECURITY: Enforce role hierarchy - prevent privilege escalation
        $currentUserRole = $this->session['role'] ?? 'user';
        $targetUserRole = $userToEdit['role'] ?? 'user';
        
        // Define role hierarchy (higher number = higher privilege)
        $roleHierarchy = [
            'user' => 1,
            'manager' => 2,
            'admin' => 3,
            'super_admin' => 4
        ];
        
        // Get hierarchy values (default to lowest if role not found)
        $currentUserLevel = $roleHierarchy[$currentUserRole] ?? 1;
        $targetUserLevel = $roleHierarchy[$targetUserRole] ?? 1;
        
        // SECURITY: Only allow editing users with lower or equal privilege level
        // Super admin can edit anyone, admin can edit manager/user, manager can only edit user
        if ($currentUserLevel <= $targetUserLevel) {
            $this->setFlashMessage('danger', 'You do not have permission to edit this user.');
            redirect('users');
        }
        
        $user = $userToEdit; // Keep variable name for backward compatibility
        
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
            
            // SECURITY: Prevent users from escalating their own or others' privileges
            // Check if role is being changed to a higher privilege level
            $newRole = $data['role'];
            $newRoleLevel = $roleHierarchy[$newRole] ?? 1;
            
            // Prevent changing role to a level higher than current user's level
            if ($newRoleLevel > $currentUserLevel) {
                $this->setFlashMessage('danger', 'You cannot assign a role with higher privileges than your own.');
                redirect('users/edit/' . $id);
            }
            
            // Prevent changing role to a level higher than target user's current level
            if ($newRoleLevel > $targetUserLevel) {
                $this->setFlashMessage('danger', 'You cannot promote a user to a role with higher privileges than their current role.');
                redirect('users/edit/' . $id);
            }
            
            try {
                $this->userModel->update($id, $data);
                
                // Update permissions using centralized method
                $this->assignPermissionsByRole($id, $data['role'], $_POST['permissions'] ?? null);
                
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
    /**
     * Assign permissions to user based on role
     * 
     * Centralized method to handle permission assignment logic.
     * Removes code duplication between create() and edit() methods.
     * 
     * @param int $userId User ID
     * @param string $role User role
     * @param array|null $postedPermissions Explicitly provided permissions (optional)
     * @return void
     */
    private function assignPermissionsByRole($userId, $role, $postedPermissions = null) {
        if (isset($postedPermissions) && is_array($postedPermissions)) {
            // If permissions are explicitly provided, use them
            $permissionIds = array_map('intval', $postedPermissions);
            $this->userPermissionModel->assignPermissions($userId, $permissionIds);
        } elseif ($role === 'admin') {
            // For admin role, assign all permissions by default
            $allPermissions = $this->permissionModel->getAllPermissions();
            $permissionIds = array_column($allPermissions, 'id');
            $this->userPermissionModel->assignPermissions($userId, $permissionIds);
        } elseif ($role === 'manager') {
            // For manager role, assign create, read, update for all modules except tax
            $managerPermissions = $this->getManagerPermissions();
            $permissionIds = array_column($managerPermissions, 'id');
            $this->userPermissionModel->assignPermissions($userId, $permissionIds);
        }
    }
    
    /**
     * Get manager permissions - create, read, update for all modules except tax
     */
    private function getManagerPermissions() {
        $allPermissions = $this->permissionModel->getAllPermissions();
        $managerPermissions = [];
        
        // Modules to exclude from manager access
        $excludedModules = ['tax', 'users', 'settings', 'companies', 'modules'];
        
        // Allowed actions for managers
        $allowedActions = ['create', 'read', 'update'];
        
        foreach ($allPermissions as $permission) {
            // Skip excluded modules
            if (in_array($permission['module'], $excludedModules)) {
                continue;
            }
            
            // Only include create, read, update actions (exclude delete)
            if (in_array($permission['permission'], $allowedActions)) {
                $managerPermissions[] = $permission;
            }
        }
        
        return $managerPermissions;
    }
    
    /**
     * Ensure all business module permissions exist in the database
     * This is a utility method to fix missing permissions for existing installations
     * @return int Number of permissions created
     */
    private function ensureAllPermissionsExist() {
        $businessModules = ['accounting', 'bookings', 'properties', 'utilities', 'inventory', 'tax', 'pos'];
        $actions = ['create', 'read', 'update', 'delete'];
        $created = 0;
        
        foreach ($businessModules as $module) {
            foreach ($actions as $action) {
                // Check if permission exists
                $existing = $this->permissionModel->getByModule($module);
                $exists = false;
                foreach ($existing as $perm) {
                    if ($perm['permission'] === $action) {
                        $exists = true;
                        break;
                    }
                }
                
                // Insert if doesn't exist
                if (!$exists) {
                    try {
                        $prefix = $this->db->getPrefix();
                        $sql = "INSERT IGNORE INTO `{$prefix}permissions` (module, permission, description, created_at) VALUES (?, ?, ?, ?)";
                        $stmt = $this->db->getConnection()->prepare($sql);
                        $stmt->execute([
                            $module,
                            $action,
                            ucfirst($action) . ' ' . ucfirst($module),
                            date('Y-m-d H:i:s')
                        ]);
                        if ($stmt->rowCount() > 0) {
                            $created++;
                            error_log("Created permission: {$module}.{$action}");
                        }
                    } catch (Exception $e) {
                        // Log but continue
                        error_log("Permission insert warning for {$module}.{$action}: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $created;
    }
    
    /**
     * Fix manager permissions - assign create, read, update for all modules except tax
     * This is a utility method to fix existing manager users
     */
    public function fixManagerPermissions() {
        // Only super admin can run this
        if ($this->session['role'] !== 'super_admin') {
            $this->setFlashMessage('danger', 'Only super administrators can run this utility.');
            redirect('users');
        }
        
        try {
            // First, ensure all permissions exist in the database
            $created = $this->ensureAllPermissionsExist();
            
            // Get all manager users using the model
            $allUsers = $this->userModel->getAll();
            $managerUsers = array_filter($allUsers, function($user) {
                return $user['role'] === 'manager';
            });
            
            // Get manager permissions (create, read, update for all modules except tax)
            $managerPermissions = $this->getManagerPermissions();
            $permissionIds = array_column($managerPermissions, 'id');
            
            if (empty($permissionIds)) {
                $this->setFlashMessage('warning', 'No manager permissions found. Please ensure permissions are created in the database.');
                redirect('users');
            }
            
            $fixed = 0;
            $messages = [];
            foreach ($managerUsers as $manager) {
                $this->userPermissionModel->assignPermissions($manager['id'], $permissionIds);
                $fixed++;
                $messages[] = "Assigned " . count($permissionIds) . " permissions to manager: {$manager['username']} (ID: {$manager['id']})";
            }
            
            $message = "Fixed permissions for {$fixed} manager user(s).";
            if ($created > 0) {
                $message .= " Created {$created} missing permission(s).";
            }
            $message .= " Total permissions assigned: " . count($permissionIds);
            
            // Log detailed messages
            foreach ($messages as $msg) {
                error_log($msg);
            }
            
            $this->setFlashMessage('success', $message);
            redirect('users');
        } catch (Exception $e) {
            error_log("Error fixing manager permissions: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->setFlashMessage('danger', 'Error fixing manager permissions: ' . $e->getMessage());
            redirect('users');
        }
    }
    
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
