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
            'can_restore' => isset($this->session['role']) && $this->session['role'] === 'super_admin',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/backup', $data);
    }
    
    public function create() {
        $this->requirePermission('settings', 'update');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('settings/backup');
            return;
        }
        check_csrf();
        
        try {
            $backupFile = $this->createBackup();
            if ($backupFile) {
                $this->activityModel->log($this->session['user_id'], 'backup', 'Backup', 'Created system backup: ' . basename($backupFile));
                $this->setFlashMessage('success', 'Backup created successfully: ' . basename($backupFile));
            } else {
                $this->setFlashMessage('danger', 'Failed to create backup.');
            }
        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Failed to create backup. Please try again.');
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
        
        $dbConfig = $this->getDbClientConfig();
        if (!$dbConfig) {
            return false;
        }
        
        $mysqlConfigFile = $this->writeMysqlConfigFile('mysql_backup_', $dbConfig);
        if (!$mysqlConfigFile) {
            return false;
        }
        
        try {
            $command = sprintf(
                'mysqldump --defaults-file=%s %s > %s 2>&1',
                escapeshellarg($mysqlConfigFile),
                escapeshellarg($dbConfig['database']),
                escapeshellarg($backupFile)
            );
            
            exec($command, $output, $returnVar);
            @unlink($mysqlConfigFile);
            
            if ($returnVar === 0 && file_exists($backupFile)) {
                $this->cleanupOldBackups($backupDir, 30);
                return $backupFile;
            }
            
            error_log('Backup failed: ' . implode("\n", $output));
        } catch (Exception $e) {
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
        $this->requireRole('super_admin');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['backup_file'])) {
            $this->setFlashMessage('danger', 'No backup file uploaded.');
            redirect('settings/backup');
            return;
        }
        
        check_csrf();

        $confirmation = trim($_POST['restore_confirmation'] ?? '');
        if ($confirmation !== 'RESTORE') {
            $this->setFlashMessage('danger', 'Restore cancelled: type RESTORE to confirm.');
            redirect('settings/backup');
            return;
        }

        $validation = validateBackupUpload($_FILES['backup_file']);
        if (!$validation['valid']) {
            $this->setFlashMessage('danger', $validation['error'] ?? 'Invalid backup file.');
            redirect('settings/backup');
            return;
        }
        
        $uploadedName = basename($_FILES['backup_file']['name']);
        $tempFile = sys_get_temp_dir() . '/restore_' . uniqid('', true) . '.sql';
        
        try {
            $this->createBackup();
            
            if (!move_uploaded_file($_FILES['backup_file']['tmp_name'], $tempFile)) {
                throw new Exception('Failed to upload backup file');
            }
            
            $dbConfig = $this->getDbClientConfig(true);
            if (!$dbConfig) {
                throw new Exception('Incomplete database configuration');
            }
            
            $mysqlConfigFile = $this->writeMysqlConfigFile('mysql_restore_', $dbConfig);
            if (!$mysqlConfigFile) {
                throw new Exception('Failed to create MySQL config file');
            }
            
            try {
                $command = sprintf(
                    'mysql --defaults-file=%s %s < %s 2>&1',
                    escapeshellarg($mysqlConfigFile),
                    escapeshellarg($dbConfig['database']),
                    escapeshellarg($tempFile)
                );
                
                exec($command, $output, $returnVar);
                @unlink($mysqlConfigFile);
                @unlink($tempFile);
                
                if ($returnVar === 0) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $this->activityModel->log(
                        $this->session['user_id'],
                        'restore',
                        'Backup',
                        'Restored database from backup file: ' . $uploadedName . ' (IP: ' . $ip . ')'
                    );
                    error_log('SECURITY: Database restore by user ' . $this->session['user_id'] . ' from ' . $uploadedName . ' IP ' . $ip);
                    $this->setFlashMessage('success', 'Database restored successfully from backup.');
                } else {
                    throw new Exception('Restore failed: ' . implode("\n", $output));
                }
            } catch (Exception $e) {
                @unlink($mysqlConfigFile);
                @unlink($tempFile);
                throw $e;
            }
        } catch (Exception $e) {
            error_log('Restore error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error restoring backup. Please check the logs.');
        }
        
        redirect('settings/backup');
    }

    /**
     * @param bool $forRestore Use dedicated restore credentials when configured
     */
    private function getDbClientConfig($forRestore = false) {
        $config = load_app_config();
        $dbConfig = $config['db'] ?? $config['database'] ?? [];

        if (empty($dbConfig['hostname']) || empty($dbConfig['database']) || !isset($dbConfig['password'])) {
            error_log('Backup: Incomplete database configuration');
            return null;
        }

        $username = $dbConfig['username'] ?? '';
        $password = $dbConfig['password'] ?? '';

        if ($forRestore && !empty($config['db_restore']['username'])) {
            $username = $config['db_restore']['username'];
            $password = $config['db_restore']['password'] ?? '';
        } elseif ($forRestore) {
            error_log('Backup restore: DB_RESTORE_USER not configured; using main database credentials');
        }

        if ($username === '') {
            error_log('Backup: Database username missing');
            return null;
        }

        return [
            'hostname' => $dbConfig['hostname'],
            'username' => $username,
            'password' => $password,
            'database' => $dbConfig['database'],
        ];
    }

    private function writeMysqlConfigFile($prefix, array $dbConfig) {
        $mysqlConfigFile = sys_get_temp_dir() . '/' . $prefix . uniqid('', true) . '.cnf';
        $mysqlConfigContent = "[client]\n";
        $mysqlConfigContent .= "host=" . $dbConfig['hostname'] . "\n";
        $mysqlConfigContent .= "user=" . $dbConfig['username'] . "\n";
        $mysqlConfigContent .= "password=" . $dbConfig['password'] . "\n";

        if (file_put_contents($mysqlConfigFile, $mysqlConfigContent) === false) {
            error_log('Backup: Failed to create MySQL config file');
            return false;
        }

        chmod($mysqlConfigFile, 0600);
        return $mysqlConfigFile;
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
