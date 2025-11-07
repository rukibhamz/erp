<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Permission_model extends Base_Model {
    protected $table = 'permissions';
    
    public function getAllByModule() {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` ORDER BY module, permission";
        $permissions = $this->db->fetchAll($sql);
        
        $grouped = [];
        foreach ($permissions as $perm) {
            $grouped[$perm['module']][] = $perm;
        }
        
        return $grouped;
    }
    
    public function getAllModules() {
        $sql = "SELECT DISTINCT module FROM `" . $this->db->getPrefix() . $this->table . "` ORDER BY module";
        $result = $this->db->fetchAll($sql);
        return array_column($result, 'module');
    }
    
    public function getByModule($module) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE module = ? ORDER BY permission";
        return $this->db->fetchAll($sql, [$module]);
    }
    
    public function getAll() {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` ORDER BY module, permission";
        return $this->db->fetchAll($sql);
    }
}

