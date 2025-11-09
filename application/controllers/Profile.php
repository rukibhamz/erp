<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends Base_Controller {
    private $userModel;
    private $activityModel;
    private $sessionModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = $this->loadModel('User_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->sessionModel = $this->loadModel('Session_model');
    }
    
    public function index() {
        $userId = $this->session['user_id'];
        $user = $this->userModel->getById($userId);
        $sessions = $this->sessionModel->getUserSessions($userId);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_profile':
                    $this->updateProfile($userId);
                    break;
                case 'change_password':
                    $this->changePassword($userId);
                    break;
                case 'upload_avatar':
                    $this->uploadAvatar($userId);
                    break;
            }
            redirect('profile');
        }
        
        $data = [
            'page_title' => 'My Profile',
            'user' => $user,
            'sessions' => $sessions,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('profile/index', $data);
    }
    
    private function updateProfile($userId) {
        $data = [
            'first_name' => sanitize_input($_POST['first_name'] ?? ''),
            'last_name' => sanitize_input($_POST['last_name'] ?? ''),
            'phone' => sanitize_input($_POST['phone'] ?? ''),
            'email' => sanitize_input($_POST['email'] ?? '')
        ];
        
        // Validate email format
        if (!empty($data['email']) && !validate_email($data['email'])) {
            $this->setFlashMessage('danger', 'Invalid email address.');
            return;
        }
        
        // Validate phone if provided
        if (!empty($data['phone']) && !validate_phone($data['phone'])) {
            $this->setFlashMessage('danger', 'Invalid phone number. Please enter a valid phone number.');
            return;
        }
        
        // Sanitize phone
        if (!empty($data['phone'])) {
            $data['phone'] = sanitize_phone($data['phone']);
        }
        
        // Validate names if provided
        if (!empty($data['first_name']) && !validate_name($data['first_name'])) {
            $this->setFlashMessage('danger', 'Invalid first name.');
            return;
        }
        
        if (!empty($data['last_name']) && !validate_name($data['last_name'])) {
            $this->setFlashMessage('danger', 'Invalid last name.');
            return;
        }
        
        // Check if email is already taken by another user
        $existingUser = $this->userModel->getByEmail($data['email']);
        if ($existingUser && $existingUser['id'] != $userId) {
            $this->setFlashMessage('danger', 'Email is already taken.');
            return;
        }
        
        $this->userModel->update($userId, $data);
        
        // Update session
        $this->session['email'] = $data['email'];
        $this->session['first_name'] = $data['first_name'];
        $this->session['last_name'] = $data['last_name'];
        
        $this->activityModel->log($userId, 'profile_updated', 'Profile');
        $this->setFlashMessage('success', 'Profile updated successfully.');
    }
    
    private function changePassword($userId) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            $this->setFlashMessage('danger', 'New passwords do not match.');
            return;
        }
        
        $user = $this->userModel->getById($userId);
        if (!password_verify($currentPassword, $user['password'])) {
            $this->setFlashMessage('danger', 'Current password is incorrect.');
            return;
        }
        
        try {
            $this->userModel->update($userId, ['password' => $newPassword]);
            $this->activityModel->log($userId, 'password_changed', 'Profile');
            $this->setFlashMessage('success', 'Password changed successfully.');
        } catch (Exception $e) {
            $this->setFlashMessage('danger', $e->getMessage());
        }
    }
    
    private function uploadAvatar($userId) {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlashMessage('danger', 'Failed to upload avatar.');
            return;
        }
        
        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $this->setFlashMessage('danger', 'Invalid file type. Only JPEG, PNG, and GIF are allowed.');
            return;
        }
        
        if ($file['size'] > 2097152) { // 2MB
            $this->setFlashMessage('danger', 'File size exceeds 2MB limit.');
            return;
        }
        
        $uploadDir = ROOTPATH . 'uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Delete old avatar
            $user = $this->userModel->getById($userId);
            if ($user['avatar'] && file_exists(ROOTPATH . 'uploads/avatars/' . $user['avatar'])) {
                unlink(ROOTPATH . 'uploads/avatars/' . $user['avatar']);
            }
            
            $this->userModel->update($userId, ['avatar' => $filename]);
            $this->activityModel->log($userId, 'avatar_uploaded', 'Profile');
            $this->setFlashMessage('success', 'Avatar uploaded successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to save avatar.');
        }
    }
    
    public function terminateSession($sessionId) {
        $userId = $this->session['user_id'];
        $this->sessionModel->destroySession($sessionId);
        $this->setFlashMessage('success', 'Session terminated.');
        redirect('profile');
    }
}

