<?php
/**
 * Automatic Migration Runner
 * 
 * Automatically runs pending database migrations on application startup
 * Safe to run multiple times - checks migration status before executing
 */

class AutoMigration {
    private static $executed = false;
    private $pdo;
    private $prefix;
    
    public function __construct() {
        // Prevent multiple executions in same request
        if (self::$executed) {
            return;
        }
        
        try {
            // Get database connection directly from config
            // Try config.installed.php first, then config.php
            $configFile = BASEPATH . 'config/config.installed.php';
            if (!file_exists($configFile)) {
                $configFile = BASEPATH . 'config/config.php';
            }
            
            if (!file_exists($configFile)) {
                // Config not found - skip migration (installer will handle it)
                return;
            }
            
            $config = require $configFile;
            
            // Check if installed
            if (!isset($config['installed']) || $config['installed'] !== true) {
                // Not installed yet - skip migration
                return;
            }
            
            $dbConfig = $config['db'] ?? [];
            
            if (empty($dbConfig['hostname']) || empty($dbConfig['database'])) {
                // Database config incomplete - skip migration
                return;
            }
            
            $host = $dbConfig['hostname'];
            $dbname = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];
            $this->prefix = $dbConfig['dbprefix'] ?? 'erp_';
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, $username, $password, $options);
            $this->runPendingMigrations();
            self::$executed = true;
        } catch (Exception $e) {
            // Silently fail - don't break application if migration fails
            error_log('AutoMigration error: ' . $e->getMessage());
        }
    }
    
    /**
     * Run pending migrations automatically
     */
    private function runPendingMigrations() {
        // Check if migrations table exists, if not create it
        $this->ensureMigrationsTable();
        
        // Get executed migrations
        $executed = $this->getExecutedMigrations();
        
        // Get migration files
        $migrationFile = __DIR__ . '/../../database/migrations/000_complete_system_migration.sql';
        
        // Check if main migration needs to run
        if (!in_array('000_complete_system_migration.sql', $executed)) {
            if (file_exists($migrationFile)) {
                $this->executeMigration($migrationFile, '000_complete_system_migration.sql');
            }
        }
    }
    
    /**
     * Ensure migrations tracking table exists
     */
    private function ensureMigrationsTable() {
        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->prefix}migrations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT(11) NOT NULL DEFAULT 1,
                `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_migration` (`migration`),
                KEY `idx_batch` (`batch`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {
            // Table might already exist, ignore error
            error_log('Migrations table check: ' . $e->getMessage());
        }
    }
    
    /**
     * Get list of executed migrations
     */
    private function getExecutedMigrations() {
        try {
            $stmt = $this->pdo->query("SELECT migration FROM `{$this->prefix}migrations` ORDER BY id ASC");
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result ?: [];
        } catch (Exception $e) {
            // If table doesn't exist yet, return empty array
            return [];
        }
    }
    
    /**
     * Execute a migration file
     */
    private function executeMigration($filePath, $migrationName) {
        try {
            $sql = file_get_contents($filePath);
            
            if (empty($sql)) {
                error_log("AutoMigration: Migration file is empty: {$migrationName}");
                return false;
            }
            
            // Remove comments and verification queries (SELECT statements that are just for checking)
            // Keep only DDL and DML statements
            $sql = preg_replace('/^--.*$/m', '', $sql);
            
            // Split by semicolons but preserve those inside strings
            $statements = $this->splitSQL($sql);
            
            // Execute each statement
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement) || 
                    stripos($statement, 'SELECT') === 0 || 
                    stripos($statement, 'SHOW') === 0 ||
                    stripos($statement, 'DESCRIBE') === 0) {
                    continue; // Skip SELECT/SHOW/DESCRIBE statements
                }
                
                try {
                    $this->pdo->exec($statement);
                } catch (Exception $e) {
                    // Log but continue - some statements might fail if already executed
                    // Ignore "table already exists" and "duplicate key" errors
                    $errorMsg = $e->getMessage();
                    if (stripos($errorMsg, 'already exists') === false && 
                        stripos($errorMsg, 'duplicate') === false &&
                        stripos($errorMsg, 'Duplicate entry') === false) {
                        error_log("AutoMigration statement warning: " . $errorMsg);
                    }
                }
            }
            
            // Record migration as executed
            $this->recordMigration($migrationName);
            
            error_log("AutoMigration: Successfully executed {$migrationName}");
            return true;
            
        } catch (Exception $e) {
            error_log("AutoMigration error executing {$migrationName}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Split SQL into individual statements
     */
    private function splitSQL($sql) {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split by semicolon, but be careful with semicolons inside strings
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString && ($char === '"' || $char === "'" || $char === '`')) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($inString && $char === $stringChar && $sql[$i-1] !== '\\') {
                $inString = false;
                $current .= $char;
            } elseif (!$inString && $char === ';') {
                $statements[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if (!empty(trim($current))) {
            $statements[] = $current;
        }
        
        return array_filter(array_map('trim', $statements));
    }
    
    /**
     * Record migration as executed
     */
    private function recordMigration($migrationName) {
        try {
            $batch = $this->getNextBatch();
            $stmt = $this->pdo->prepare(
                "INSERT INTO `{$this->prefix}migrations` (migration, batch) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE executed_at = CURRENT_TIMESTAMP"
            );
            $stmt->execute([$migrationName, $batch]);
        } catch (Exception $e) {
            error_log("AutoMigration: Failed to record migration: " . $e->getMessage());
        }
    }
    
    /**
     * Get next batch number
     */
    private function getNextBatch() {
        try {
            $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM `{$this->prefix}migrations`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['max_batch'] ?? 0) + 1;
        } catch (Exception $e) {
            return 1;
        }
    }
}

