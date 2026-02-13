<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_portal_user_model extends Base_Model {
    protected $table = 'customer_portal_users';
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 1800; // 30 minutes
    
    /**
     * Register new customer
     */
    public function register($data) {
        try {
            // Check if email already exists
            $existing = $this->getByEmail($data['email']);
            if ($existing) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // SECURITY: Hash password using bcrypt (consistent with User_model)
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            
            // Generate email verification token
            $data['email_verification_token'] = bin2hex(random_bytes(32));
            
            // Set status to inactive until email verified
            $data['status'] = 'inactive';
            $data['email_verified'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            
            $userId = $this->create($data);
            
            if ($userId) {
                return [
                    'success' => true,
                    'user_id' => $userId,
                    'verification_token' => $data['email_verification_token']
                ];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
        } catch (Exception $e) {
            error_log('Customer_portal_user_model register error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Registration error'];
        }
    }
    
    /**
     * Authenticate customer
     */
    public function authenticate($email, $password, $rememberMe = false) {
        try {
            $user = $this->getByEmail($email);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if account is locked
            if ($user['status'] === 'suspended' || 
                ($user['locked_until'] && strtotime($user['locked_until']) > time())) {
                return ['success' => false, 'message' => 'Account is locked. Please contact support.'];
            }
            
            // Check if account is active
            if ($user['status'] !== 'active') {
                return ['success' => false, 'message' => 'Account is not active. Please verify your email.'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->incrementFailedLogin($user['id']);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Successful login - reset failed attempts
            $this->resetFailedLogin($user['id']);
            
            // Update last login
            $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
            
            // Generate remember token if needed
            if ($rememberMe) {
                $rememberToken = bin2hex(random_bytes(32));
                $this->update($user['id'], ['remember_token' => $rememberToken]);
                $user['remember_token'] = $rememberToken;
            }
            
            unset($user['password']);
            return ['success' => true, 'user' => $user];
        } catch (Exception $e) {
            error_log('Customer_portal_user_model authenticate error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication error'];
        }
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE email = ?",
                [$email]
            );
        } catch (Exception $e) {
            error_log('Customer_portal_user_model getByEmail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Link bookings to customer by email
     */
    public function linkBookingsByEmail($email) {
        try {
            $user = $this->getByEmail($email);
            if (!$user) {
                return false;
            }
            
            // Update bookings with this email to link to customer portal user
            $this->db->update(
                'bookings',
                ['customer_portal_user_id' => $user['id']],
                "customer_email = ? AND (customer_portal_user_id IS NULL OR customer_portal_user_id = 0)",
                [$email]
            );
            
            return true;
        } catch (Exception $e) {
            error_log('Customer_portal_user_model linkBookingsByEmail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify email
     */
    public function verifyEmail($token) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE email_verification_token = ?",
                [$token]
            );
            
            if (!$user) {
                return false;
            }
            
            $this->update($user['id'], [
                'email_verified' => 1,
                'status' => 'active',
                'email_verification_token' => null
            ]);
            
            // Link bookings
            $this->linkBookingsByEmail($user['email']);
            
            return true;
        } catch (Exception $e) {
            error_log('Customer_portal_user_model verifyEmail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($email) {
        try {
            $user = $this->getByEmail($email);
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->update($user['id'], [
                'password_reset_token' => $token,
                'password_reset_expires' => $expires
            ]);
            
            return ['success' => true, 'token' => $token];
        } catch (Exception $e) {
            error_log('Customer_portal_user_model requestPasswordReset error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error requesting reset'];
        }
    }
    
    /**
     * Reset password
     */
    public function resetPassword($token, $newPassword) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE password_reset_token = ? AND password_reset_expires > NOW()",
                [$token]
            );
            
            if (!$user) {
                return false;
            }
            
            $this->update($user['id'], [
                'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                'password_reset_token' => null,
                'password_reset_expires' => null,
                'failed_login_attempts' => 0,
                'locked_until' => null
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Customer_portal_user_model resetPassword error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get customer bookings
     */
    public function getBookings($userId, $status = null) {
        try {
            $user = $this->getById($userId);
            if (!$user) {
                return [];
            }
            
            $sql = "SELECT b.*, f.facility_name, f.facility_code 
                    FROM `" . $this->db->getPrefix() . "bookings` b
                    JOIN `" . $this->db->getPrefix() . "facilities` f ON b.facility_id = f.id
                    WHERE b.customer_email = ?";
            
            $params = [$user['email']];
            
            if ($status) {
                $sql .= " AND b.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY b.booking_date DESC, b.created_at DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Customer_portal_user_model getBookings error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function incrementFailedLogin($userId) {
        try {
            $user = $this->getById($userId);
            if (!$user) {
                return;
            }
            
            $attempts = intval($user['failed_login_attempts']) + 1;
            $updateData = ['failed_login_attempts' => $attempts];
            
            if ($attempts >= $this->maxLoginAttempts) {
                $updateData['locked_until'] = date('Y-m-d H:i:s', time() + $this->lockoutDuration);
                $updateData['status'] = 'suspended';
            }
            
            $this->update($userId, $updateData);
        } catch (Exception $e) {
            error_log('Customer_portal_user_model incrementFailedLogin error: ' . $e->getMessage());
        }
    }
    
    private function resetFailedLogin($userId) {
        try {
            $this->update($userId, [
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'status' => 'active'
            ]);
        } catch (Exception $e) {
            error_log('Customer_portal_user_model resetFailedLogin error: ' . $e->getMessage());
        }
    }
}

