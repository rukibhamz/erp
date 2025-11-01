<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Base_Controller {
    private $userModel;
    private $activityModel;
    private $sessionModel;
    
    public function __construct() {
        parent::__construct();
        // Only load models if database is available
        if ($this->db) {
            $this->userModel = $this->loadModel('User_model');
            $this->activityModel = $this->loadModel('Activity_model');
            $this->sessionModel = $this->loadModel('Session_model');
        }
        
        // Check remember me cookie
        $this->checkRememberMe();
    }
    
    public function login() {
        // Redirect if already logged in
        if (isset($this->session['user_id'])) {
            redirect('dashboard');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->userModel || !$this->activityModel) {
                $this->setFlashMessage('danger', 'System not properly configured. Please run the installer.');
            } else {
                $username = sanitize_input($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $rememberMe = isset($_POST['remember_me']);
                
                $result = $this->userModel->authenticate($username, $password, $rememberMe);
                
                if ($result['success']) {
                    $user = $result['user'];
                    
                    // Set session
                    $this->session['user_id'] = $user['id'];
                    $this->session['username'] = $user['username'];
                    $this->session['email'] = $user['email'];
                    $user['role'] = $user['role'] ?? 'user';
                    $this->session['role'] = $user['role'];
                    $this->session['first_name'] = $user['first_name'] ?? '';
                    $this->session['last_name'] = $user['last_name'] ?? '';
                    
                    // Set remember me cookie
                    if ($rememberMe && isset($user['remember_token'])) {
                        setcookie('remember_token', $user['remember_token'], time() + (86400 * 30), '/', '', false, true); // 30 days, httponly
                    }
                    
                    // Store session in database
                    $this->storeSession($user['id']);
                    
                    // Log activity
                    $this->activityModel->log($user['id'], 'login', 'Auth', 'User logged in successfully');
                    
                    redirect('dashboard');
                } else {
                    $this->setFlashMessage('danger', $result['message'] ?? 'Invalid username or password.');
                }
            }
        }
        
        $data['flash'] = $this->getFlashMessage();
        $this->loader->view('auth/login', $data);
    }
    
    public function logout() {
        $userId = $this->session['user_id'] ?? null;
        
        // Log activity
        if ($userId && $this->activityModel) {
            $this->activityModel->log($userId, 'logout', 'Auth');
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        // Destroy database session
        if ($userId && $this->sessionModel) {
            $sessionId = session_id();
            $this->sessionModel->destroySession($sessionId);
        }
        
        // Destroy session
        session_destroy();
        
        redirect('login');
    }
    
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize_input($_POST['email'] ?? '');
            
            if (empty($email)) {
                $this->setFlashMessage('danger', 'Please enter your email address.');
                redirect('auth/forgotPassword');
            }
            
            $user = $this->userModel->getByEmail($email);
            
            if ($user) {
                // Generate reset token
                $token = $this->userModel->generatePasswordResetToken($user['id']);
                
                // TODO: Send email with reset link
                // For now, we'll just show the token (in production, send via email)
                $resetLink = base_url('auth/resetPassword?token=' . $token);
                
                // Log activity
                $this->activityModel->log($user['id'], 'password_reset_requested', 'Auth');
                
                $this->setFlashMessage('info', 'Password reset link has been sent to your email. (In development: Token: ' . $token . ')');
            } else {
                // Don't reveal if user exists
                $this->setFlashMessage('info', 'If that email exists, a password reset link has been sent.');
            }
            
            redirect('login');
        }
        
        $data['flash'] = $this->getFlashMessage();
        $this->loader->view('auth/forgot_password', $data);
    }
    
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->setFlashMessage('danger', 'Invalid reset token.');
            redirect('login');
        }
        
        $user = $this->userModel->getByPasswordResetToken($token);
        
        if (!$user) {
            $this->setFlashMessage('danger', 'Invalid or expired reset token.');
            redirect('login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if ($password !== $confirmPassword) {
                $this->setFlashMessage('danger', 'Passwords do not match.');
                redirect('auth/resetPassword?token=' . $token);
            }
            
            try {
                $this->userModel->resetPassword($user['id'], $password);
                
                // Log activity
                $this->activityModel->log($user['id'], 'password_reset', 'Auth');
                
                $this->setFlashMessage('success', 'Password has been reset successfully. Please login.');
                redirect('login');
            } catch (Exception $e) {
                $this->setFlashMessage('danger', $e->getMessage());
                redirect('auth/resetPassword?token=' . $token);
            }
        }
        
        $data['token'] = $token;
        $data['flash'] = $this->getFlashMessage();
        $this->loader->view('auth/reset_password', $data);
    }
    
    private function checkRememberMe() {
        if (isset($this->session['user_id'])) {
            return; // Already logged in
        }
        
        if (!isset($_COOKIE['remember_token']) || empty($_COOKIE['remember_token'])) {
            return;
        }
        
        $token = $_COOKIE['remember_token'];
        $user = $this->userModel->getByRememberToken($token);
        
        if ($user) {
            // Auto-login
            $this->session['user_id'] = $user['id'];
            $this->session['username'] = $user['username'];
            $this->session['email'] = $user['email'];
            $this->session['role'] = $user['role'] ?? 'user';
            $this->session['first_name'] = $user['first_name'] ?? '';
            $this->session['last_name'] = $user['last_name'] ?? '';
            
            $this->storeSession($user['id']);
        }
    }
    
    private function storeSession($userId) {
        if (!$this->sessionModel) {
            return;
        }
        
        $sessionId = session_id();
        $sessionData = [
            'id' => $sessionId,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'last_activity' => time(),
            'data' => serialize($_SESSION)
        ];
        
        // Check if session exists
        $existing = $this->sessionModel->getById($sessionId);
        
        if ($existing) {
            $this->sessionModel->updateLastActivity($sessionId, time());
        } else {
            $this->sessionModel->create($sessionData);
        }
    }
}
