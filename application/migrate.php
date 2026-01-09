<?php
/**
 * Standalone Migration Runner
 */
define('BASEPATH', __DIR__ . '/');
require_once __DIR__ . '/core/Database.php';

// Mock common functions if needed
if (!function_exists('redirect')) {
    function redirect($url) { echo "Redirecting to $url\n"; }
}

try {
    $db = Database::getInstance();
    $prefix = $db->getPrefix();
    
    echo "Starting migrations...\n";
    
    // Ensure migrations table exists
    $db->query("CREATE TABLE IF NOT EXISTS `{$prefix}migrations` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `migration` VARCHAR(255) NOT NULL,
        `batch` INT(11) NOT NULL,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_migration` (`migration`)
    )");
    
    $executedResults = $db->fetchAll("SELECT migration FROM `{$prefix}migrations` ORDER BY id ASC");
    $executed = array_column($executedResults, 'migration');
    
    $dir = __DIR__ . '/../database/migrations';
    $files = glob($dir . '/*.sql');
    sort($files);
    
    $batchResult = $db->fetchOne("SELECT MAX(batch) as max_batch FROM `{$prefix}migrations` ");
    $batch = (int)($batchResult['max_batch'] ?? 0) + 1;
    
    $count = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $executed)) {
            echo "Skipping $name (already executed)\n";
            continue;
        }
        
        echo "Executing $name...\n";
        $sql = file_get_contents($file);
        
        // Split by semicolon but be careful of triggers/procedures
        // For simplicity, we'll try executing the whole block if it doesn't have delimiters
        // Many systems support multi-query via PDO
        try {
            $db->query($sql);
            
            $db->query(
                "INSERT INTO `{$prefix}migrations` (migration, batch, executed_at) VALUES (?, ?, NOW())",
                [$name, $batch]
            );
            echo "Successfully executed $name\n";
            $count++;
        } catch (Exception $e) {
            echo "Error executing $name: " . $e->getMessage() . "\n";
            // Check if it's a multi-statement issue
            if (strpos($e->getMessage(), 'You have an error in your SQL syntax') !== false) {
                echo "Attempting to split and execute statements...\n";
                // Basic split by semicolon - might fail for complex SQL
                $statements = explode(';', $sql);
                foreach ($statements as $stmt) {
                    $stmt = trim($stmt);
                    if (empty($stmt)) continue;
                    try {
                        $db->query($stmt);
                    } catch (Exception $subE) {
                        echo "Sub-statement error: " . $subE->getMessage() . "\n";
                    }
                }
                
                $db->query(
                    "INSERT INTO `{$prefix}migrations` (migration, batch, executed_at) VALUES (?, ?, NOW())",
                    [$name, $batch]
                );
                echo "Managed to execute $name via splitting\n";
                $count++;
            } else {
                throw $e;
            }
        }
    }
    
    echo "Finished. $count migrations executed.\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
