<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends Base_Controller {
    private $userModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        // Only load models if database is available
        if ($this->db) {
            $this->userModel = $this->loadModel('User_model');
            $this->activityModel = $this->loadModel('Activity_model');
        }
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
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                
                $user = $this->userModel->authenticate($username, $password);
                
                if ($user) {
                    $this->session['user_id'] = $user['id'];
                    $this->session['username'] = $user['username'];
                    $this->session['email'] = $user['email'];
                    $this->session['role'] = $user['role'];
                    
                    $this->activityModel->log($user['id'], 'login', 'Auth');
                    
                    redirect('dashboard');
                } else {
                    $this->setFlashMessage('danger', 'Invalid username or password.');
                }
            }
        }
        
        $data['flash'] = $this->getFlashMessage();
        $this->loader->view('auth/login', $data);
    }
    
    public function logout() {
        if (isset($this->session['user_id'])) {
            $this->activityModel->log($this->session['user_id'], 'logout', 'Auth');
        }
        
        session_destroy();
        redirect('login');
    }
}

