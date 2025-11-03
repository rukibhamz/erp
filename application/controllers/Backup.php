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
    
    private function createBackup() {
        $backupDir = BASEPATH . '../backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . 'backup_' . $timestamp . '.sql';
        
        $config = require BASEPATH . 'config/config.php';
        $dbConfig = $config['database'];
        
        // Create SQL dump
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg($dbConfig['hostname']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($backupFile)) {
            // Keep only last 30 backups
            $this->cleanupOldBackups($backupDir, 30);
            return $backupFile;
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
            $dbConfig = $config['database'];
            
            // Restore database
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
                escapeshellarg($dbConfig['hostname']),
                escapeshellarg($dbConfig['username']),
                escapeshellarg($dbConfig['password']),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($tempFile)
            );
            
            exec($command, $output, $returnVar);
            @unlink($tempFile);
            
            if ($returnVar === 0) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Backup', 'Restored system from backup');
                $this->setFlashMessage('success', 'Database restored successfully from backup.');
            } else {
                throw new Exception('Restore failed: ' . implode("\n", $output));
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

