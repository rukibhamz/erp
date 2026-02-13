<?php
// Define BASEPATH for migration files (they check for this constant)
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/application/');
}
if (!defined('ROOTPATH')) {
    define('ROOTPATH', dirname(__DIR__) . '/');
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/install_error.log');

// Ensure logs directory exists
$logs_dir = dirname(__DIR__) . '/logs';
if (!is_dir($logs_dir)) {
    @mkdir($logs_dir, 0755, true);
}

// Set up error handler to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
        error_log($error_message);
        
        // If we're in the installer and not in output buffering, show error
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/install/') !== false) {
            echo "<!DOCTYPE html><html><head><title>Installation Error</title>";
            echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
            echo ".error{background:#fff;border-left:4px solid #dc3545;padding:20px;margin:20px 0;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
            echo "h1{color:#dc3545;margin-top:0;}pre{background:#f8f9fa;padding:15px;border-radius:4px;overflow-x:auto;}</style></head><body>";
            echo "<div class='error'><h1>Installation Error</h1>";
            echo "<p><strong>{$error['message']}</strong></p>";
            echo "<p><strong>File:</strong> {$error['file']}</p>";
            echo "<p><strong>Line:</strong> {$error['line']}</p>";
            echo "<details><summary>Stack Trace</summary><pre>";
            debug_print_backtrace();
            echo "</pre></details>";
            echo "<p><small>Check <code>logs/install_error.log</code> for more details.</small></p>";
            echo "</div></body></html>";
        }
    }
});

session_start();

// Define installation steps
define('STEP_WELCOME', 1);
define('STEP_REQUIREMENTS', 2);
define('STEP_DATABASE', 3);
define('STEP_ADMIN', 4);
define('STEP_COMPLETE', 5);

// Get current step
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : STEP_WELCOME;
$current_step = max(STEP_WELCOME, min($current_step, STEP_COMPLETE));

// Check if already installed (only on non-complete steps)
$config_installed_file = dirname(__DIR__) . '/application/config/config.installed.php';
if ($current_step != STEP_COMPLETE && file_exists($config_installed_file)) {
    // Simple check: read file content to see if installed = true
    $config_content = file_get_contents($config_installed_file);
    if (strpos($config_content, "'installed' => true") !== false || strpos($config_content, '"installed" => true') !== false) {
        // Already installed - redirect to login
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['SCRIPT_NAME']);
        $path = rtrim($path, '/');
        header('Location: ' . $protocol . $host . $path . '/login');
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($current_step) {
        case STEP_DATABASE:
            $_SESSION['db_host'] = $_POST['db_host'] ?? 'localhost';
            $_SESSION['db_name'] = $_POST['db_name'] ?? '';
            $_SESSION['db_user'] = $_POST['db_user'] ?? 'root';
            $_SESSION['db_pass'] = $_POST['db_pass'] ?? '';
            $_SESSION['db_prefix'] = $_POST['db_prefix'] ?? 'erp_';
            
            // Test database connection
            try {
                $pdo = new PDO(
                    "mysql:host={$_SESSION['db_host']};charset=utf8mb4",
                    $_SESSION['db_user'],
                    $_SESSION['db_pass']
                );
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if database exists, create if not
                $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
                $stmt->execute([$_SESSION['db_name']]);
                
                if (!$stmt->fetch()) {
                    $pdo->exec("CREATE DATABASE `{$_SESSION['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                }
                
                $_SESSION['db_connected'] = true;
                header('Location: ?step=' . STEP_ADMIN);
                exit;
            } catch (PDOException $e) {
                $_SESSION['db_error'] = $e->getMessage();
            }
            break;
            
        case STEP_ADMIN:
            $_SESSION['admin_username'] = $_POST['admin_username'] ?? '';
            $_SESSION['admin_email'] = $_POST['admin_email'] ?? '';
            $_SESSION['admin_password'] = $_POST['admin_password'] ?? '';
            $_SESSION['company_name'] = $_POST['company_name'] ?? '';
            
            // Increase PHP execution time for long-running migrations
            set_time_limit(600); // 10 minutes
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '512M');
            
            // Create database tables and admin account
            try {
                $pdo = new PDO(
                    "mysql:host={$_SESSION['db_host']};dbname={$_SESSION['db_name']};charset=utf8mb4",
                    $_SESSION['db_user'],
                    $_SESSION['db_pass']
                );
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Increase timeout for long-running migrations
                $pdo->exec("SET SESSION wait_timeout = 600");
                $pdo->exec("SET SESSION interactive_timeout = 600");
                // Note: max_allowed_packet is read-only at SESSION level, must be set at server/GLOBAL level
                // If needed, ask your hosting provider to increase it, or set it in my.cnf
                
                // Check if tables exist - only drop if this is a reinstall
                $existingTables = $pdo->query("SHOW TABLES LIKE '{$_SESSION['db_prefix']}%'")->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($existingTables)) {
                    // Tables exist - drop them efficiently
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    // Drop tables individually but in a transaction-like manner
                    foreach ($existingTables as $table) {
                        try {
                            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                        } catch (PDOException $e) {
                            // Continue even if one table fails
                            error_log("Failed to drop table {$table}: " . $e->getMessage());
                        }
                    }
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                }
                
                // Run migrations
                error_log("Starting main migrations...");
                require __DIR__ . '/migrations.php';
                runMigrations($pdo, $_SESSION['db_prefix']);
                error_log("Main migrations completed successfully");
                
                // Run complete system migration (includes role-based permissions)
                // This ensures erp_roles, erp_role_permissions, and all business tables are created
                $completeMigrationPath = dirname(__DIR__) . '/database/migrations/000_complete_system_migration.sql';
                if (file_exists($completeMigrationPath)) {
                    error_log("Starting complete system migration...");
                    try {
                        // Read and execute the SQL file
                        $sql = file_get_contents($completeMigrationPath);
                        // Replace the prefix placeholder if needed
                        $sql = str_replace('erp_', $_SESSION['db_prefix'], $sql);
                        // Split by semicolon and execute each statement
                        $statements = array_filter(array_map('trim', explode(';', $sql)));
                        foreach ($statements as $statement) {
                            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                                try {
                                    $pdo->exec($statement);
                                } catch (PDOException $e) {
                                    // Log but continue for non-critical errors (like duplicate keys)
                                    if (strpos($e->getMessage(), 'Duplicate') === false && 
                                        strpos($e->getMessage(), 'already exists') === false) {
                                        error_log("SQL execution warning: " . $e->getMessage());
                                    }
                                }
                            }
                        }
                        error_log("Complete system migration completed");
                    } catch (Exception $e) {
                        error_log("Complete system migration warning: " . $e->getMessage());
                        // Don't fail installation, but log the error
                    }
                } else {
                    error_log("Complete system migration file not found at: {$completeMigrationPath}");
                }
                
                // Run enhanced migrations if file exists
                if (file_exists(__DIR__ . '/migrations_enhanced.php')) {
                    error_log("Starting enhanced migrations...");
                    require_once __DIR__ . '/migrations_enhanced.php';
                    try {
                        runEnhancedMigrations($pdo, $_SESSION['db_prefix']);
                        error_log("Enhanced migrations completed");
                    } catch (Exception $e) {
                        error_log("Enhanced migrations warning: " . $e->getMessage());
                        // Don't fail installation for optional migrations
                    }
                }
                
        // Run booking migrations if file exists
        if (file_exists(__DIR__ . '/migrations_booking.php')) {
            require_once __DIR__ . '/migrations_booking.php';
            try {
                runBookingMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Booking migrations warning: " . $e->getMessage());
            }
        }
        
        // Run payment gateway migrations if file exists
        if (file_exists(__DIR__ . '/migrations_payment_gateways.php')) {
            require_once __DIR__ . '/migrations_payment_gateways.php';
            try {
                runPaymentGatewayMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Payment gateway migrations warning: " . $e->getMessage());
            }
        }
        
        // Run enhanced booking migrations if file exists
        if (file_exists(__DIR__ . '/migrations_booking_enhanced.php')) {
            require_once __DIR__ . '/migrations_booking_enhanced.php';
            try {
                runBookingEnhancedMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Enhanced booking migrations warning: " . $e->getMessage());
            }
        }
        
        // Run customer portal migrations if file exists
        if (file_exists(__DIR__ . '/migrations_customer_portal.php')) {
            require_once __DIR__ . '/migrations_customer_portal.php';
            try {
                runCustomerPortalMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Customer portal migrations warning: " . $e->getMessage());
            }
        }
        
        // Run notification migrations if file exists
        if (file_exists(__DIR__ . '/migrations_notifications.php')) {
            require_once __DIR__ . '/migrations_notifications.php';
            try {
                runNotificationMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Notification migrations warning: " . $e->getMessage());
            }
        }
        
        // Run property management migrations if file exists
        if (file_exists(__DIR__ . '/migrations_property_management.php')) {
            require_once __DIR__ . '/migrations_property_management.php';
            try {
                runPropertyManagementMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Property management migrations warning: " . $e->getMessage());
            }
        }
        
        // Run utilities migrations if file exists
        if (file_exists(__DIR__ . '/migrations_utilities.php')) {
            require_once __DIR__ . '/migrations_utilities.php';
            try {
                runUtilitiesMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Utilities migrations warning: " . $e->getMessage());
            }
        }
        
        // Run inventory migrations if file exists
        if (file_exists(__DIR__ . '/migrations_inventory.php')) {
            require_once __DIR__ . '/migrations_inventory.php';
            try {
                runInventoryMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Inventory migrations warning: " . $e->getMessage());
            }
        }
        
        // Run tax migrations if file exists
        if (file_exists(__DIR__ . '/migrations_tax.php')) {
            require_once __DIR__ . '/migrations_tax.php';
            try {
                runTaxMigrations($pdo, $_SESSION['db_prefix']);
                // Insert default tax types
                insertDefaultTaxTypes($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Tax migrations warning: " . $e->getMessage());
            }
        }
        
        // Run POS migrations if file exists
        if (file_exists(__DIR__ . '/migrations_pos.php')) {
            require_once __DIR__ . '/migrations_pos.php';
            try {
                runPosMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("POS migrations warning: " . $e->getMessage());
            }
        }
        
        // Run performance migrations (indexes)
        if (file_exists(__DIR__ . '/migrations_performance.php')) {
            require_once __DIR__ . '/migrations_performance.php';
            try {
                runPerformanceMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Performance migrations warning: " . $e->getMessage());
            }
        }
        
        // Run security migrations
        if (file_exists(__DIR__ . '/migrations_security.php')) {
            require_once __DIR__ . '/migrations_security.php';
            try {
                runSecurityMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Security migrations warning: " . $e->getMessage());
            }
        }
        
        // Run audit migrations
        if (file_exists(__DIR__ . '/migrations_audit.php')) {
            require_once __DIR__ . '/migrations_audit.php';
            try {
                runAuditMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Audit migrations warning: " . $e->getMessage());
            }
        }
        
        // Run advanced permissions migrations
        if (file_exists(__DIR__ . '/migrations_advanced_permissions.php')) {
            require_once __DIR__ . '/migrations_advanced_permissions.php';
            try {
                runAdvancedPermissionsMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Advanced permissions migrations warning: " . $e->getMessage());
            }
        }
        
        // Run report builder migrations
        if (file_exists(__DIR__ . '/migrations_report_builder.php')) {
            require_once __DIR__ . '/migrations_report_builder.php';
            try {
                runReportBuilderMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Report builder migrations warning: " . $e->getMessage());
            }
        }
        
        // Run modules migrations
        if (file_exists(__DIR__ . '/migrations_modules.php')) {
            require_once __DIR__ . '/migrations_modules.php';
            try {
                $modulesMigration = migrations_modules($_SESSION['db_prefix']);
                // Batch table creation with delays
                foreach ($modulesMigration['tables'] as $tableName => $sql) {
                    $pdo->exec($sql);
                    usleep(50000); // 0.05 second delay between tables
                }
                // Batch inserts more efficiently
                if (!empty($modulesMigration['inserts'])) {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    foreach ($modulesMigration['inserts'] as $insertSql) {
                        $pdo->exec($insertSql);
                    }
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                }
            } catch (Exception $e) {
                error_log("Modules migrations warning: " . $e->getMessage());
            }
        }
                
        // Run ALTER TABLE migrations (add columns to existing tables)
        if (file_exists(__DIR__ . '/migrations_alter.php')) {
            require_once __DIR__ . '/migrations_alter.php';
            try {
                runAlterMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Alter migrations warning: " . $e->getMessage());
            }
        }
        
        // Run bookable config migrations
        if (file_exists(__DIR__ . '/migrations_bookable_config.php')) {
            require_once __DIR__ . '/migrations_bookable_config.php';
            try {
                runBookableConfigMigration($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Bookable config migrations warning: " . $e->getMessage());
            }
        }
        
        // Run facilities rates migrations (adds rate columns)
        if (file_exists(__DIR__ . '/migrations_facilities_rates.php')) {
            require_once __DIR__ . '/migrations_facilities_rates.php';
            try {
                runFacilitiesRatesMigrations($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("Facilities rates migrations warning: " . $e->getMessage());
            }
        }
        
        // Run new features migrations (wholesale pricing, education tax)
        if (file_exists(__DIR__ . '/migrations_new_features.php')) {
            require_once __DIR__ . '/migrations_new_features.php';
            try {
                runNewFeatureMigrations($pdo, $_SESSION['db_prefix']);
                fixNewFeatureColumns($pdo, $_SESSION['db_prefix']);
            } catch (Exception $e) {
                error_log("New features migrations warning: " . $e->getMessage());
            }
        }
                
                // Create super admin user
                error_log("Creating super admin user...");
                if (empty($_SESSION['admin_username']) || empty($_SESSION['admin_email']) || empty($_SESSION['admin_password'])) {
                    throw new Exception("Admin username, email, and password are required.");
                }
                
                $password_hash = password_hash($_SESSION['admin_password'], PASSWORD_BCRYPT);
                if (!$password_hash) {
                    throw new Exception("Failed to hash password. Please check PHP password hashing support.");
                }
                
                $stmt = $pdo->prepare("INSERT INTO {$_SESSION['db_prefix']}users (username, email, password, role, status, created_at) VALUES (?, ?, ?, 'super_admin', 'active', NOW())");
                $stmt->execute([$_SESSION['admin_username'], $_SESSION['admin_email'], $password_hash]);
                error_log("Super admin user created");
                
                // Assign all permissions to super admin (batch insert)
                $adminId = $pdo->lastInsertId();
                if (!$adminId) {
                    throw new Exception("Failed to get admin user ID. User may not have been created.");
                }
                
                error_log("Assigning permissions to admin user (ID: {$adminId})...");
                // Use a single query instead of looping
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                $stmt = $pdo->prepare("INSERT IGNORE INTO {$_SESSION['db_prefix']}user_permissions (user_id, permission_id, created_at) SELECT ?, id, NOW() FROM {$_SESSION['db_prefix']}permissions");
                $stmt->execute([$adminId]);
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                error_log("Permissions assigned");
                
                // Create default company
                error_log("Creating default company...");
                if (empty($_SESSION['company_name'])) {
                    $_SESSION['company_name'] = 'My Company'; // Default name
                }
                $stmt = $pdo->prepare("INSERT INTO {$_SESSION['db_prefix']}companies (name, created_at) VALUES (?, NOW())");
                $stmt->execute([$_SESSION['company_name']]);
                error_log("Default company created");
                
                // Generate config file
                error_log("Generating configuration files...");
                $config_content = generateConfigFile($_SESSION);
                $config_dir = dirname(__DIR__) . '/application/config';
                $config_file = $config_dir . '/config.php';
                
                if (!is_dir($config_dir)) {
                    if (!mkdir($config_dir, 0755, true)) {
                        throw new Exception("Failed to create config directory: {$config_dir}");
                    }
                }
                
                if (!file_put_contents($config_file, $config_content)) {
                    throw new Exception("Failed to write config file: {$config_file}");
                }
                
                // Also create config.installed.php for compatibility
                $installed_config_file = $config_dir . '/config.installed.php';
                if (!file_put_contents($installed_config_file, $config_content)) {
                    throw new Exception("Failed to write installed config file: {$installed_config_file}");
                }
                
                // Set proper permissions on config files
                @chmod($config_file, 0644);
                @chmod($installed_config_file, 0644);
                error_log("Configuration files created");
                
                // Create .htaccess
                error_log("Creating .htaccess file...");
                createHtaccess();
                error_log("Installation completed successfully");
                
                // Run all final SQL migrations from database/migrations to ensure completeness
                error_log("Running all final SQL migrations from database/migrations...");
                $migrationDir = dirname(__DIR__) . '/database/migrations';
                $sqlFiles = glob($migrationDir . '/*.sql');
                if ($sqlFiles) {
                    sort($sqlFiles);
                    foreach ($sqlFiles as $sqlFile) {
                        $fileName = basename($sqlFile);
                        error_log("Executing migration: {$fileName}");
                        
                        $sql = file_get_contents($sqlFile);
                        // Replace erp_ prefix with the user-defined prefix
                        $sql = str_replace('erp_', $_SESSION['db_prefix'], $sql);
                        
                        // Split by semicolon and execute each statement
                        // Note: This is a simple parser, but works for the current migration files
                        $statements = array_filter(array_map('trim', explode(';', $sql)));
                        foreach ($statements as $statement) {
                            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                                try {
                                    $pdo->exec($statement);
                                } catch (PDOException $e) {
                                    // Log but continue for non-critical errors (like duplicate columns/tables)
                                    if (strpos($e->getMessage(), 'Duplicate') === false && 
                                        strpos($e->getMessage(), 'already exists') === false) {
                                        error_log("Migration {$fileName} statement warning: " . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                }
                error_log("All final SQL migrations completed");
                
                header('Location: ?step=' . STEP_COMPLETE);
                exit;
            } catch (Exception $e) {
                $_SESSION['install_error'] = $e->getMessage();
                $_SESSION['install_error_trace'] = $e->getTraceAsString();
                error_log("Installation Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            } catch (Error $e) {
                $_SESSION['install_error'] = "Fatal Error: " . $e->getMessage();
                $_SESSION['install_error_trace'] = $e->getTraceAsString();
                error_log("Installation Fatal Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
            break;
    }
}

// Check requirements
function checkRequirements() {
    $requirements = [
        'php_version' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'mysqli' => extension_loaded('mysqli'),
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'curl' => extension_loaded('curl'),
        'zip' => extension_loaded('zip'),
        'gd' => extension_loaded('gd'),
        'mbstring' => extension_loaded('mbstring'),
        'json' => extension_loaded('json'),
    ];
    
    return $requirements;
}

// Generate config file
function generateConfigFile($session) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = rtrim($protocol . '://' . $host . $scriptPath, '/') . '/';
    
    // Generate encryption key if not set
    $encryption_key = bin2hex(random_bytes(32));
    
    $config = "<?php\n";
    $config .= "defined('BASEPATH') OR exit('No direct script access allowed');\n\n";
    $config .= "return [\n";
    $config .= "    'installed' => true,\n";
    $config .= "    'environment' => 'development', // Change to 'production' after deployment\n";
    $config .= "    'base_url' => '$baseUrl',\n";
    $config .= "    'db' => [\n";
    $config .= "        'hostname' => '" . addslashes($session['db_host']) . "',\n";
    $config .= "        'username' => '" . addslashes($session['db_user']) . "',\n";
    $config .= "        'password' => '" . addslashes($session['db_pass']) . "',\n";
    $config .= "        'database' => '" . addslashes($session['db_name']) . "',\n";
    $config .= "        'dbprefix' => '" . addslashes($session['db_prefix']) . "',\n";
    $config .= "        'charset' => 'utf8mb4',\n";
    $config .= "        'collation' => 'utf8mb4_unicode_ci'\n";
    $config .= "    ],\n";
    $config .= "    'encryption_key' => '$encryption_key'\n";
    $config .= "];\n";
    
    return $config;
}

// Create .htaccess file
function createHtaccess() {
    $htaccess = "# Enable Rewrite Engine\n";
    $htaccess .= "RewriteEngine On\n\n";
    $htaccess .= "# Prevent directory listing\n";
    $htaccess .= "Options -Indexes\n\n";
    $htaccess .= "# Protect config files\n";
    $htaccess .= "<FilesMatch \"\\.(php|ini|conf)$\">\n";
    $htaccess .= "    <Files \"config.php\">\n";
    $htaccess .= "        Require all denied\n";
    $htaccess .= "    </Files>\n";
    $htaccess .= "    <Files \"config.installed.php\">\n";
    $htaccess .= "        Require all denied\n";
    $htaccess .= "    </Files>\n";
    $htaccess .= "</FilesMatch>\n\n";
    $htaccess .= "# Block access to install directory after installation\n";
    $htaccess .= "# Uncomment the following lines AFTER installation is complete and verified\n";
    $htaccess .= "# RewriteCond %{REQUEST_URI} ^/install\n";
    $htaccess .= "# RewriteRule . - [F,L]\n\n";
    $htaccess .= "# Route all requests to index.php (allow assets and install to pass through)\n";
    $htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
    $htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
    $htaccess .= "RewriteCond %{REQUEST_URI} !^/install\n";
    $htaccess .= "RewriteCond %{REQUEST_URI} !^/assets\n";
    $htaccess .= "RewriteCond %{REQUEST_URI} !^/uploads\n";
    $htaccess .= "RewriteRule ^(.*)$ index.php?url=$1 [L,QSA]\n\n";
    $htaccess .= "# Set default charset\n";
    $htaccess .= "AddDefaultCharset UTF-8\n\n";
    $htaccess .= "# Prevent access to .htaccess\n";
    $htaccess .= "<Files .htaccess>\n";
    $htaccess .= "    Require all denied\n";
    $htaccess .= "</Files>\n\n";
    $htaccess .= "# Security headers\n";
    $htaccess .= "<IfModule mod_headers.c>\n";
    $htaccess .= "    Header set X-Content-Type-Options \"nosniff\"\n";
    $htaccess .= "    Header set X-Frame-Options \"SAMEORIGIN\"\n";
    $htaccess .= "    Header set X-XSS-Protection \"1; mode=block\"\n";
    $htaccess .= "</IfModule>\n\n";
    $htaccess .= "# Enable compression\n";
    $htaccess .= "<IfModule mod_deflate.c>\n";
    $htaccess .= "    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json\n";
    $htaccess .= "</IfModule>\n\n";
    $htaccess .= "# Browser caching\n";
    $htaccess .= "<IfModule mod_expires.c>\n";
    $htaccess .= "    ExpiresActive On\n";
    $htaccess .= "    ExpiresByType image/jpg \"access plus 1 year\"\n";
    $htaccess .= "    ExpiresByType image/jpeg \"access plus 1 year\"\n";
    $htaccess .= "    ExpiresByType image/gif \"access plus 1 year\"\n";
    $htaccess .= "    ExpiresByType image/png \"access plus 1 year\"\n";
    $htaccess .= "    ExpiresByType image/svg+xml \"access plus 1 year\"\n";
    $htaccess .= "    ExpiresByType text/css \"access plus 1 month\"\n";
    $htaccess .= "    ExpiresByType application/javascript \"access plus 1 month\"\n";
    $htaccess .= "</IfModule>\n";
    
    file_put_contents(dirname(__DIR__) . '/.htaccess', $htaccess);
}

$requirements = checkRequirements();
$all_requirements_met = array_reduce($requirements, function($carry, $item) { return $carry && $item; }, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Wizard - Business Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: #f9fafb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        .install-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .install-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .install-header {
            background: #000000;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .install-body {
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .step.active .step-number {
            background: #000000;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .requirement-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .requirement-item.pass {
            background: #d4edda;
            color: #155724;
        }
        .requirement-item.fail {
            background: #f8d7da;
            color: #721c24;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .btn-install {
            background: #000000;
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 500;
            color: white;
        }
        .btn-install:hover {
            background: #1a1a1a;
            transform: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <h1 class="mb-0"><i class="bi bi-gear-fill"></i> Installation Wizard</h1>
                <p class="mb-0 mt-2">Business Management System</p>
            </div>
            
            <div class="install-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?= $current_step >= STEP_WELCOME ? 'active' : '' ?> <?= $current_step > STEP_WELCOME ? 'completed' : '' ?>">
                        <div class="step-number">1</div>
                        <small>Welcome</small>
                    </div>
                    <div class="step <?= $current_step >= STEP_REQUIREMENTS ? 'active' : '' ?> <?= $current_step > STEP_REQUIREMENTS ? 'completed' : '' ?>">
                        <div class="step-number">2</div>
                        <small>Requirements</small>
                    </div>
                    <div class="step <?= $current_step >= STEP_DATABASE ? 'active' : '' ?> <?= $current_step > STEP_DATABASE ? 'completed' : '' ?>">
                        <div class="step-number">3</div>
                        <small>Database</small>
                    </div>
                    <div class="step <?= $current_step >= STEP_ADMIN ? 'active' : '' ?> <?= $current_step > STEP_ADMIN ? 'completed' : '' ?>">
                        <div class="step-number">4</div>
                        <small>Admin</small>
                    </div>
                    <div class="step <?= $current_step >= STEP_COMPLETE ? 'active' : '' ?>">
                        <div class="step-number">5</div>
                        <small>Complete</small>
                    </div>
                </div>

                <!-- Step Content -->
                <?php if ($current_step == STEP_WELCOME): ?>
                    <div class="text-center py-4">
                        <h3>Welcome to Installation</h3>
                        <p class="text-muted">This wizard will guide you through the installation process.</p>
                        <div class="mt-4">
                            <a href="?step=<?= STEP_REQUIREMENTS ?>" class="btn btn-install btn-lg text-white">
                                <i class="bi bi-arrow-right"></i> Get Started
                            </a>
                        </div>
                    </div>

                <?php elseif ($current_step == STEP_REQUIREMENTS): ?>
                    <h3>System Requirements</h3>
                    <p class="text-muted mb-4">Please ensure all requirements are met before proceeding.</p>
                    
                    <?php foreach ($requirements as $name => $status): ?>
                        <div class="requirement-item <?= $status ? 'pass' : 'fail' ?>">
                            <span><i class="bi bi-<?= $status ? 'check-circle-fill' : 'x-circle-fill' ?>"></i> <?= ucwords(str_replace('_', ' ', $name)) ?></span>
                            <?php if (!$status): ?>
                                <span class="badge bg-danger">Required</span>
                            <?php else: ?>
                                <span class="badge bg-success">OK</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="?step=<?= STEP_WELCOME ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Previous</a>
                        <?php if ($all_requirements_met): ?>
                            <a href="?step=<?= STEP_DATABASE ?>" class="btn btn-install text-white">Next <i class="bi bi-arrow-right"></i></a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Please fix requirements</button>
                        <?php endif; ?>
                    </div>

                <?php elseif ($current_step == STEP_DATABASE): ?>
                    <h3>Database Configuration</h3>
                    <p class="text-muted mb-4">Enter your database connection details.</p>
                    
                    <?php if (isset($_SESSION['db_error'])): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($_SESSION['db_error']) ?>
                        </div>
                        <?php unset($_SESSION['db_error']); ?>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Database Host</label>
                            <input type="text" name="db_host" class="form-control" value="<?= $_SESSION['db_host'] ?? 'localhost' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Database Name</label>
                            <input type="text" name="db_name" class="form-control" value="<?= $_SESSION['db_name'] ?? '' ?>" required>
                            <small class="text-muted">Database will be created if it doesn't exist</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Database User</label>
                            <input type="text" name="db_user" class="form-control" value="<?= $_SESSION['db_user'] ?? 'root' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Database Password</label>
                            <input type="password" name="db_pass" class="form-control" value="<?= $_SESSION['db_pass'] ?? '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Table Prefix</label>
                            <input type="text" name="db_prefix" class="form-control" value="<?= $_SESSION['db_prefix'] ?? 'erp_' ?>">
                            <small class="text-muted">Optional prefix for database tables</small>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <a href="?step=<?= STEP_REQUIREMENTS ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Previous</a>
                            <button type="submit" class="btn btn-install text-white">Test & Continue <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </form>

                <?php elseif ($current_step == STEP_ADMIN): ?>
                    <h3>Administrator Account</h3>
                    <p class="text-muted mb-4">Create your administrator account and company information.</p>
                    
                    <?php if (isset($_SESSION['install_error'])): ?>
                        <div class="alert alert-danger">
                            <h5><i class="bi bi-exclamation-triangle-fill"></i> Installation Error</h5>
                            <p><strong><?= htmlspecialchars($_SESSION['install_error']) ?></strong></p>
                            <?php if (isset($_SESSION['install_error_trace'])): ?>
                                <details class="mt-2">
                                    <summary>Technical Details (Click to expand)</summary>
                                    <pre class="mt-2 p-2 bg-light" style="font-size: 0.85rem; overflow-x: auto;"><?= htmlspecialchars($_SESSION['install_error_trace']) ?></pre>
                                </details>
                            <?php endif; ?>
                            <p class="mt-2 mb-0"><small>Please check your database connection, permissions, and server logs for more details.</small></p>
                        </div>
                        <?php 
                        unset($_SESSION['install_error']); 
                        unset($_SESSION['install_error_trace']);
                        ?>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="admin_username" class="form-control" value="<?= $_SESSION['admin_username'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="admin_email" class="form-control" value="<?= $_SESSION['admin_email'] ?? '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="admin_password" class="form-control" minlength="6" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?= $_SESSION['company_name'] ?? '' ?>" required>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <a href="?step=<?= STEP_DATABASE ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Previous</a>
                            <button type="submit" class="btn btn-install text-white">Install <i class="bi bi-check-circle"></i></button>
                        </div>
                    </form>

                <?php elseif ($current_step == STEP_COMPLETE): ?>
                    <div class="text-center py-4">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        </div>
                        <h3>Installation Complete!</h3>
                        <p class="text-muted">Your Business Management System has been successfully installed.</p>
                        <div class="alert alert-warning mt-4">
                            <i class="bi bi-exclamation-triangle"></i> <strong>Important:</strong> For security reasons, please delete the <code>install</code> directory after verifying the installation.
                        </div>
                        <div class="mt-4">
                            <?php
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                            $host = $_SERVER['HTTP_HOST'];
                            $path = dirname(dirname($_SERVER['SCRIPT_NAME']));
                            $path = rtrim($path, '/');
                            $app_url = $protocol . $host . $path . '/login';
                            ?>
                            <a href="<?= $app_url ?>" class="btn btn-install btn-lg text-white">
                                <i class="bi bi-house-door"></i> Go to Application
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

