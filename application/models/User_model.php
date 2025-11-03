<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends Base_Model {
    protected $table = 'users';
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 1800; // 30 minutes in seconds
    
    public function authenticate($username, $password, $rememberMe = false) {
        $user = $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE (username = ? OR email = ?)",
            [$username, $username]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check if account is locked
        if ($user['status'] === 'locked' || ($user['locked_until'] && strtotime($user['locked_until']) > time())) {
            return ['success' => false, 'message' => 'Account is locked. Please try again later.'];
        }
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->incrementFailedLogin($user['id']);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Successful login - reset failed attempts
        $this->resetFailedLogin($user['id']);
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Generate remember token if needed
        if ($rememberMe) {
            $rememberToken = bin2hex(random_bytes(32));
            $this->update($user['id'], ['remember_token' => $rememberToken]);
            $user['remember_token'] = $rememberToken;
        }
        
        unset($user['password']);
        return ['success' => true, 'user' => $user];
    }
    
    private function incrementFailedLogin($userId) {
        $user = $this->getById($userId);
        $attempts = ($user['failed_login_attempts'] ?? 0) + 1;
        $updateData = ['failed_login_attempts' => $attempts];
        
        if ($attempts >= $this->maxLoginAttempts) {
            $updateData['status'] = 'locked';
            $updateData['locked_until'] = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
        }
        
        $this->update($userId, $updateData);
    }
    
    private function resetFailedLogin($userId) {
        $this->update($userId, [
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'status' => 'active'
        ]);
    }
    
    private function updateLastLogin($userId) {
        $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }
    
    public function getByEmail($email) {
        return $this->getBy('email', $email);
    }
    
    public function getByUsername($username) {
        return $this->getBy('username', $username);
    }
    
    public function getByRememberToken($token) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE remember_token = ? AND status = 'active'",
                [$token]
            );
            if ($user) {
                unset($user['password']);
            }
            return $user;
        } catch (Exception $e) {
            error_log('User_model getByRememberToken error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function getByPasswordResetToken($token) {
        $user = $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE password_reset_token = ? AND password_reset_expires > NOW()",
            [$token]
        );
        if ($user) {
            unset($user['password']);
        }
        return $user;
    }
    
    public function generatePasswordResetToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        $this->update($userId, [
            'password_reset_token' => $token,
            'password_reset_expires' => $expires
        ]);
        return $token;
    }
    
    public function resetPassword($userId, $newPassword) {
        return $this->update($userId, [
            'password' => $newPassword,
            'password_reset_token' => null,
            'password_reset_expires' => null,
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);
    }
    
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
    
    public function create($data) {
        if (isset($data['password'])) {
            // Validate password strength
            $errors = $this->validatePasswordStrength($data['password']);
            if (!empty($errors)) {
                throw new Exception(implode('. ', $errors));
            }
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::create($data);
    }
    
    public function update($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            // Validate password strength
            $errors = $this->validatePasswordStrength($data['password']);
            if (!empty($errors)) {
                throw new Exception(implode('. ', $errors));
            }
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
    
    public function enable2FA($userId, $secret) {
        return $this->update($userId, [
            'two_factor_secret' => $secret,
            'two_factor_enabled' => 1
        ]);
    }
    
    public function disable2FA($userId) {
        return $this->update($userId, [
            'two_factor_secret' => null,
            'two_factor_enabled' => 0
        ]);
    }
    
    public function getFullName($userId) {
        $user = $this->getById($userId);
        if ($user && ($user['first_name'] || $user['last_name'])) {
            return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        }
        return $user['username'] ?? '';
    }
}

