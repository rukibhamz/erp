<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = [];
    
    private function __construct() {
        // Load config - prefer config.installed.php if it exists
        $configFile = BASEPATH . 'config/config.installed.php';
        if (!file_exists($configFile)) {
            $configFile = BASEPATH . 'config/config.php';
        }
        
        if (!file_exists($configFile)) {
            throw new Exception('Configuration file not found.');
        }
        
        $config = require $configFile;
        $this->config = $config['db'] ?? [];
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            if (empty($this->config['hostname']) || empty($this->config['database'])) {
                throw new PDOException('Database configuration is incomplete.');
            }
            
            $dsn = "mysql:host={$this->config['hostname']};dbname={$this->config['database']};charset={$this->config['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            $this->connection = null; // Don't die, just set connection to null
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function getPrefix() {
        return $this->config['dbprefix'];
    }
    
    public function query($sql, $params = []) {
        try {
            if (!$this->connection) {
                throw new Exception('Database connection not available.');
            }
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database Query Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: false;
    }
    
    public function insert($table, $data) {
        $table = $this->config['dbprefix'] . $table;
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, $values);
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $table = $this->config['dbprefix'] . $table;
        $fields = array_keys($data);
        $values = array_values($data);
        
        $set = [];
        foreach ($fields as $field) {
            $set[] = "`{$field}` = ?";
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $set) . " WHERE {$where}";
        $params = array_merge($values, $whereParams);
        
        return $this->query($sql, $params)->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $table = $this->config['dbprefix'] . $table;
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    public function execute($sql, $params = []) {
        return $this->query($sql, $params);
    }
}

