<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Backup extends Base_Controller {
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $backupDir = BASEPATH . '../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backups = $this->getBackupList($backupDir);
        
        $data = [
            'page_title' => 'Backup & Restore',
            'backups' => $backups,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/backup', $data);
    }
    
    public function create() {
        $this->requirePermission('settings', 'update');
        
        try {
            $backupFile = $this->createBackup();
            if ($backupFile) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Backup', 'Created system backup');
                $this->setFlashMessage('success', 'Backup created successfully: ' . basename($backupFile));
            } else {
                $this->setFlashMessage('danger', 'Failed to create backup.');
            }
        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error creating backup: ' . $e->getMessage());
        }
        
        redirect('settings/backup');
    }
    
    public function download($filename) {
        $this->requirePermission('settings', 'read');
        
        $backupDir = BASEPATH . '../backups/';
        $filePath = $backupDir . basename($filename);
        
        if (file_exists($filePath) && is_readable($filePath)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            $this->setFlashMessage('danger', 'Backup file not found.');
            redirect('settings/backup');
        }
    }
    
    /**
     * Create database backup
     * 
     * SECURITY: Uses MySQL configuration file to avoid exposing password in process list.
     * This prevents command injection and credential exposure.
     * 
     * @return string|false Backup file path or false on failure
     */
    private function createBackup() {
        $backupDir = BASEPATH . '../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . 'backup_' . $timestamp . '.sql';
        
        $config = require BASEPATH . 'config/config.php';
        $dbConfig = $config['db'] ?? $config['database'] ?? [];
        
        // SECURITY: Validate database configuration
        if (empty($dbConfig['hostname']) || empty($dbConfig['username']) || 
            empty($dbConfig['database']) || !isset($dbConfig['password'])) {
            error_log('Backup: Incomplete database configuration');
            return false;
        }
        
        // SECURITY: Create temporary MySQL config file to avoid password in command line
        $mysqlConfigFile = sys_get_temp_dir() . '/mysql_backup_' . uniqid() . '.cnf';
        $mysqlConfigContent = "[client]\n";
        $mysqlConfigContent .= "host=" . $dbConfig['hostname'] . "\n";
        $mysqlConfigContent .= "user=" . $dbConfig['username'] . "\n";
        $mysqlConfigContent .= "password=" . $dbConfig['password'] . "\n";
        
        // SECURITY: Set restrictive permissions on config file (readable only by owner)
        if (file_put_contents($mysqlConfigFile, $mysqlConfigContent) === false) {
            error_log('Backup: Failed to create MySQL config file');
            return false;
        }
        
        // Set file permissions to 600 (read/write for owner only)
        chmod($mysqlConfigFile, 0600);
        
        try {
            // SECURITY: Use --defaults-file to read credentials from file instead of command line
            // This prevents password from appearing in process list
            $command = sprintf(
                'mysqldump --defaults-file=%s %s > %s 2>&1',
                escapeshellarg($mysqlConfigFile),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($backupFile)
            );
            
            exec($command, $output, $returnVar);
            
            // SECURITY: Always delete config file, even on error
            @unlink($mysqlConfigFile);
            
            if ($returnVar === 0 && file_exists($backupFile)) {
                // Keep only last 30 backups
                $this->cleanupOldBackups($backupDir, 30);
                return $backupFile;
            } else {
                // Log error output for debugging (but don't expose to user)
                error_log('Backup failed: ' . implode("\n", $output));
            }
        } catch (Exception $e) {
            // SECURITY: Ensure config file is deleted even on exception
            @unlink($mysqlConfigFile);
            error_log('Backup error: ' . $e->getMessage());
        }
        
        return false;
    }
    
    private function cleanupOldBackups($dir, $keepCount = 30) {
        $files = glob($dir . 'backup_*.sql');
        if (count($files) > $keepCount) {
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $filesToDelete = array_slice($files, $keepCount);
            foreach ($filesToDelete as $file) {
                @unlink($file);
            }
        }
    }
    
    private function getBackupList($dir) {
        $files = glob($dir . 'backup_*.sql');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created' => date('Y-m-d H:i:s', filemtime($file)),
                'size_formatted' => $this->formatBytes(filesize($file))
            ];
        }
        
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });
        
        return $backups;
    }
    
    public function restore() {
        $this->requirePermission('settings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['backup_file'])) {
            $this->setFlashMessage('danger', 'No backup file uploaded.');
            redirect('settings/backup');
        }
        
        try {
            // Create backup before restore
            $this->createBackup();
            
            $uploadedFile = $_FILES['backup_file'];
            $tempFile = sys_get_temp_dir() . '/' . basename($uploadedFile['tmp_name']);
            
            if (!move_uploaded_file($uploadedFile['tmp_name'], $tempFile)) {
                throw new Exception('Failed to upload backup file');
            }
            
            $config = require BASEPATH . 'config/config.php';
            $dbConfig = $config['db'] ?? $config['database'] ?? [];
            
            // SECURITY: Validate database configuration
            if (empty($dbConfig['hostname']) || empty($dbConfig['username']) || 
                empty($dbConfig['database']) || !isset($dbConfig['password'])) {
                throw new Exception('Incomplete database configuration');
            }
            
            // SECURITY: Create temporary MySQL config file to avoid password in command line
            $mysqlConfigFile = sys_get_temp_dir() . '/mysql_restore_' . uniqid() . '.cnf';
            $mysqlConfigContent = "[client]\n";
            $mysqlConfigContent .= "host=" . $dbConfig['hostname'] . "\n";
            $mysqlConfigContent .= "user=" . $dbConfig['username'] . "\n";
            $mysqlConfigContent .= "password=" . $dbConfig['password'] . "\n";
            
            if (file_put_contents($mysqlConfigFile, $mysqlConfigContent) === false) {
                throw new Exception('Failed to create MySQL config file');
            }
            
            // Set file permissions to 600 (read/write for owner only)
            chmod($mysqlConfigFile, 0600);
            
            try {
                // SECURITY: Use --defaults-file to read credentials from file
                $command = sprintf(
                    'mysql --defaults-file=%s %s < %s 2>&1',
                    escapeshellarg($mysqlConfigFile),
                    escapeshellarg($dbConfig['database']),
                    escapeshellarg($tempFile)
                );
                
                exec($command, $output, $returnVar);
                
                // SECURITY: Always delete config file and temp file
                @unlink($mysqlConfigFile);
                @unlink($tempFile);
                
                if ($returnVar === 0) {
                    $this->activityModel->log($this->session['user_id'], 'update', 'Backup', 'Restored system from backup');
                    $this->setFlashMessage('success', 'Database restored successfully from backup.');
                } else {
                    throw new Exception('Restore failed: ' . implode("\n", $output));
                }
            } catch (Exception $e) {
                // SECURITY: Ensure config file is deleted even on exception
                @unlink($mysqlConfigFile);
                @unlink($tempFile);
                throw $e;
            }
        } catch (Exception $e) {
            error_log('Restore error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error restoring backup: ' . $e->getMessage());
        }
        
        redirect('settings/backup');
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

