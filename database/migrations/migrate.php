<?php
/**
 * Database Migration Runner
 * 
 * Executes database migrations in order and tracks execution
 * 
 * Usage:
 *   php database/migrations/migrate.php up    - Run all pending migrations
 *   php database/migrations/migrate.php down  - Rollback last migration
 *   php database/migrations/migrate.php status - Show migration status
 */

require_once __DIR__ . '/../../application/config/database.php';

class MigrationRunner {
    private $pdo;
    private $prefix;
    private $migrationsDir;
    
    public function __construct() {
        try {
            $dbConfig = $db['default'];
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
            $this->migrationsDir = __DIR__;
            
            // Create migrations tracking table
            $this->createMigrationsTable();
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    private function createMigrationsTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->prefix}migrations` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT(11) NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_migration` (`migration`),
            KEY `idx_batch` (`batch`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    private function getExecutedMigrations() {
        $stmt = $this->pdo->query("SELECT migration FROM `{$this->prefix}migrations` ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getMigrationFiles() {
        $files = glob($this->migrationsDir . '/0*.sql');
        usort($files, function($a, $b) {
            return basename($a) <=> basename($b);
        });
        return $files;
    }
    
    private function getNextBatch() {
        $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM `{$this->prefix}migrations`");
        $result = $stmt->fetch();
        return ($result['max_batch'] ?? 0) + 1;
    }
    
    public function up() {
        echo "========================================\n";
        echo "RUNNING MIGRATIONS\n";
        echo "========================================\n\n";
        
        $executed = $this->getExecutedMigrations();
        $files = $this->getMigrationFiles();
        $batch = $this->getNextBatch();
        
        $pending = array_filter($files, function($file) use ($executed) {
            return !in_array(basename($file), $executed);
        });
        
        if (empty($pending)) {
            echo "✓ No pending migrations\n";
            return;
        }
        
        foreach ($pending as $file) {
            $migrationName = basename($file);
            echo "Running: {$migrationName}...\n";
            
            try {
                $sql = file_get_contents($file);
                
                // Remove verification queries (SELECT statements) for execution
                $sql = preg_replace('/^--.*$/m', '', $sql);
                
                // Execute migration
                $this->pdo->exec($sql);
                
                // Record migration
                $stmt = $this->pdo->prepare(
                    "INSERT INTO `{$this->prefix}migrations` (migration, batch) VALUES (?, ?)"
                );
                $stmt->execute([$migrationName, $batch]);
                
                echo "✓ {$migrationName} completed\n\n";
            } catch (PDOException $e) {
                echo "✗ ERROR: {$migrationName} failed\n";
                echo "  " . $e->getMessage() . "\n";
                die("\nMigration failed. Please fix errors and try again.\n");
            }
        }
        
        echo "========================================\n";
        echo "ALL MIGRATIONS COMPLETED\n";
        echo "========================================\n";
    }
    
    public function down() {
        echo "========================================\n";
        echo "ROLLING BACK LAST MIGRATION BATCH\n";
        echo "========================================\n\n";
        
        // Get last batch
        $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM `{$this->prefix}migrations`");
        $result = $stmt->fetch();
        $lastBatch = $result['max_batch'] ?? 0;
        
        if ($lastBatch == 0) {
            echo "No migrations to rollback\n";
            return;
        }
        
        // Get migrations in last batch
        $stmt = $this->pdo->prepare("SELECT migration FROM `{$this->prefix}migrations` WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "WARNING: Rollback functionality requires manual SQL scripts.\n";
        echo "Migrations in batch {$lastBatch}:\n";
        foreach ($migrations as $migration) {
            echo "  - {$migration}\n";
        }
        echo "\nTo rollback, manually:\n";
        echo "1. Review the migration file\n";
        echo "2. Create reverse SQL statements\n";
        echo "3. Execute rollback SQL\n";
        echo "4. Remove from migrations table:\n";
        echo "   DELETE FROM `{$this->prefix}migrations` WHERE batch = {$lastBatch};\n";
    }
    
    public function status() {
        echo "========================================\n";
        echo "MIGRATION STATUS\n";
        echo "========================================\n\n";
        
        $executed = $this->getExecutedMigrations();
        $files = $this->getMigrationFiles();
        
        echo "Executed Migrations (" . count($executed) . "):\n";
        foreach ($executed as $migration) {
            echo "  ✓ {$migration}\n";
        }
        
        echo "\nPending Migrations (" . (count($files) - count($executed)) . "):\n";
        foreach ($files as $file) {
            $migration = basename($file);
            if (!in_array($migration, $executed)) {
                echo "  ⏳ {$migration}\n";
            }
        }
        
        echo "\n";
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$command = $argv[1] ?? 'status';

$runner = new MigrationRunner();

switch ($command) {
    case 'up':
        $runner->up();
        break;
    case 'down':
        $runner->down();
        break;
    case 'status':
    default:
        $runner->status();
        break;
}

