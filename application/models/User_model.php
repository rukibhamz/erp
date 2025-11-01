<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends Base_Model {
    protected $table = 'users';
    
    public function authenticate($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE (username = ? OR email = ?) AND status = 'active'",
            [$username, $username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    public function getByEmail($email) {
        return $this->getBy('email', $email);
    }
    
    public function getByUsername($username) {
        return $this->getBy('username', $username);
    }
    
    public function create($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::create($data);
    }
    
    public function update($id, $data) {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            unset($data['password']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}

