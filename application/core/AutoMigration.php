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
        } catch (\Throwable $e) {
            // Silently fail - don't break application if migration fails
            // Use Throwable to also catch TypeError/Error (e.g. execute() on false)
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
        $needsMigration = !in_array('000_complete_system_migration.sql', $executed);
        
        // Check if critical tables and data exist (for cases where migration was run before updates were added)
        if (!$needsMigration) {
            try {
                // Check for tax_types table
                $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}tax_types'");
                $taxTypesExists = ($stmt && count($stmt->fetchAll()) > 0);
                
                // Check for module labels
                $entitiesLabelExists = false;
                $locationsLabelExists = false;
                $staffManagementLabelExists = false;
                
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}module_labels` WHERE module_code = 'entities'");
                    if ($stmt) {
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $entitiesLabelExists = ($result[0]['cnt'] ?? 0) > 0;
                    }
                } catch (Exception $e) {}
                
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}module_labels` WHERE module_code = 'locations'");
                    if ($stmt) {
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $locationsLabelExists = ($result[0]['cnt'] ?? 0) > 0;
                    }
                } catch (Exception $e) {}
                
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}module_labels` WHERE module_code = 'staff_management'");
                    if ($stmt) {
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $staffManagementLabelExists = ($result[0]['cnt'] ?? 0) > 0;
                    }
                } catch (Exception $e) {}
                
                // Re-run migration if any critical updates are missing
                if (!$taxTypesExists || !$entitiesLabelExists || !$locationsLabelExists || !$staffManagementLabelExists) {
                    error_log("AutoMigration: Missing updates detected, re-running migration to apply them");
                    $needsMigration = true;
                }
            } catch (Exception $e) {
                error_log("AutoMigration: Error checking for updates: " . $e->getMessage());
            }
        }
        
        // Run main migration if needed
        if ($needsMigration && file_exists($migrationFile)) {
            $this->executeMigration($migrationFile, '000_complete_system_migration.sql');
        }

        // Run all other numbered migrations automatically
        $this->runNumberedMigrations($executed);

        
        // ALWAYS check and create properties table if missing
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}properties'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $this->createPropertiesTable();
            }
        } catch (Exception $e) {
            error_log("AutoMigration: Error checking properties table: " . $e->getMessage());
        }
        
        // ALWAYS check and create space_bookings table if missing
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}space_bookings'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $this->createSpaceBookingsTable();
            }
        } catch (Exception $e) {
            error_log("AutoMigration: Error checking space_bookings table: " . $e->getMessage());
        }
        
        // ALWAYS check and create rate_limits table if missing (for security rate limiting)
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}rate_limits'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS `{$this->prefix}rate_limits` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `identifier` VARCHAR(255) NOT NULL,
                        `ip_address` VARCHAR(45),
                        `created_at` DATETIME NOT NULL,
                        INDEX `idx_identifier` (`identifier`),
                        INDEX `idx_created_at` (`created_at`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                error_log("AutoMigration: Created rate_limits table");
            }
        } catch (Exception $e) {
            error_log("AutoMigration: Error creating rate_limits table: " . $e->getMessage());
        }

        // ALWAYS check and create utilities tables if missing
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}meters'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $this->createUtilitiesTables();
            }
        } catch (Exception $e) {
            error_log("AutoMigration: Error checking utilities tables: " . $e->getMessage());
        }

        // ALWAYS check and create tax tables if missing
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}vat_returns'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $this->createTaxTables();
            }
        } catch (Exception $e) {
            error_log("AutoMigration: Error checking tax tables: " . $e->getMessage());
        }

        // ALWAYS ensure modules table
        try {
            $this->ensureModulesTable();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring modules table: " . $e->getMessage());
        }
        
        // ALWAYS fix employees table
        try {
            $this->fixEmployeesTable();
        } catch (Exception $e) {
            error_log("AutoMigration: Error fixing employees table: " . $e->getMessage());
        }
        
        // ALWAYS ensure default cash account
        try {
            $this->ensureDefaultCashAccount();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring default cash account: " . $e->getMessage());
        }
        
        // ALWAYS standardize accounts table
        try {
            $this->standardizeAccountsTable();
        } catch (Exception $e) {
            error_log("AutoMigration: Error standardizing accounts table: " . $e->getMessage());
        }
        
        // ALWAYS install default COA
        try {
            $this->installDefaultCOA();
        } catch (Exception $e) {
            error_log("AutoMigration: Error installing default COA: " . $e->getMessage());
        }
        
        // ALWAYS install Phase 12 accounts
        try {
            $this->installPhase12Accounts();
        } catch (Exception $e) {
            error_log("AutoMigration: Error installing Phase 12 accounts: " . $e->getMessage());
        }
        
        // ALWAYS add performance indexes
        try {
            $this->addPerformanceIndexes();
        } catch (Exception $e) {
            error_log("AutoMigration: Error adding performance indexes: " . $e->getMessage());
        }
        
        // ALWAYS ensure inventory valuation view
        try {
            $this->ensureInventoryValuationView();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring inventory valuation view: " . $e->getMessage());
        }
        
        // ALWAYS ensure PAYE tax brackets
        try {
            $this->ensurePAYEBrackets();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring PAYE tax brackets: " . $e->getMessage());
        }
        
        // ALWAYS ensure default POS terminal
        try {
            $this->ensureDefaultPOSTerminal();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring default POS terminal: " . $e->getMessage());
        }

        // ALWAYS run new feature migrations (Wholesale Pricing & Education Tax)
        try {
            $this->applyNewFeatureMigrations();
        } catch (Exception $e) {
            error_log("AutoMigration: Error applying new feature migrations: " . $e->getMessage());
        }
        
        // ALWAYS ensure items table has is_sellable column
        try {
            $this->ensureItemsSellableColumn();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring items sellable column: " . $e->getMessage());
        }
        
        // ALWAYS ensure bookings table has all required columns for booking wizard
        try {
            $this->ensureBookingsTableColumns();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring bookings table columns: " . $e->getMessage());
        }
        
        // ALWAYS ensure payment_transactions table exists for payment gateway integration
        try {
            $this->ensurePaymentTransactionsTable();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring payment_transactions table: " . $e->getMessage());
        }
        
        // ALWAYS ensure transactions table has accounting columns
        try {
            $this->ensureTransactionsTableColumns();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring transactions table columns: " . $e->getMessage());
        }
        
        // ALWAYS ensure payment_gateways table exists with Paystack as default
        try {
            $this->ensurePaymentGatewaysTable();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring payment_gateways table: " . $e->getMessage());
        }
        
        // Ensure booking-related tables exist
        try {
            $this->ensureBookingResourcesTable();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring booking_resources table: " . $e->getMessage());
        }
        
        try {
            $this->ensureBookingSlotsTable();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring booking_slots table: " . $e->getMessage());
        }
        
        try {
            $this->ensureBookingPaymentsTable();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring booking_payments table: " . $e->getMessage());
        }
        
        // ALWAYS ensure space_bookings has payment verification columns
        try {
            $this->ensureSpaceBookingsTableColumns();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring space_bookings columns: " . $e->getMessage());
        }
        
        // Ensure customer role exists for guest bookings
        try {
            $this->ensureCustomerRole();
        } catch (Exception $e) {
            error_log("AutoMigration: Error ensuring customer role: " . $e->getMessage());
        }
        
        // Apply best-practice manager permissions
        try {
            $this->applyManagerBestPracticePermissions();
        } catch (Exception $e) {
            error_log("AutoMigration: Error applying manager permissions: " . $e->getMessage());
        }
        
        // Apply best-practice accountant permissions
        try {
            $this->applyAccountantBestPracticePermissions();
        } catch (Exception $e) {
            error_log("AutoMigration: Error applying accountant permissions: " . $e->getMessage());
        }
            
        // Check if admin locations fix is needed
        $adminLocationsFix = __DIR__ . '/../../database/migrations/002_ensure_admin_locations_permissions.sql';
        if (file_exists($adminLocationsFix) && !in_array('002_ensure_admin_locations_permissions.sql', $executed)) {
            // Check if admin has locations permissions
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) as cnt 
                    FROM `{$this->prefix}role_permissions` rp
                    JOIN `{$this->prefix}roles` r ON rp.role_id = r.id
                    JOIN `{$this->prefix}permissions` p ON rp.permission_id = p.id
                    WHERE r.role_code = 'admin' AND p.module = 'locations'");
                
                $adminHasLocationsPerms = false;
                if ($stmt) {
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $adminHasLocationsPerms = ($result['cnt'] ?? 0) > 0;
                }
                
                if (!$adminHasLocationsPerms) {
                    $this->executeMigration($adminLocationsFix, '002_ensure_admin_locations_permissions.sql');
                }
            } catch (Exception $e) {
                error_log("AutoMigration: Error checking admin locations permissions: " . $e->getMessage());
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
            
        } catch (\Throwable $e) {
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
            if ($stmt === false) {
                // prepare() returned false - try raw exec as fallback
                $safeName = addslashes($migrationName);
                $this->pdo->exec(
                    "INSERT INTO `{$this->prefix}migrations` (migration, batch) VALUES ('{$safeName}', {$batch})
                     ON DUPLICATE KEY UPDATE executed_at = CURRENT_TIMESTAMP"
                );
                return;
            }
            $stmt->execute([$migrationName, $batch]);
        } catch (\Throwable $e) {
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
            if ($stmt && count($stmt->fetchAll()) > 0) {
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
            if ($stmt && count($stmt->fetchAll()) > 0) {
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
    
    /**
     * Create utilities tables if they don't exist
     */
    private function createUtilitiesTables() {
        try {
            // Load utilities migration function
            $migrationFile = __DIR__ . '/../../install/migrations_utilities.php';
            if (file_exists($migrationFile)) {
                require_once $migrationFile;
                if (function_exists('runUtilitiesMigrations')) {
                    runUtilitiesMigrations($this->pdo, $this->prefix);
                    error_log("AutoMigration: Utilities tables created successfully");
                    return true;
                }
            }
            error_log("AutoMigration: WARNING - Utilities migration file or function not found");
            return false;
        } catch (Exception $e) {
            error_log("AutoMigration: CRITICAL ERROR creating utilities tables: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create tax tables if they don't exist
     */
    private function createTaxTables() {
        try {
            // Load tax migration function
            $migrationFile = __DIR__ . '/../../install/migrations_tax.php';
            if (file_exists($migrationFile)) {
                require_once $migrationFile;
                if (function_exists('runTaxMigrations')) {
                    runTaxMigrations($this->pdo, $this->prefix);
                    error_log("AutoMigration: Tax tables created successfully");
                    return true;
                }
            }
            error_log("AutoMigration: WARNING - Tax migration file or function not found");
            return false;
        } catch (Exception $e) {
            error_log("AutoMigration: CRITICAL ERROR creating tax tables: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Apply new feature migrations (Wholesale Pricing, Education Tax, Report Views)
     */
    private function applyNewFeatureMigrations() {
        try {
            $migrationFile = __DIR__ . '/../../install/migrations_new_features.php';
            if (file_exists($migrationFile)) {
                require_once $migrationFile;
                if (function_exists('runNewFeatureMigrations')) {
                    runNewFeatureMigrations($this->pdo, $this->prefix);
                }
                if (function_exists('fixNewFeatureColumns')) {
                    fixNewFeatureColumns($this->pdo, $this->prefix);
                }
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR applying new feature migrations: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure modules table exists and staff_management module is registered
     * Works for both new and existing installations
     */
    private function ensureModulesTable() {
        try {
            // Check if modules table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}modules'");
            $modulesTableExists = ($stmt && count($stmt->fetchAll()) > 0);

            // Load modules migration function
            $migrationFile = __DIR__ . '/../../install/migrations_modules.php';
            if (file_exists($migrationFile)) {
                require_once $migrationFile;
                if (function_exists('migrations_modules')) {
                    $modulesMigration = migrations_modules($this->prefix);
                    
                    // Create modules table if it doesn't exist
                    if (!$modulesTableExists && isset($modulesMigration['tables']['modules'])) {
                        $this->pdo->exec($modulesMigration['tables']['modules']);
                        error_log("AutoMigration: Modules table created successfully");
                    }
                    
                    // Insert/update modules (INSERT IGNORE handles existing installations)
                    if (!empty($modulesMigration['inserts'])) {
                        foreach ($modulesMigration['inserts'] as $insertSql) {
                            try {
                                $this->pdo->exec($insertSql);
                            } catch (Exception $e) {
                                // Ignore duplicate key errors (module already exists)
                                if (stripos($e->getMessage(), 'duplicate') === false) {
                                    error_log("AutoMigration: Warning inserting module: " . $e->getMessage());
                                }
                            }
                        }
                        error_log("AutoMigration: Modules inserted/updated successfully");
                    }
                }
            }

            // Also ensure staff_management exists in erp_module_labels (handled by main migration)
            // But check and add if missing for existing installations
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}module_labels` WHERE module_code = 'staff_management'");
                if ($stmt) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $staffManagementLabelExists = ($result[0]['cnt'] ?? 0) > 0;
                } else {
                    $staffManagementLabelExists = false;
                }
                
                if (!$staffManagementLabelExists) {
                    // Insert staff_management into module_labels
                    $insertSql = "INSERT INTO `{$this->prefix}module_labels` 
                        (module_code, default_label, icon_class, display_order, is_active) 
                        VALUES ('staff_management', 'Staff Management', 'bi-people-fill', 3, 1)
                        ON DUPLICATE KEY UPDATE 
                            default_label = 'Staff Management',
                            icon_class = 'bi-people-fill',
                            is_active = 1";
                    $this->pdo->exec($insertSql);
                    error_log("AutoMigration: Staff Management module label added to module_labels");
                }
            } catch (Exception $e) {
                // Table might not exist yet, that's OK - main migration will handle it
                error_log("AutoMigration: Note - module_labels check: " . $e->getMessage());
            }

            // Verify staff_management exists in modules table
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}modules` WHERE module_key = 'staff_management'");
                if ($stmt) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $staffManagementExists = ($result[0]['cnt'] ?? 0) > 0;
                } else {
                    $staffManagementExists = false;
                }
                
                if (!$staffManagementExists) {
                    // Insert staff_management into modules table
                    $insertSql = "INSERT INTO `{$this->prefix}modules` 
                        (module_key, display_name, description, is_active, sort_order, icon) 
                        VALUES ('staff_management', 'Staff Management', 'Employee and payroll management', 1, 2, 'bi-people-fill')
                        ON DUPLICATE KEY UPDATE 
                            display_name = 'Staff Management',
                            description = 'Employee and payroll management',
                            icon = 'bi-people-fill',
                            is_active = 1";
                    $this->pdo->exec($insertSql);
                    error_log("AutoMigration: Staff Management module added to modules table");
                }
            } catch (Exception $e) {
                // Table might not exist yet, that's OK - migration will handle it
                error_log("AutoMigration: Note - modules check: " . $e->getMessage());
            }

            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: CRITICAL ERROR ensuring modules table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Fix employees table by adding missing columns
     * Works for both new and existing installations
     */
    private function fixEmployeesTable() {
        try {
            // Check if employees table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}employees'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                // Table doesn't exist yet, skip
                return true;
            }
            
            // Check and add hire_date column
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}employees` LIKE 'hire_date'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}employees` 
                    ADD COLUMN `hire_date` DATE NULL AFTER `employment_type`");
                error_log("AutoMigration: Added hire_date column to employees table");
            }
            
            // Check and add salary_structure column
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}employees` LIKE 'salary_structure'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}employees` 
                    ADD COLUMN `salary_structure` TEXT NULL AFTER `status`");
                error_log("AutoMigration: Added salary_structure column to employees table");
            }
            
        } catch (Exception $e) {
            error_log("AutoMigration: Error fixing employees table: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure at least one default cash account exists
     * Needed for Payroll and other modules
     */
    private function ensureDefaultCashAccount() {
        try {
            // Check if cash_accounts table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}cash_accounts'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                // Table doesn't exist yet, skip
                return true;
            }
            
            // Check if any cash accounts exist
            $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}cash_accounts`");
            $count = 0;
            if ($stmt) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $count = $result[0]['cnt'] ?? 0;
            }
            
            if ($count == 0) {
                // No cash accounts exist, create a default one
                // First, get or create a default Cash account in chart of accounts
                $stmt = $this->pdo->query("SELECT id FROM `{$this->prefix}accounts` 
                    WHERE account_type = 'Assets' AND (account_name LIKE '%Cash%' OR account_code = '1001') 
                    LIMIT 1");
                $cashAccount = ($stmt) ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
                
                if (!$cashAccount) {
                    // Create default cash account in chart of accounts
                    // Use INSERT IGNORE to avoid duplicate key errors
                    $this->pdo->exec("INSERT IGNORE INTO `{$this->prefix}accounts` 
                        (account_code, account_name, account_type, currency, status, created_at)
                        VALUES ('1001', 'Cash on Hand', 'Assets', 'NGN', 'active', NOW())");
                    
                    // Get the ID (either newly created or existing)
                    $stmt = $this->pdo->query("SELECT id FROM `{$this->prefix}accounts` 
                        WHERE account_code = '1001' LIMIT 1");
                    $cashAccount = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$cashAccount) {
                        error_log("AutoMigration: Could not create or find cash account");
                        return false;
                    }
                }
                
                $accountId = $cashAccount['id'];
                
                // Create cash account (use INSERT IGNORE to avoid duplicates)
                $this->pdo->exec("INSERT IGNORE INTO `{$this->prefix}cash_accounts` 
                    (account_id, account_name, account_type, currency, balance, status, created_at)
                    VALUES ({$accountId}, 'Main Cash Account', 'cash', 'NGN', 0.00, 'active', NOW())");
                
                error_log("AutoMigration: Created default cash account");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring default cash account: " . $e->getMessage());
            // Don't fail - just log the error
            return true;
        }
    }
    
    /**
     * Standardize accounts table for proper accounting module
     * Adds hierarchy, categories, and other standardization fields
     */
    private function standardizeAccountsTable() {
        try {
            // Check if accounts table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}accounts'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                // Table doesn't exist yet, skip
                return true;
            }
            
            $columnsAdded = false;
            
            // Add parent_account_id for hierarchy
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}accounts` LIKE 'parent_account_id'");
            if ($stmt && $stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}accounts` 
                    ADD COLUMN `parent_account_id` INT NULL AFTER `id`");
                error_log("AutoMigration: Added parent_account_id column to accounts table");
                $columnsAdded = true;
            }
            
            // Add account_category
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}accounts` LIKE 'account_category'");
            if ($stmt && $stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}accounts` 
                    ADD COLUMN `account_category` VARCHAR(50) NULL AFTER `account_type`");
                error_log("AutoMigration: Added account_category column to accounts table");
                $columnsAdded = true;
            }
            
            // Add is_system_account
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}accounts` LIKE 'is_system_account'");
            if ($stmt && $stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}accounts` 
                    ADD COLUMN `is_system_account` TINYINT(1) DEFAULT 0 AFTER `account_category`");
                error_log("AutoMigration: Added is_system_account column to accounts table");
                $columnsAdded = true;
            }
            
            // Add opening_balance
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}accounts` LIKE 'opening_balance'");
            if ($stmt && $stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}accounts` 
                    ADD COLUMN `opening_balance` DECIMAL(15,2) DEFAULT 0.00 AFTER `balance`");
                error_log("AutoMigration: Added opening_balance column to accounts table");
                $columnsAdded = true;
            }
            
            // Add opening_balance_date
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}accounts` LIKE 'opening_balance_date'");
            if ($stmt && $stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}accounts` 
                    ADD COLUMN `opening_balance_date` DATE NULL AFTER `opening_balance`");
                error_log("AutoMigration: Added opening_balance_date column to accounts table");
                $columnsAdded = true;
            }
            
            // Add description
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}accounts` LIKE 'description'");
            if ($stmt && $stmt->rowCount() == 0) {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}accounts` 
                    ADD COLUMN `description` TEXT NULL AFTER `account_name`");
                error_log("AutoMigration: Added description column to accounts table");
                $columnsAdded = true;
            }
            
            if ($columnsAdded) {
                error_log("AutoMigration: Standardized accounts table for accounting module");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR standardizing accounts table: " . $e->getMessage());
            // Don't fail - just log the error
            return true;
        }
    }

    private function installDefaultCOA() {
        try {
            // Define default accounts (same as in 006_install_default_coa.php)
            $accounts = [
                // ASSETS (1000-1999)
                [
                    'account_code' => '1000',
                    'account_name' => 'Cash on Hand',
                    'account_type' => 'Assets',
                    'account_category' => 'Cash & Bank',
                    'description' => 'Petty cash and physical currency',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '1010',
                    'account_name' => 'Bank Account - Main',
                    'account_type' => 'Assets',
                    'account_category' => 'Cash & Bank',
                    'description' => 'Primary business bank account',
                    'is_system_account' => 0
                ],
                [
                    'account_code' => '1100',
                    'account_name' => 'Accounts Receivable',
                    'account_type' => 'Assets',
                    'account_category' => 'Accounts Receivable',
                    'description' => 'Unpaid customer invoices',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '1200',
                    'account_name' => 'Inventory Asset',
                    'account_type' => 'Assets',
                    'account_category' => 'Inventory',
                    'description' => 'Value of goods held for sale',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '1500',
                    'account_name' => 'Furniture & Equipment',
                    'account_type' => 'Assets',
                    'account_category' => 'Fixed Assets',
                    'description' => 'Office furniture and equipment',
                    'is_system_account' => 0
                ],
                
                // LIABILITIES (2000-2999)
                [
                    'account_code' => '2000',
                    'account_name' => 'Accounts Payable',
                    'account_type' => 'Liabilities',
                    'account_category' => 'Accounts Payable',
                    'description' => 'Unpaid vendor bills',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '2100',
                    'account_name' => 'VAT Payable',
                    'account_type' => 'Liabilities',
                    'account_category' => 'Tax Payable',
                    'description' => 'VAT collected on sales',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '2110',
                    'account_name' => 'PAYE Payable',
                    'account_type' => 'Liabilities',
                    'account_category' => 'Tax Payable',
                    'description' => 'Employee income tax withheld',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '2120',
                    'account_name' => 'WHT Payable',
                    'account_type' => 'Liabilities',
                    'account_category' => 'Tax Payable',
                    'description' => 'Withholding tax deducted',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '2200',
                    'account_name' => 'Payroll Liabilities',
                    'account_type' => 'Liabilities',
                    'account_category' => 'Payroll Liabilities',
                    'description' => 'Salaries and wages payable',
                    'is_system_account' => 1
                ],
                
                // EQUITY (3000-3999)
                [
                    'account_code' => '3000',
                    'account_name' => 'Owner\'s Equity',
                    'account_type' => 'Equity',
                    'account_category' => 'Equity',
                    'description' => 'Owner\'s investment',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '3100',
                    'account_name' => 'Retained Earnings',
                    'account_type' => 'Equity',
                    'account_category' => 'Retained Earnings',
                    'description' => 'Accumulated profits',
                    'is_system_account' => 1
                ],
                
                // REVENUE (4000-4999)
                [
                    'account_code' => '4000',
                    'account_name' => 'Sales Revenue',
                    'account_type' => 'Revenue',
                    'account_category' => 'Sales',
                    'description' => 'Income from sales',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '4100',
                    'account_name' => 'Service Revenue',
                    'account_type' => 'Revenue',
                    'account_category' => 'Services',
                    'description' => 'Income from services',
                    'is_system_account' => 0
                ],
                [
                    'account_code' => '4200',
                    'account_name' => 'Other Income',
                    'account_type' => 'Revenue',
                    'account_category' => 'Other Income',
                    'description' => 'Miscellaneous income',
                    'is_system_account' => 0
                ],
                
                // EXPENSES (5000-9999)
                [
                    'account_code' => '5000',
                    'account_name' => 'Cost of Goods Sold',
                    'account_type' => 'Expenses',
                    'account_category' => 'Cost of Goods Sold',
                    'description' => 'Direct costs of goods sold',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '6000',
                    'account_name' => 'Rent Expense',
                    'account_type' => 'Expenses',
                    'account_category' => 'Operating Expenses',
                    'description' => 'Office or facility rent',
                    'is_system_account' => 0
                ],
                [
                    'account_code' => '6010',
                    'account_name' => 'Utilities Expense',
                    'account_type' => 'Expenses',
                    'account_category' => 'Operating Expenses',
                    'description' => 'Electricity, water, etc.',
                    'is_system_account' => 0
                ],
                [
                    'account_code' => '6020',
                    'account_name' => 'Salaries Expense',
                    'account_type' => 'Expenses',
                    'account_category' => 'Payroll Expenses',
                    'description' => 'Employee salaries and wages',
                    'is_system_account' => 1
                ],
                [
                    'account_code' => '6030',
                    'account_name' => 'Office Supplies',
                    'account_type' => 'Expenses',
                    'account_category' => 'Operating Expenses',
                    'description' => 'Stationery and supplies',
                    'is_system_account' => 0
                ],
                [
                    'account_code' => '6040',
                    'account_name' => 'Travel Expense',
                    'account_type' => 'Expenses',
                    'account_category' => 'Operating Expenses',
                    'description' => 'Business travel costs',
                    'is_system_account' => 0
                ],
                [
                    'account_code' => '6050',
                    'account_name' => 'Advertising Expense',
                    'account_type' => 'Expenses',
                    'account_category' => 'Marketing',
                    'description' => 'Marketing and advertising',
                    'is_system_account' => 0
                ]
            ];
            
            $insertedCount = 0;
            
            foreach ($accounts as $account) {
                // Check if account exists
                $stmt = $this->pdo->prepare("SELECT id FROM `{$this->prefix}accounts` WHERE account_code = ?");
                if ($stmt === false) {
                    error_log("AutoMigration: Failed to prepare SELECT for accounts table - table might not exist or have different schema");
                    return; // Exit early, table not ready
                }
                $stmt->execute([$account['account_code']]);
                
                if (count($stmt->fetchAll()) == 0) {
                    // Insert account
                    $stmt = $this->pdo->prepare("
                        INSERT INTO `{$this->prefix}accounts` 
                        (account_code, account_name, account_type, account_category, description, is_system_account, created_at, status)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'active')
                    ");
                    if ($stmt === false) {
                        error_log("AutoMigration: Failed to prepare INSERT for accounts table - missing columns? Skipping COA install.");
                        return; // Exit early, table schema not ready
                    }
                    $stmt->execute([
                        $account['account_code'],
                        $account['account_name'],
                        $account['account_type'],
                        $account['account_category'],
                        $account['description'],
                        $account['is_system_account']
                    ]);
                    $insertedCount++;
                }
            }
            
            if ($insertedCount > 0) {
                error_log("AutoMigration: Installed {$insertedCount} default accounts");
            }
            
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR installing default COA: " . $e->getMessage());
        }
    }
    
    /**
     * Install Phase 12 accounts (Fixed Assets, Security Deposits, Gain/Loss)
     */
    private function installPhase12Accounts() {
        try {
            // Check if accounts table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}accounts'");
            if ($stmt && $stmt->rowCount() == 0) {
                return true; // Table doesn't exist yet
            }
            
            $phase12Accounts = [
                ['1500', 'Buildings', 'Assets', 'Fixed Assets', 'Building and property assets', 1],
                ['1510', 'Furniture & Fixtures', 'Assets', 'Fixed Assets', 'Office furniture and fixtures', 1],
                ['1520', 'Equipment', 'Assets', 'Fixed Assets', 'Machinery and equipment', 1],
                ['1530', 'Vehicles', 'Assets', 'Fixed Assets', 'Company vehicles', 1],
                ['1540', 'Computer Equipment', 'Assets', 'Fixed Assets', 'Computers and IT equipment', 1],
                ['1550', 'Leasehold Improvements', 'Assets', 'Fixed Assets', 'Improvements to leased property', 1],
                ['1590', 'Accumulated Depreciation', 'Assets', 'Contra-Asset', 'Accumulated depreciation on fixed assets', 1],
                ['2210', 'Security Deposits Payable', 'Liabilities', 'Current Liabilities', 'Security deposits received from tenants', 1],
                ['4900', 'Gain on Asset Disposal', 'Revenue', 'Other Income', 'Gains from sale of fixed assets', 1],
                ['6200', 'Depreciation Expense', 'Expenses', 'Operating Expenses', 'Depreciation on fixed assets', 1],
                ['7000', 'Loss on Asset Disposal', 'Expenses', 'Other Expenses', 'Losses from sale of fixed assets', 1]
            ];
            
            $insertedCount = 0;
            foreach ($phase12Accounts as $account) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO `{$this->prefix}accounts` 
                    (account_code, account_name, account_type, account_category, description, is_system_account, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE account_name = account_name
                ");
                
                $result = $stmt->execute($account);
                if ($result && $stmt->rowCount() > 0) {
                    $insertedCount++;
                }
            }
            
            if ($insertedCount > 0) {
                error_log("AutoMigration: Installed {$insertedCount} Phase 12 accounts");
            }
            
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR installing Phase 12 accounts: " . $e->getMessage());
        }
    }
    
    /**
     * Add performance indexes to frequently queried tables
     */
    private function addPerformanceIndexes() {
        try {
            $indexes = [
                // Journal Entries
                "ALTER TABLE `{$this->prefix}journal_entries` ADD INDEX IF NOT EXISTS idx_reference (reference_type, reference_id)",
                "ALTER TABLE `{$this->prefix}journal_entries` ADD INDEX IF NOT EXISTS idx_date (entry_date)",
                "ALTER TABLE `{$this->prefix}journal_entries` ADD INDEX IF NOT EXISTS idx_status (status)",
                
                // Journal Entry Lines
                "ALTER TABLE `{$this->prefix}journal_entry_lines` ADD INDEX IF NOT EXISTS idx_entry_id (entry_id)",
                "ALTER TABLE `{$this->prefix}journal_entry_lines` ADD INDEX IF NOT EXISTS idx_account_id (account_id)",
                
                // Accounts
                "ALTER TABLE `{$this->prefix}accounts` ADD INDEX IF NOT EXISTS idx_account_code (account_code)",
                "ALTER TABLE `{$this->prefix}accounts` ADD INDEX IF NOT EXISTS idx_account_type (account_type)",
                "ALTER TABLE `{$this->prefix}accounts` ADD INDEX IF NOT EXISTS idx_parent (parent_account_id)",
                
                // Invoices
                "ALTER TABLE `{$this->prefix}invoices` ADD INDEX IF NOT EXISTS idx_customer (customer_id)",
                "ALTER TABLE `{$this->prefix}invoices` ADD INDEX IF NOT EXISTS idx_status (status)",
                "ALTER TABLE `{$this->prefix}invoices` ADD INDEX IF NOT EXISTS idx_date (invoice_date)",
                
                // Bills
                "ALTER TABLE `{$this->prefix}bills` ADD INDEX IF NOT EXISTS idx_supplier (supplier_id)",
                "ALTER TABLE `{$this->prefix}bills` ADD INDEX IF NOT EXISTS idx_status (status)",
                "ALTER TABLE `{$this->prefix}bills` ADD INDEX IF NOT EXISTS idx_date (bill_date)",
                
                // Fixed Assets
                "ALTER TABLE `{$this->prefix}fixed_assets` ADD INDEX IF NOT EXISTS idx_status (asset_status)",
                "ALTER TABLE `{$this->prefix}fixed_assets` ADD INDEX IF NOT EXISTS idx_category (asset_category)",
                
                // Bookings
                "ALTER TABLE `{$this->prefix}bookings` ADD INDEX IF NOT EXISTS idx_customer (customer_id)",
                "ALTER TABLE `{$this->prefix}bookings` ADD INDEX IF NOT EXISTS idx_status (status)",
                
                // Users
                "ALTER TABLE `{$this->prefix}users` ADD INDEX IF NOT EXISTS idx_email (email)",
                "ALTER TABLE `{$this->prefix}users` ADD INDEX IF NOT EXISTS idx_role (role)"
            ];
            
            $addedCount = 0;
            foreach ($indexes as $indexSql) {
                try {
                    $this->pdo->exec($indexSql);
                    $addedCount++;
                } catch (Exception $e) {
                    // Ignore errors for tables that don't exist or indexes that already exist
                    if (stripos($e->getMessage(), "doesn't exist") === false && 
                        stripos($e->getMessage(), "Duplicate key") === false) {
                        error_log("AutoMigration: Index warning: " . $e->getMessage());
                    }
                }
            }
            
            if ($addedCount > 0) {
                error_log("AutoMigration: Added {$addedCount} performance indexes");
            }
            
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR adding performance indexes: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure inventory valuation view exists
     * Used by Inventory_reports::valuation()
     */
    private function ensureInventoryValuationView() {
        try {
            // Check if items table exists first
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}items'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                // Items table doesn't exist yet, skip
                return true;
            }
            
            // Create or replace the view
            $sql = "CREATE OR REPLACE VIEW `{$this->prefix}vw_inventory_valuation` AS
                SELECT 
                    i.id AS item_id,
                    i.sku,
                    i.item_name,
                    i.item_type,
                    i.category,
                    i.unit_of_measure,
                    i.cost_price,
                    i.selling_price,
                    i.costing_method,
                    COALESCE(SUM(sl.quantity), 0) AS total_quantity,
                    COALESCE(SUM(sl.quantity * COALESCE(sl.unit_cost, i.cost_price, 0)), 0) AS total_value,
                    i.item_status,
                    i.created_at,
                    i.updated_at
                FROM 
                    `{$this->prefix}items` i
                LEFT JOIN 
                    `{$this->prefix}stock_levels` sl ON i.id = sl.item_id
                WHERE 
                    i.item_status = 'active' OR i.item_status IS NULL
                GROUP BY 
                    i.id, i.sku, i.item_name, i.item_type, i.category, 
                    i.unit_of_measure, i.cost_price, i.selling_price, i.costing_method,
                    i.item_status, i.created_at, i.updated_at";
            
            $this->pdo->exec($sql);
            error_log("AutoMigration: Inventory valuation view created/updated successfully");
            return true;
            
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR creating inventory valuation view: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure PAYE tax brackets exist
     * Nigeria progressive tax brackets for payroll
     */
    private function ensurePAYEBrackets() {
        try {
            // Create tax_brackets table if not exists
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->prefix}tax_brackets` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `tax_type_code` VARCHAR(50) NOT NULL,
                `bracket_name` VARCHAR(100) NOT NULL,
                `min_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `max_amount` DECIMAL(15,2) DEFAULT NULL,
                `rate` DECIMAL(5,2) NOT NULL DEFAULT 0,
                `cumulative_tax` DECIMAL(15,2) DEFAULT 0,
                `sort_order` INT(11) DEFAULT 0,
                `is_active` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_tax_type` (`tax_type_code`),
                KEY `idx_is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Check if PAYE brackets exist
            $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}tax_brackets` WHERE tax_type_code = 'PAYE'");
            $result = ($stmt) ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [['cnt' => 0]];
            $count = $result[0]['cnt'] ?? 0;
            
            if (($result['cnt'] ?? 0) == 0) {
                // Insert Nigeria PAYE brackets
                $brackets = [
                    ['PAYE', 'First ₦300,000', 0.00, 300000.00, 7.00, 0.00, 1],
                    ['PAYE', 'Next ₦300,000', 300000.01, 600000.00, 11.00, 21000.00, 2],
                    ['PAYE', 'Next ₦500,000', 600000.01, 1100000.00, 15.00, 54000.00, 3],
                    ['PAYE', 'Next ₦500,000', 1100000.01, 1600000.00, 19.00, 129000.00, 4],
                    ['PAYE', 'Next ₦1,600,000', 1600000.01, 3200000.00, 21.00, 224000.00, 5],
                    ['PAYE', 'Above ₦3,200,000', 3200000.01, null, 24.00, 560000.00, 6]
                ];
                
                $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}tax_brackets` 
                    (tax_type_code, bracket_name, min_amount, max_amount, rate, cumulative_tax, sort_order, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
                
                foreach ($brackets as $bracket) {
                    $stmt->execute($bracket);
                }
                
                error_log("AutoMigration: PAYE tax brackets created successfully");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring PAYE brackets: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure default POS terminal exists
     */
    private function ensureDefaultPOSTerminal() {
        try {
            // Create pos_terminals table if not exists
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->prefix}pos_terminals` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `terminal_code` VARCHAR(50) NOT NULL UNIQUE,
                `terminal_name` VARCHAR(100) NOT NULL,
                `location_id` INT(11) DEFAULT NULL,
                `terminal_type` ENUM('physical','virtual','mobile') DEFAULT 'virtual',
                `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
                `settings` JSON DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_terminal_code` (`terminal_code`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Create pos_payment_methods table if not exists
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->prefix}pos_payment_methods` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `type` ENUM('cash','card','transfer','mobile','other') NOT NULL,
                `is_active` TINYINT(1) DEFAULT 1,
                `sort_order` INT(11) DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Check if default terminal exists
            $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}pos_terminals`");
            $result = ($stmt) ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [['cnt' => 0]];
            
            if (($result[0]['cnt'] ?? 0) == 0) {
                // Insert default terminal
                $this->pdo->exec("INSERT INTO `{$this->prefix}pos_terminals` 
                    (terminal_code, terminal_name, terminal_type, status, created_at)
                    VALUES ('TERM-001', 'Main POS Terminal', 'virtual', 'active', NOW())");
                error_log("AutoMigration: Default POS terminal created");
            }
            
            // Check if payment methods exist
            $stmt = $this->pdo->query("SELECT COUNT(*) as cnt FROM `{$this->prefix}pos_payment_methods`");
            $result = ($stmt) ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [['cnt' => 0]];
            
            if (($result[0]['cnt'] ?? 0) == 0) {
                // Insert default payment methods
                $this->pdo->exec("INSERT INTO `{$this->prefix}pos_payment_methods` 
                    (code, name, type, is_active, sort_order, created_at) VALUES
                    ('CASH', 'Cash', 'cash', 1, 1, NOW()),
                    ('CARD', 'Card (POS)', 'card', 1, 2, NOW()),
                    ('TRANSFER', 'Bank Transfer', 'transfer', 1, 3, NOW())");
                error_log("AutoMigration: Default POS payment methods created");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring default POS terminal: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Run all numbered migrations from the migrations directory
     * 
     * Scans for files like 001_name.sql, 002_name.sql etc.
     * and runs them if they haven't been executed yet.
     */
    private function runNumberedMigrations($executedMigrations) {
        $migrationsDir = __DIR__ . '/../../database/migrations/';
        
        if (!is_dir($migrationsDir)) {
            return;
        }
        
        // Get all sql files
        $files = glob($migrationsDir . '*.sql');
        
        if (empty($files)) {
            return;
        }
        
        // Sort files to ensure order (001, 002, etc.)
        sort($files);
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            // Skip 000 as it's handled separately
            if ($filename === '000_complete_system_migration.sql') {
                continue;
            }
            
            // Check if already executed
            if (!in_array($filename, $executedMigrations)) {
                error_log("AutoMigration: Found new migration: {$filename}");
                $this->executeMigration($file, $filename);
                
                // Add to executed list to prevent re-running in same request if called again
                $executedMigrations[] = $filename;
            }
        }
    }
    
    /**
     * Ensure items table has is_sellable, opening_quantity, and opening_location_id columns
     * These columns support POS sellability filtering and opening stock entry
     */
    private function ensureItemsSellableColumn() {
        try {
            // Check if is_sellable column exists
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}items` LIKE 'is_sellable'");
            $columnExists = ($stmt && count($stmt->fetchAll()) > 0);
            
            if (!$columnExists) {
                error_log("AutoMigration: Adding is_sellable column to items table");
                
                // Add is_sellable column
                $this->pdo->exec("ALTER TABLE `{$this->prefix}items` 
                    ADD COLUMN `is_sellable` TINYINT(1) NOT NULL DEFAULT 1 
                    AFTER `item_status`");
                
                // Add opening_quantity column
                $this->pdo->exec("ALTER TABLE `{$this->prefix}items` 
                    ADD COLUMN `opening_quantity` DECIMAL(15,4) DEFAULT 0 
                    AFTER `is_sellable`");
                
                // Add opening_location_id column
                $this->pdo->exec("ALTER TABLE `{$this->prefix}items` 
                    ADD COLUMN `opening_location_id` INT(11) DEFAULT NULL 
                    AFTER `opening_quantity`");
                
                // Update existing fixed_asset items to not be sellable
                $this->pdo->exec("UPDATE `{$this->prefix}items` 
                    SET `is_sellable` = 0 
                    WHERE `item_type` = 'fixed_asset'");
                
                // Create index for faster POS queries
                try {
                    $this->pdo->exec("CREATE INDEX `idx_items_sellable` 
                        ON `{$this->prefix}items` (`is_sellable`, `item_status`)");
                } catch (Exception $e) {
                    // Index might already exist
                    error_log("AutoMigration: Index creation note: " . $e->getMessage());
                }
                
                error_log("AutoMigration: Successfully added is_sellable column and related columns to items table");
            }
        } catch (Exception $e) {
            error_log("AutoMigration: Error in ensureItemsSellableColumn: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Ensure bookings table has all required columns for the booking wizard
     * These columns support full booking functionality including pricing, payments, and special requests
     */
    private function ensureBookingsTableColumns() {
        try {
            // Check if bookings table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}bookings'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                // Table doesn't exist yet, skip
                return true;
            }
            
            $columnsAdded = 0;
            
            // List of columns to ensure exist with their definitions
            $columns = [
                'customer_address' => "VARCHAR(500) NULL AFTER `customer_phone`",
                'invoice_id' => "INT(11) NULL AFTER `customer_address`",
                'duration_hours' => "DECIMAL(10,2) DEFAULT 0 AFTER `end_time`",
                'number_of_guests' => "INT(11) DEFAULT 0 AFTER `duration_hours`",
                'booking_type' => "ENUM('hourly','half_day','full_day','daily','multi_day','weekly') DEFAULT 'hourly' AFTER `number_of_guests`",
                'base_amount' => "DECIMAL(15,2) DEFAULT 0 AFTER `booking_type`",
                'subtotal' => "DECIMAL(15,2) DEFAULT 0 AFTER `base_amount`",
                'discount_amount' => "DECIMAL(15,2) DEFAULT 0 AFTER `subtotal`",
                'security_deposit' => "DECIMAL(15,2) DEFAULT 0 AFTER `discount_amount`",
                'currency' => "VARCHAR(10) DEFAULT 'NGN' AFTER `balance_amount`",
                'payment_plan' => "ENUM('full','deposit','installment','pay_later') DEFAULT 'full' AFTER `payment_status`",
                'promo_code' => "VARCHAR(50) NULL AFTER `payment_plan`",
                'booking_notes' => "TEXT NULL AFTER `promo_code`",
                'special_requests' => "TEXT NULL AFTER `booking_notes`",
                'booking_source' => "ENUM('online','dashboard','phone','walkin') DEFAULT 'online' AFTER `special_requests`",
                'is_recurring' => "TINYINT(1) DEFAULT 0 AFTER `booking_source`",
                'recurring_pattern' => "ENUM('daily','weekly','monthly') NULL AFTER `is_recurring`",
                'recurring_end_date' => "DATE NULL AFTER `recurring_pattern`",
                'created_by' => "INT(11) NULL AFTER `recurring_end_date`",
                'confirmed_at' => "DATETIME NULL AFTER `created_by`",
                'cancelled_at' => "DATETIME NULL AFTER `confirmed_at`",
                'completed_at' => "DATETIME NULL AFTER `cancelled_at`"
            ];
            
            foreach ($columns as $columnName => $columnDef) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}bookings` LIKE '{$columnName}'");
                if ($stmt && count($stmt->fetchAll()) == 0) {
                    try {
                        $this->pdo->exec("ALTER TABLE `{$this->prefix}bookings` ADD COLUMN `{$columnName}` {$columnDef}");
                        error_log("AutoMigration: Added {$columnName} column to bookings table");
                        $columnsAdded++;
                    } catch (Exception $e) {
                        // Column might already exist or AFTER column doesn't exist
                        error_log("AutoMigration: Note adding {$columnName}: " . $e->getMessage());
                        // Try without AFTER clause
                        try {
                            $defWithoutAfter = preg_replace('/ AFTER `[^`]+`/', '', $columnDef);
                            $this->pdo->exec("ALTER TABLE `{$this->prefix}bookings` ADD COLUMN `{$columnName}` {$defWithoutAfter}");
                            error_log("AutoMigration: Added {$columnName} column (without position) to bookings table");
                            $columnsAdded++;
                        } catch (Exception $e2) {
                            error_log("AutoMigration: Could not add {$columnName}: " . $e2->getMessage());
                        }
                    }
                }
            }
            
            if ($columnsAdded > 0) {
                error_log("AutoMigration: Added {$columnsAdded} columns to bookings table for booking wizard");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring bookings table columns: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure space_bookings table has all required columns for payment verification
     * Adds payment_verified_at and other columns needed for idempotency and tracking
     */
    private function ensureSpaceBookingsTableColumns() {
        try {
            // Check if space_bookings table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}space_bookings'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                // Table doesn't exist yet, skip
                return true;
            }
            
            $columnsAdded = 0;
            
            // Columns needed for payment verification and tracking
            $columns = [
                'payment_verified_at' => "DATETIME NULL COMMENT 'When payment was verified from gateway'",
                'customer_id' => "INT(11) NULL",
                'customer_name' => "VARCHAR(255) NULL",
                'customer_email' => "VARCHAR(255) NULL",
                'customer_phone' => "VARCHAR(50) NULL",
                'customer_address' => "TEXT NULL",
                'invoice_id' => "INT(11) NULL",
                'reference' => "VARCHAR(100) NULL COMMENT 'Payment reference for linking'",
                'subtotal' => "DECIMAL(15,2) DEFAULT 0",
                'tax_rate' => "DECIMAL(5,2) DEFAULT 0 COMMENT 'Tax rate percentage applied'",
                'tax_amount' => "DECIMAL(15,2) DEFAULT 0 COMMENT 'Calculated tax amount'",
                'security_deposit' => "DECIMAL(15,2) DEFAULT 0",
                'payment_plan' => "VARCHAR(20) DEFAULT 'full'",
                'promo_code' => "VARCHAR(50) NULL",
                'booking_source' => "VARCHAR(20) DEFAULT 'online'",
                'is_recurring' => "TINYINT(1) DEFAULT 0",
                'recurring_pattern' => "VARCHAR(20) NULL",
                'recurring_end_date' => "DATE NULL",
                'started_at' => "DATETIME NULL",
                'facility_id' => "INT(11) NULL COMMENT 'Links to facilities table'"
            ];
            
            foreach ($columns as $columnName => $columnDef) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}space_bookings` LIKE '{$columnName}'");
                if ($stmt && count($stmt->fetchAll()) == 0) {
                    try {
                        $this->pdo->exec("ALTER TABLE `{$this->prefix}space_bookings` ADD COLUMN `{$columnName}` {$columnDef}");
                        error_log("AutoMigration: Added {$columnName} column to space_bookings table");
                        $columnsAdded++;
                    } catch (Exception $e) {
                        error_log("AutoMigration: Could not add {$columnName} to space_bookings: " . $e->getMessage());
                    }
                }
            }
            
            // Add index for payment verification lookups
            try {
                $this->pdo->exec("ALTER TABLE `{$this->prefix}space_bookings` ADD INDEX IF NOT EXISTS `idx_payment_verified` (`payment_verified_at`)");
            } catch (Exception $e) {
                // Index might already exist
            }
            
            if ($columnsAdded > 0) {
                error_log("AutoMigration: Added {$columnsAdded} columns to space_bookings table for payment verification");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring space_bookings table columns: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure payment_transactions table exists for payment gateway integration
     */
    private function ensurePaymentTransactionsTable() {
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}payment_transactions'");
            $tableExists = ($stmt && count($stmt->fetchAll()) > 0);
            
            if (!$tableExists) {
                // Create payment_transactions table
                $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}payment_transactions` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `transaction_ref` VARCHAR(100) NOT NULL,
                    `payment_type` VARCHAR(50) NOT NULL COMMENT 'booking_payment, invoice_payment, etc.',
                    `reference_id` INT(11) NOT NULL COMMENT 'ID of the related record (booking_id, invoice_id, etc.)',
                    `gateway_code` VARCHAR(50) NOT NULL COMMENT 'paystack, flutterwave, etc.',
                    `amount` DECIMAL(15,2) NOT NULL,
                    `currency` VARCHAR(10) DEFAULT 'NGN',
                    `status` ENUM('pending','success','failed','cancelled') DEFAULT 'pending',
                    `customer_email` VARCHAR(255) NULL,
                    `customer_name` VARCHAR(255) NULL,
                    `description` TEXT NULL,
                    `gateway_transaction_id` VARCHAR(255) NULL COMMENT 'Transaction ID from the gateway',
                    `gateway_response` TEXT NULL COMMENT 'JSON response from gateway',
                    `paid_at` DATETIME NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `transaction_ref_unique` (`transaction_ref`),
                    KEY `idx_payment_type` (`payment_type`),
                    KEY `idx_reference_id` (`reference_id`),
                    KEY `idx_status` (`status`),
                    KEY `idx_gateway_code` (`gateway_code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->pdo->exec($sql);
                error_log("AutoMigration: Created payment_transactions table");
            } else {
                // Table exists - check for missing columns and add them
                $columnsToAdd = [
                    'description' => "TEXT NULL AFTER `customer_name`",
                    'gateway_transaction_id' => "VARCHAR(255) NULL AFTER `description`",
                    'gateway_response' => "TEXT NULL AFTER `gateway_transaction_id`",
                    'paid_at' => "DATETIME NULL AFTER `gateway_response`",
                    'customer_email' => "VARCHAR(255) NULL AFTER `status`",
                    'customer_name' => "VARCHAR(255) NULL AFTER `customer_email`",
                    'gateway_code' => "VARCHAR(50) NOT NULL DEFAULT 'paystack' AFTER `reference_id`",
                    'currency' => "VARCHAR(10) DEFAULT 'NGN' AFTER `amount`"
                ];
                
                foreach ($columnsToAdd as $colName => $colDef) {
                    $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}payment_transactions` LIKE '{$colName}'");
                    if ($stmt && count($stmt->fetchAll()) == 0) {
                        try {
                            $this->pdo->exec("ALTER TABLE `{$this->prefix}payment_transactions` ADD COLUMN `{$colName}` {$colDef}");
                            error_log("AutoMigration: Added {$colName} column to payment_transactions table");
                        } catch (Exception $e) {
                            // Try without AFTER clause
                            try {
                                $defWithoutAfter = preg_replace('/ AFTER `[^`]+`/', '', $colDef);
                                $this->pdo->exec("ALTER TABLE `{$this->prefix}payment_transactions` ADD COLUMN `{$colName}` {$defWithoutAfter}");
                                error_log("AutoMigration: Added {$colName} column (without position) to payment_transactions table");
                            } catch (Exception $e2) {
                                error_log("AutoMigration: Could not add {$colName} to payment_transactions: " . $e2->getMessage());
                            }
                        }
                    }
                }
            }
    

            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring payment_transactions table: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure transactions table has debit/credit columns for accounting
     */
    private function ensureTransactionsTableColumns() {
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}transactions'");
            if (!$stmt || count($stmt->fetchAll()) == 0) {
                return false; // Table doesn't exist yet, likely to be created by migration
            }
            
            $columnsToAdd = [
                'debit' => "DECIMAL(15,2) DEFAULT 0 AFTER `account_id`",
                'credit' => "DECIMAL(15,2) DEFAULT 0 AFTER `debit`",
                'status' => "VARCHAR(20) DEFAULT 'posted' AFTER `transaction_date`"
            ];
            
            foreach ($columnsToAdd as $colName => $colDef) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM `{$this->prefix}transactions` LIKE '{$colName}'");
                if ($stmt && count($stmt->fetchAll()) == 0) {
                    try {
                        $this->pdo->exec("ALTER TABLE `{$this->prefix}transactions` ADD COLUMN `{$colName}` {$colDef}");
                        error_log("AutoMigration: Added {$colName} column to transactions table");
                    } catch (Exception $e) {
                        // Try without AFTER clause if that fails
                        try {
                            $defWithoutAfter = preg_replace('/ AFTER `[^`]+`/', '', $colDef);
                            $this->pdo->exec("ALTER TABLE `{$this->prefix}transactions` ADD COLUMN `{$colName}` {$defWithoutAfter}");
                            error_log("AutoMigration: Added {$colName} column (without position) to transactions table");
                        } catch (Exception $e2) {
                            error_log("AutoMigration: Could not add {$colName} to transactions: " . $e2->getMessage());
                        }
                    }
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring transactions table columns: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure payment_gateways table exists with Paystack as default
     */
    private function ensurePaymentGatewaysTable() {
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}payment_gateways'");
            $tableExists = ($stmt && count($stmt->fetchAll()) > 0);
            
            if (!$tableExists) {
                // Create payment_gateways table
                $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}payment_gateways` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `gateway_code` VARCHAR(50) NOT NULL,
                    `gateway_name` VARCHAR(100) NOT NULL,
                    `description` TEXT NULL,
                    `public_key` VARCHAR(255) NULL,
                    `private_key` VARCHAR(255) NULL,
                    `secret_key` VARCHAR(255) NULL,
                    `test_public_key` VARCHAR(255) NULL,
                    `test_secret_key` VARCHAR(255) NULL,
                    `test_mode` TINYINT(1) DEFAULT 1,
                    `is_active` TINYINT(1) DEFAULT 1,
                    `is_default` TINYINT(1) DEFAULT 0,
                    `display_order` INT(11) DEFAULT 0,
                    `supported_currencies` TEXT NULL COMMENT 'JSON array of currency codes',
                    `additional_config` TEXT NULL COMMENT 'JSON for gateway-specific config',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `gateway_code_unique` (`gateway_code`),
                    KEY `idx_is_active` (`is_active`),
                    KEY `idx_is_default` (`is_default`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->pdo->exec($sql);
                error_log("AutoMigration: Created payment_gateways table");
                
                // Seed Paystack as default gateway
                $insertSql = "INSERT INTO `{$this->prefix}payment_gateways` 
                    (`gateway_code`, `gateway_name`, `description`, `public_key`, `private_key`, `secret_key`, 
                     `test_public_key`, `test_secret_key`, `test_mode`, `is_active`, `is_default`, `display_order`, 
                     `supported_currencies`) 
                    VALUES 
                    ('paystack', 'Paystack', 'Accept payments via Paystack - Cards, Bank Transfer, USSD', 
                     '', '', '', '', '', 1, 1, 1, 1, 
                     '[\"NGN\", \"GHS\", \"ZAR\", \"USD\"]')";
                
                $this->pdo->exec($insertSql);
                error_log("AutoMigration: Seeded Paystack as default payment gateway");
            } else {
                // Table exists - ensure Paystack gateway exists
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->prefix}payment_gateways` WHERE gateway_code = ?");
                $count = 0;
                if ($stmt && $stmt->execute(['paystack'])) {
                    $count = $stmt->fetchColumn();
                }
                
                if ($count == 0) {
                    // Insert Paystack
                    $insertSql = "INSERT INTO `{$this->prefix}payment_gateways` 
                        (`gateway_code`, `gateway_name`, `description`, `test_mode`, `is_active`, `is_default`, 
                         `display_order`, `supported_currencies`) 
                        VALUES 
                        ('paystack', 'Paystack', 'Accept payments via Paystack', 1, 1, 1, 1, 
                         '[\"NGN\", \"GHS\", \"ZAR\", \"USD\"]')";
                    
                    $this->pdo->exec($insertSql);
                    error_log("AutoMigration: Added Paystack gateway to existing table");
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring payment_gateways table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure booking_resources table exists
     */
    private function ensureBookingResourcesTable() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}booking_resources'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}booking_resources` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `booking_id` INT(11) NOT NULL,
                    `resource_id` INT(11) NULL,
                    `resource_type` VARCHAR(50) DEFAULT 'facility',
                    `start_time` TIME NULL,
                    `end_time` TIME NULL,
                    `quantity` INT(11) DEFAULT 1,
                    `rate` DECIMAL(15,2) DEFAULT 0.00,
                    `rate_type` VARCHAR(20) DEFAULT 'hourly',
                    `amount` DECIMAL(15,2) DEFAULT 0.00,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_booking_id` (`booking_id`),
                    KEY `idx_resource_id` (`resource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->pdo->exec($sql);
                error_log("AutoMigration: Created booking_resources table");
            }
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring booking_resources table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure booking_slots table exists
     */
    private function ensureBookingSlotsTable() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}booking_slots'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}booking_slots` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `booking_id` INT(11) NOT NULL,
                    `facility_id` INT(11) NULL,
                    `slot_date` DATE NOT NULL,
                    `slot_start_time` TIME NULL,
                    `slot_end_time` TIME NULL,
                    `status` VARCHAR(20) DEFAULT 'booked',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_booking_id` (`booking_id`),
                    KEY `idx_facility_id` (`facility_id`),
                    KEY `idx_slot_date` (`slot_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->pdo->exec($sql);
                error_log("AutoMigration: Created booking_slots table");
            }
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring booking_slots table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure booking_payments table exists
     */
    private function ensureBookingPaymentsTable() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}booking_payments'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                $sql = "CREATE TABLE IF NOT EXISTS `{$this->prefix}booking_payments` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `booking_id` INT(11) NOT NULL,
                    `payment_number` VARCHAR(50) NULL,
                    `payment_date` DATE NOT NULL,
                    `payment_type` VARCHAR(50) DEFAULT 'full',
                    `payment_method` VARCHAR(50) DEFAULT 'cash',
                    `amount` DECIMAL(15,2) NOT NULL,
                    `currency` VARCHAR(10) DEFAULT 'NGN',
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `gateway_transaction_id` VARCHAR(255) NULL,
                    `reference` VARCHAR(255) NULL,
                    `notes` TEXT NULL,
                    `created_by` INT(11) NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_booking_id` (`booking_id`),
                    KEY `idx_payment_date` (`payment_date`),
                    KEY `idx_status` (`status`),
                    KEY `idx_gateway_transaction_id` (`gateway_transaction_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $this->pdo->exec($sql);
                error_log("AutoMigration: Created booking_payments table");
            }
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring booking_payments table: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure customer role exists for guest bookings
     */
    private function ensureCustomerRole() {
        try {
            // Check if roles table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}roles'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                error_log("AutoMigration: Roles table does not exist, skipping customer role creation");
                return false;
            }
            
            // Check if customer role already exists
            $stmt = $this->pdo->prepare("SELECT id FROM `{$this->prefix}roles` WHERE role_code = ?");
            $stmt->execute(['customer']);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                // Create customer role
                $sql = "INSERT INTO `{$this->prefix}roles` 
                        (`role_name`, `role_code`, `description`, `is_system`, `is_active`, `created_at`) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'Customer',
                    'customer',
                    'Customer role for guest bookings - limited to viewing own bookings',
                    1,
                    1
                ]);
                
                $roleId = $this->pdo->lastInsertId();
                
                // Assign minimal permissions: dashboard read, bookings read
                $permSql = "INSERT IGNORE INTO `{$this->prefix}role_permissions` (`role_id`, `permission_id`, `created_at`)
                           SELECT ?, p.id, NOW() 
                           FROM `{$this->prefix}permissions` p
                           WHERE (p.module = 'dashboard' AND p.permission = 'read')
                              OR (p.module = 'bookings' AND p.permission = 'read')";
                $this->pdo->prepare($permSql)->execute([$roleId]);
                
                error_log("AutoMigration: Created customer role with ID: $roleId");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR ensuring customer role: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Apply best-practice permissions for manager role
     * Based on separation of duties - managers handle operations, not system config
     */
    private function applyManagerBestPracticePermissions() {
        try {
            // Check if required tables exist
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}role_permissions'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                return false;
            }
            
            // Get manager role ID
            $stmt = $this->pdo->prepare("SELECT id FROM `{$this->prefix}roles` WHERE role_code = ?");
            if ($stmt && $stmt->execute(['manager'])) {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $manager = $results[0] ?? false;
            } else {
                return false;
            }
            
            if (!$manager) {
                return false;
            }
            
            $managerId = $manager['id'];
            
            // Remove delete permissions for financial modules (audit trail protection)
            $removeDeleteSql = "DELETE rp FROM `{$this->prefix}role_permissions` rp
                               INNER JOIN `{$this->prefix}permissions` p ON rp.permission_id = p.id
                               WHERE rp.role_id = ?
                               AND p.module IN ('accounting', 'accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates', 'pos', 'properties', 'inventory', 'utilities')
                               AND p.permission = 'delete'";
            $stmt = $this->pdo->prepare($removeDeleteSql);
            if ($stmt) $stmt->execute([$managerId]);
            
            // Remove settings access (admin only)
            $removeSettingsSql = "DELETE rp FROM `{$this->prefix}role_permissions` rp
                                 INNER JOIN `{$this->prefix}permissions` p ON rp.permission_id = p.id
                                 WHERE rp.role_id = ?
                                 AND p.module = 'settings'";
            $stmt = $this->pdo->prepare($removeSettingsSql);
            if ($stmt) $stmt->execute([$managerId]);
            
            // Remove users access (admin only)
            $removeUsersSql = "DELETE rp FROM `{$this->prefix}role_permissions` rp
                              INNER JOIN `{$this->prefix}permissions` p ON rp.permission_id = p.id
                              WHERE rp.role_id = ?
                              AND p.module = 'users'";
            $stmt = $this->pdo->prepare($removeUsersSql);
            if ($stmt) $stmt->execute([$managerId]);
            
            // Add reports read permission
            $addReportsSql = "INSERT IGNORE INTO `{$this->prefix}role_permissions` (`role_id`, `permission_id`, `created_at`)
                             SELECT ?, p.id, NOW()
                             FROM `{$this->prefix}permissions` p
                             WHERE p.module = 'reports' AND p.permission = 'read'";
            $stmt = $this->pdo->prepare($addReportsSql);
            if ($stmt) $stmt->execute([$managerId]);
            
            // Ensure ledger is read-only (ledger entries are generated, not created manually)
            $removeLedgerWriteSql = "DELETE rp FROM `{$this->prefix}role_permissions` rp
                                    INNER JOIN `{$this->prefix}permissions` p ON rp.permission_id = p.id
                                    WHERE rp.role_id = ?
                                    AND p.module = 'ledger'
                                    AND p.permission IN ('write', 'create', 'update', 'delete')";
            $stmt = $this->pdo->prepare($removeLedgerWriteSql);
            if ($stmt) $stmt->execute([$managerId]);
            
            error_log("AutoMigration: Applied best-practice permissions for manager role");
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR applying manager permissions: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Apply best-practice permissions for accountant role
     * Accountants need full access to all financial modules
     */
    private function applyAccountantBestPracticePermissions() {
        try {
            // Check if required tables exist
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$this->prefix}role_permissions'");
            if ($stmt && count($stmt->fetchAll()) == 0) {
                return false;
            }
            
            // Get accountant role ID
            $stmt = $this->pdo->prepare("SELECT id FROM `{$this->prefix}roles` WHERE role_code = ?");
            if ($stmt && $stmt->execute(['accountant'])) {
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $accountant = $results[0] ?? false;
            } else {
                return false;
            }
            
            if (!$accountant) {
                return false;
            }
            
            $accountantId = $accountant['id'];
            
            // Accountant should have full access to:
            // - All accounting modules (accounts, cash, receivables, payables, ledger, estimates)
            // - Tax module (for tax reporting and compliance)
            // - Reports (for financial reporting)
            // - Dashboard read
            // - NO delete on any module (audit trail protection)
            // - NO access to: settings, users, inventory, properties, bookings, pos, utilities
            
            // First, remove all existing permissions for clean slate
            $stmt = $this->pdo->prepare("DELETE FROM `{$this->prefix}role_permissions` WHERE role_id = ?");
            if ($stmt) $stmt->execute([$accountantId]);
            
            // Add accounting module permissions (read, write, create, update - no delete)
            $accountingModules = ['accounting', 'accounts', 'cash', 'receivables', 'payables', 'ledger', 'estimates'];
            foreach ($accountingModules as $module) {
                $addPermsSql = "INSERT IGNORE INTO `{$this->prefix}role_permissions` (`role_id`, `permission_id`, `created_at`)
                               SELECT ?, p.id, NOW()
                               FROM `{$this->prefix}permissions` p
                               WHERE p.module = ?
                               AND p.permission IN ('read', 'write', 'create', 'update')";
                $stmt = $this->pdo->prepare($addPermsSql);
                if ($stmt) $stmt->execute([$accountantId, $module]);
            }
            
            // Add tax module permissions (accountants handle tax reporting)
            $addTaxSql = "INSERT IGNORE INTO `{$this->prefix}role_permissions` (`role_id`, `permission_id`, `created_at`)
                         SELECT ?, p.id, NOW()
                         FROM `{$this->prefix}permissions` p
                         WHERE p.module = 'tax'
                         AND p.permission IN ('read', 'write', 'create', 'update')";
            $stmt = $this->pdo->prepare($addTaxSql);
            if ($stmt) $stmt->execute([$accountantId]);
            
            // Add reports read permission
            $addReportsSql = "INSERT IGNORE INTO `{$this->prefix}role_permissions` (`role_id`, `permission_id`, `created_at`)
                             SELECT ?, p.id, NOW()
                             FROM `{$this->prefix}permissions` p
                             WHERE p.module = 'reports' AND p.permission = 'read'";
            $stmt = $this->pdo->prepare($addReportsSql);
            if ($stmt) $stmt->execute([$accountantId]);
            
            // Add dashboard read permission
            $addDashboardSql = "INSERT IGNORE INTO `{$this->prefix}role_permissions` (`role_id`, `permission_id`, `created_at`)
                               SELECT ?, p.id, NOW()
                               FROM `{$this->prefix}permissions` p
                               WHERE p.module = 'dashboard' AND p.permission = 'read'";
            $stmt = $this->pdo->prepare($addDashboardSql);
            if ($stmt) $stmt->execute([$accountantId]);
            
            // Add notifications read permission
            $addNotifSql = "INSERT IGNORE INTO `{$this->prefix}role_permissions` (`role_id`, `permission_id`, `created_at`)
                           SELECT ?, p.id, NOW()
                           FROM `{$this->prefix}permissions` p
                           WHERE p.module = 'notifications' AND p.permission = 'read'";
            $stmt = $this->pdo->prepare($addNotifSql);
            if ($stmt) $stmt->execute([$accountantId]);
            
            // Ledger should be read-only (entries are system-generated)
            $removeLedgerWriteSql = "DELETE rp FROM `{$this->prefix}role_permissions` rp
                                    INNER JOIN `{$this->prefix}permissions` p ON rp.permission_id = p.id
                                    WHERE rp.role_id = ?
                                    AND p.module = 'ledger'
                                    AND p.permission IN ('write', 'create', 'update', 'delete')";
            $stmt = $this->pdo->prepare($removeLedgerWriteSql);
            if ($stmt) $stmt->execute([$accountantId]);
            
            error_log("AutoMigration: Applied best-practice permissions for accountant role");
            return true;
        } catch (Exception $e) {
            error_log("AutoMigration: ERROR applying accountant permissions: " . $e->getMessage());
            return false;
        }
    }
}
