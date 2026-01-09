<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class System_migrate extends Base_Controller {
    
    public function __construct() {
        parent::__construct();
        // Restrict to super_admin or admin
        $this->requirePermission('settings', 'write');
    }
    
    public function index() {
        $this->status();
    }
    
    public function status() {
        $data = [
            'page_title' => 'Migration Status',
            'migrations' => $this->getMigrationsStatus(),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/migrations/status', $data);
    }
    
    public function up() {
        try {
            // Set time limit to 0 for long-running migrations
            set_time_limit(0);
            
            $executed = $this->getExecuted();
            $files = $this->getFiles();
            $prefix = $this->db->getPrefix();
            $batch = $this->getBatch();
            
            $count = 0;
            foreach ($files as $file) {
                $name = basename($file);
                if (in_array($name, $executed)) continue;
                
                $sql = file_get_contents($file);
                // Clean SQL (remove comments that start with --)
                $sql = preg_replace('/^--.*$/m', '', $sql);
                
                // Execute migration
                $this->db->query($sql);
                
                // Record migration
                $this->db->query(
                    "INSERT INTO `{$prefix}migrations` (migration, batch, executed_at) VALUES (?, ?, NOW())",
                    [$name, $batch]
                );
                
                $count++;
            }
            
            $this->setFlashMessage($count . ' migration(s) executed successfully.', 'success');
        } catch (Exception $e) {
            $this->setFlashMessage('Migration failed: ' . $e->getMessage(), 'danger');
            error_log('System_migrate up error: ' . $e->getMessage());
        }
        
        redirect('system_migrate/status');
    }
    
    private function getExecuted() {
        $prefix = $this->db->getPrefix();
        // Ensure table exists
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}migrations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT(11) NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_migration` (`migration`)
        )");
        
        $results = $this->db->fetchAll("SELECT migration FROM `{$prefix}migrations` ORDER BY id ASC");
        return array_column($results, 'migration');
    }
    
    private function getFiles() {
        $dir = BASEPATH . '../database/migrations';
        $files = glob($dir . '/*.sql');
        sort($files);
        return $files;
    }
    
    private function getBatch() {
        $prefix = $this->db->getPrefix();
        $result = $this->db->fetchOne("SELECT MAX(batch) as max_batch FROM `{$prefix}migrations` ");
        return (int)($result['max_batch'] ?? 0) + 1;
    }
    
    private function getMigrationsStatus() {
        $executed = $this->getExecuted();
        $files = $this->getFiles();
        
        $status = [];
        foreach ($files as $file) {
            $name = basename($file);
            $status[] = [
                'name' => $name,
                'status' => in_array($name, $executed) ? 'executed' : 'pending'
            ];
        }
        return $status;
    }
}
