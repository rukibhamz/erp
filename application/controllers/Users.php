<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends Base_Controller {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = $this->loadModel('User_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Users',
            'users' => $this->userModel->getAll(null, 0, 'created_at DESC'),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('users/index', $data);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? 'user',
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Validate
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                $this->setFlashMessage('danger', 'All fields are required.');
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
            
            $this->userModel->create($data);
            $this->setFlashMessage('success', 'User created successfully.');
            redirect('users');
        }
        
        $data = ['page_title' => 'Create User', 'flash' => $this->getFlashMessage()];
        $this->loadView('users/create', $data);
    }
    
    public function edit($id) {
        $user = $this->userModel->getById($id);
        
        if (!$user) {
            $this->setFlashMessage('danger', 'User not found.');
            redirect('users');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'role' => $_POST['role'] ?? 'user',
                'status' => $_POST['status'] ?? 'active'
            ];
            
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            
            $this->userModel->update($id, $data);
            $this->setFlashMessage('success', 'User updated successfully.');
            redirect('users');
        }
        
        $data = ['page_title' => 'Edit User', 'user' => $user, 'flash' => $this->getFlashMessage()];
        $this->loadView('users/edit', $data);
    }
}

