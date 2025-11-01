<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company_model extends Base_Model {
    protected $table = 'companies';
    
    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        return parent::create($data);
    }
    
    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
}

