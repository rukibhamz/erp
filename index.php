<?php
/**
 * Main Entry Point
 * Business Management System
 */

// Define base path
define('BASEPATH', __DIR__ . '/application/');
define('ROOTPATH', __DIR__ . '/');
define('SYSPATH', __DIR__ . '/application/core/');

// Check if installed
$config_file = BASEPATH . 'config/config.php';
if (!file_exists($config_file)) {
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

// Load configuration
$config = require $config_file;

if (!isset($config['installed']) || $config['installed'] !== true) {
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

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', ROOTPATH . 'logs/error.log');

// Set timezone
date_default_timezone_set('UTC');

// Start session
if (session_status() === PHP_SESSION_NONE) {
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

// Initialize and run application
try {
    $router = new Router();
    $router->dispatch();
} catch (Exception $e) {
    error_log($e->getMessage());
    if (ini_get('display_errors')) {
        die('<h1>Application Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
    }
    die('<h1>Application Error</h1><p>An error occurred. Please check the logs.</p>');
}

