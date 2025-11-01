<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Base_Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "`";
            
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }
            
            if ($limit) {
                $sql .= " LIMIT {$limit} OFFSET {$offset}";
            }
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Base_Model getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE `{$this->primaryKey}` = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function getBy($field, $value) {
        $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` WHERE `{$field}` = ?";
        return $this->db->fetchOne($sql, [$value]);
    }
    
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    public function update($id, $data) {
        return $this->db->update($this->table, $data, "`{$this->primaryKey}` = ?", [$id]);
    }
    
    public function delete($id) {
        return $this->db->delete($this->table, "`{$this->primaryKey}` = ?", [$id]);
    }
    
    public function count($where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "` WHERE {$where}";
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] ?? 0;
    }
}

