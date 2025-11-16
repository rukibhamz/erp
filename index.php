<?php
/**
 * Main Entry Point
 * Business Management System
 */

// Define base path
define('BASEPATH', __DIR__ . '/application/');
define('ROOTPATH', __DIR__ . '/');
define('SYSPATH', __DIR__ . '/application/core/');

// Load Composer autoloader if available (for PHPMailer and other dependencies)
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Check if installed
// Prefer config.installed.php if it exists (created during installation)
$config_installed_file = BASEPATH . 'config/config.installed.php';
$config_file = BASEPATH . 'config/config.php';

// Load configuration - prefer config.installed.php if it exists
if (file_exists($config_installed_file)) {
    $config = require $config_installed_file;
} elseif (file_exists($config_file)) {
    $config = require $config_file;
} else {
    // No config files - redirect to installer
    if (file_exists(__DIR__ . '/install/index.php')) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['SCRIPT_NAME']);
        $path = rtrim($path, '/') . '/install/';
        header('Location: ' . $protocol . $host . $path);
        exit;
    }
    die('Application not configured. Please run the installer.');
}

// Verify installation status
if (!isset($config['installed']) || $config['installed'] !== true) {
    // Not installed - redirect to installer
    if (file_exists(__DIR__ . '/install/index.php')) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['SCRIPT_NAME']);
        $path = rtrim($path, '/') . '/install/';
        header('Location: ' . $protocol . $host . $path);
        exit;
    }
    die('Application not installed. Please run the installer.');
}

// Set error reporting based on environment
$environment = $config['environment'] ?? 'development';

// Ensure logs directory exists
$logsDir = ROOTPATH . 'logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

if ($environment === 'production') {
    // Production: Hide errors from users, log everything
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOTPATH . 'logs/error.log');
} else {
    // Development: Show errors for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOTPATH . 'logs/error.log');
}

// Set timezone
date_default_timezone_set('UTC');

// Start session with secure configuration
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax'); // Changed from 'Strict' to 'Lax' for better compatibility
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    
    // Only set secure flag if using HTTPS
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Autoloader
require_once BASEPATH . 'core/Autoloader.php';
spl_autoload_register([new Autoloader(), 'load']);

// Load core classes
require_once BASEPATH . 'core/Base_Controller.php';
require_once BASEPATH . 'core/Base_Model.php';
require_once BASEPATH . 'core/Database.php';
require_once BASEPATH . 'core/Router.php';
require_once BASEPATH . 'core/Loader.php';

// Load helpers
require_once BASEPATH . 'helpers/url_helper.php';
require_once BASEPATH . 'helpers/form_helper.php';
require_once BASEPATH . 'helpers/security_helper.php';
require_once BASEPATH . 'helpers/common_helper.php';
require_once BASEPATH . 'helpers/permission_helper.php';
require_once BASEPATH . 'helpers/currency_helper.php';
require_once BASEPATH . 'helpers/module_helper.php';
require_once BASEPATH . 'helpers/csrf_helper.php';
require_once BASEPATH . 'helpers/number_helper.php';

// Initialize and run application
try {
    $router = new Router();
    $router->dispatch();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    $errorTrace = $e->getTraceAsString();
    
    // Log error
    error_log("Application Error: " . $errorMessage . "\nTrace: " . $errorTrace);
    
    // Try to write to log file directly if ini_set failed
    $logFile = ROOTPATH . 'logs/error.log';
    if (is_writable($logFile) || (is_writable($logsDir) && !file_exists($logFile))) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $errorMessage . "\n" . $errorTrace . "\n\n", FILE_APPEND);
    }
    
    if (ini_get('display_errors') || $environment === 'development') {
        die('<h1>Application Error</h1><p>' . htmlspecialchars($errorMessage) . '</p><pre>' . htmlspecialchars($errorTrace) . '</pre>');
    }
    die('<h1>Application Error</h1><p>An error occurred. Please check the logs.</p>');
} catch (Error $e) {
    $errorMessage = $e->getMessage();
    $errorTrace = $e->getTraceAsString();
    
    // Log fatal error
    error_log("Fatal Error: " . $errorMessage . "\nTrace: " . $errorTrace);
    
    $logFile = ROOTPATH . 'logs/error.log';
    if (is_writable($logFile) || (is_writable($logsDir) && !file_exists($logFile))) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " - FATAL: " . $errorMessage . "\n" . $errorTrace . "\n\n", FILE_APPEND);
    }
    
    if (ini_get('display_errors') || $environment === 'development') {
        die('<h1>Fatal Error</h1><p>' . htmlspecialchars($errorMessage) . '</p><pre>' . htmlspecialchars($errorTrace) . '</pre>');
    }
    die('<h1>Application Error</h1><p>A fatal error occurred. Please check the logs.</p>');
}

