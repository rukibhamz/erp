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
        // Also check if critical tables exist (for cases where migration was run before table was added)
        $needsMigration = !in_array('000_complete_system_migration.sql', $executed);
        
        // Check if critical tables and data exist (for cases where migration was run before updates were added)
        if (!$needsMigration) {
            try {
                // Check for tax_types table (added in previous update)
                $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}tax_types'");
                $taxTypesExists = $stmt->rowCount() > 0;
                
                // Check for entities and locations module labels (added in this update)
                $entitiesLabelExists = false;
                $locationsLabelExists = false;
                $entitiesPermsExist = false;
                $locationsPermsExist = false;
                
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}module_labels` WHERE module_code = 'entities'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $entitiesLabelExists = ($result['cnt'] ?? 0) > 0;
                } catch (Exception $e) {
                    // Table might not exist yet
                }
                
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}module_labels` WHERE module_code = 'locations'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $locationsLabelExists = ($result['cnt'] ?? 0) > 0;
                } catch (Exception $e) {
                    // Table might not exist yet
                }
                
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}permissions` WHERE module = 'entities'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $entitiesPermsExist = ($result['cnt'] ?? 0) > 0;
                } catch (Exception $e) {
                    // Table might not exist yet
                }
                
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}permissions` WHERE module = 'locations'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $locationsPermsExist = ($result['cnt'] ?? 0) > 0;
                } catch (Exception $e) {
                    // Table might not exist yet
                }
                
                // Check if admin has locations permissions
                $adminHasLocationsPerms = false;
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt 
                        FROM `{$this->prefix}role_permissions` rp
                        JOIN `{$this->prefix}roles` r ON rp.role_id = r.id
                        JOIN `{$this->prefix}permissions` p ON rp.permission_id = p.id
                        WHERE r.role_code = 'admin' AND p.module = 'locations'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $adminHasLocationsPerms = ($result['cnt'] ?? 0) > 0;
                } catch (Exception $e) {
                    // Table might not exist yet
                }
                
                // Re-run migration if any critical updates are missing
                if (!$taxTypesExists || !$entitiesLabelExists || !$locationsLabelExists || !$entitiesPermsExist || !$locationsPermsExist || !$adminHasLocationsPerms) {
                    $missing = [];
                    if (!$taxTypesExists) $missing[] = 'tax_types table';
                    if (!$entitiesLabelExists) $missing[] = 'entities module label';
                    if (!$locationsLabelExists) $missing[] = 'locations module label';
                    if (!$entitiesPermsExist) $missing[] = 'entities permissions';
                    if (!$locationsPermsExist) $missing[] = 'locations permissions';
                    if (!$adminHasLocationsPerms) $missing[] = 'admin locations permissions';
                    
                    error_log("AutoMigration: Missing updates detected (" . implode(', ', $missing) . "), re-running migration to apply them");
                    $needsMigration = true;
                }
            } catch (Exception $e) {
                // If check fails, assume migration needed
                error_log("AutoMigration: Error checking for updates: " . $e->getMessage());
            }
        }
        
        // ALWAYS check and create properties table if missing (needed for Locations controller)
        // This must run regardless of migration status to ensure table exists
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}properties'");
            $propertiesExists = $stmt->rowCount() > 0;
            
            if (!$propertiesExists) {
                error_log("AutoMigration: Properties table missing, creating it immediately...");
                $this->createPropertiesTable();
                // Verify it was created
                $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}properties'");
                if ($stmt->rowCount() > 0) {
                    error_log("AutoMigration: Properties table created successfully");
                } else {
                    error_log("AutoMigration: WARNING - Properties table creation may have failed");
                }
            }
        } catch (Exception $e) {
            error_log("AutoMigration: CRITICAL ERROR checking/creating properties table: " . $e->getMessage());
            error_log("AutoMigration: Attempting to create properties table despite error...");
            // Try to create anyway, even if check failed
            try {
                $this->createPropertiesTable();
            } catch (Exception $e2) {
                error_log("AutoMigration: Failed to create properties table: " . $e2->getMessage());
            }
        }
        
        // ALWAYS check and create space_bookings table if missing (needed for Space bookings)
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}space_bookings'");
            $bookingsExists = $stmt->rowCount() > 0;
            
            if (!$bookingsExists) {
                error_log("AutoMigration: Space_bookings table missing, creating it immediately...");
                $this->createSpaceBookingsTable();
                // Verify it was created
                $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}space_bookings'");
                if ($stmt->rowCount() > 0) {
                    error_log("AutoMigration: Space_bookings table created successfully");
                } else {
                    error_log("AutoMigration: WARNING - Space_bookings table creation may have failed");
                }
            }
        } catch (Exception $e) {
            error_log("AutoMigration: CRITICAL ERROR checking/creating space_bookings table: " . $e->getMessage());
            // Try to create anyway
            try {
                $this->createSpaceBookingsTable();
            } catch (Exception $e2) {
                error_log("AutoMigration: Failed to create space_bookings table: " . $e2->getMessage());
            }
        }
        
        if ($needsMigration) {
            if (file_exists($migrationFile)) {
                $this->executeMigration($migrationFile, '000_complete_system_migration.sql');
            }
            
            // Also run the admin locations permissions fix if it exists and hasn't been executed
            $adminLocationsFix = __DIR__ . '/../../database/migrations/002_ensure_admin_locations_permissions.sql';
            if (file_exists($adminLocationsFix) && !in_array('002_ensure_admin_locations_permissions.sql', $executed)) {
                $this->executeMigration($adminLocationsFix, '002_ensure_admin_locations_permissions.sql');
            }
        } else {
            
            // Even if main migration ran, check if admin locations fix is needed
            $adminLocationsFix = __DIR__ . '/../../database/migrations/002_ensure_admin_locations_permissions.sql';
            if (file_exists($adminLocationsFix) && !in_array('002_ensure_admin_locations_permissions.sql', $executed)) {
                // Check if admin has locations permissions
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt 
                        FROM `{$this->prefix}role_permissions` rp
                        JOIN `{$this->prefix}roles` r ON rp.role_id = r.id
                        JOIN `{$this->prefix}permissions` p ON rp.permission_id = p.id
                        WHERE r.role_code = 'admin' AND p.module = 'locations'");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $adminHasLocationsPerms = ($result['cnt'] ?? 0) > 0;
                    
                    if (!$adminHasLocationsPerms) {
                        error_log("AutoMigration: Admin missing locations permissions, running fix migration");
                        $this->executeMigration($adminLocationsFix, '002_ensure_admin_locations_permissions.sql');
                    }
                } catch (Exception $e) {
                    error_log("AutoMigration: Error checking admin locations permissions: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Ensure migrations tracking table exists
     */
    private function ensureMigrationsTable() {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->prefix}migrations` (
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
    
    /**
     * Create space_bookings table if it doesn't exist
     * This table is used for time-slot based space bookings
     */
    private function createSpaceBookingsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}space_bookings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `booking_number` varchar(50) NOT NULL UNIQUE,
                `space_id` int(11) NOT NULL,
                `tenant_id` int(11) NOT NULL,
                `booking_date` date NOT NULL,
                `start_time` time NOT NULL,
                `end_time` time NOT NULL,
                `duration_hours` decimal(10,2) DEFAULT 0.00,
                `number_of_guests` int(11) DEFAULT 0,
                `booking_type` enum('hourly','daily','multi_day') DEFAULT 'hourly',
                `base_amount` decimal(15,2) DEFAULT 0.00,
                `discount_amount` decimal(15,2) DEFAULT 0.00,
                `tax_amount` decimal(15,2) DEFAULT 0.00,
                `total_amount` decimal(15,2) DEFAULT 0.00,
                `paid_amount` decimal(15,2) DEFAULT 0.00,
                `balance_amount` decimal(15,2) DEFAULT 0.00,
                `currency` varchar(3) DEFAULT 'NGN',
                `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
                `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
                `booking_notes` text DEFAULT NULL,
                `special_requests` text DEFAULT NULL,
                `cancellation_reason` text DEFAULT NULL,
                `confirmed_at` datetime DEFAULT NULL,
                `cancelled_at` datetime DEFAULT NULL,
                `completed_at` datetime DEFAULT NULL,
                `created_by` int(11) DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `booking_number` (`booking_number`),
                KEY `space_id` (`space_id`),
                KEY `tenant_id` (`tenant_id`),
                KEY `booking_date` (`booking_date`),
                KEY `status` (`status`),
                KEY `idx_space_date_time` (`space_id`, `booking_date`, `start_time`, `end_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->pdo->exec($sql);
            
            // Verify table was created
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}space_bookings'");
            if ($stmt->rowCount() > 0) {
                error_log("AutoMigration: Space_bookings table created/verified successfully");
                return true;
            } else {
                error_log("AutoMigration: WARNING - Space_bookings table creation SQL executed but table not found");
                return false;
            }
        } catch (PDOException $e) {
            // Check if error is "table already exists" - that's OK
            if (stripos($e->getMessage(), 'already exists') !== false || 
                stripos($e->getMessage(), 'Duplicate') !== false) {
                error_log("AutoMigration: Space_bookings table already exists (this is OK)");
                return true;
            }
            error_log("AutoMigration: CRITICAL ERROR creating space_bookings table: " . $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            error_log("AutoMigration: CRITICAL ERROR creating space_bookings table: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create properties table if it doesn't exist
     * This table is used by the Locations controller
     * CRITICAL: This must always succeed for the Locations module to work
     */
    private function createPropertiesTable() {
        try {
            // Use CREATE TABLE IF NOT EXISTS to be safe
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}properties` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `property_code` varchar(50) NOT NULL UNIQUE,
                `property_name` varchar(255) NOT NULL,
                `property_type` enum('multi_purpose','standalone_building','land','other') DEFAULT 'multi_purpose',
                `address` text DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `country` varchar(100) DEFAULT NULL,
                `postal_code` varchar(20) DEFAULT NULL,
                `gps_latitude` decimal(10,8) DEFAULT NULL,
                `gps_longitude` decimal(11,8) DEFAULT NULL,
                `land_area` decimal(10,2) DEFAULT NULL COMMENT 'in square meters',
                `built_area` decimal(10,2) DEFAULT NULL COMMENT 'in square meters',
                `year_built` int(4) DEFAULT NULL,
                `year_acquired` int(4) DEFAULT NULL,
                `property_value` decimal(15,2) DEFAULT NULL,
                `manager_id` int(11) DEFAULT NULL COMMENT 'user_id',
                `status` enum('operational','under_construction','under_renovation','closed') DEFAULT 'operational',
                `ownership_status` enum('owned','leased','joint_venture') DEFAULT 'owned',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_property_code` (`property_code`),
                KEY `idx_manager_id` (`manager_id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->pdo->exec($sql);
            
            // Verify table was created
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}properties'");
            if ($stmt->rowCount() > 0) {
                error_log("AutoMigration: Properties table created/verified successfully");
                return true;
            } else {
                error_log("AutoMigration: WARNING - Properties table creation SQL executed but table not found");
                return false;
            }
        } catch (PDOException $e) {
            // Check if error is "table already exists" - that's OK
            if (stripos($e->getMessage(), 'already exists') !== false || 
                stripos($e->getMessage(), 'Duplicate') !== false) {
                error_log("AutoMigration: Properties table already exists (this is OK)");
                return true;
            }
            error_log("AutoMigration: CRITICAL ERROR creating properties table: " . $e->getMessage());
            error_log("AutoMigration: SQL was: " . substr($sql, 0, 200) . "...");
            throw $e; // Re-throw so caller knows it failed
        } catch (Exception $e) {
            error_log("AutoMigration: CRITICAL ERROR creating properties table: " . $e->getMessage());
            throw $e; // Re-throw so caller knows it failed
        }
    }
}

