<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Whether the application has completed installation.
 */
function app_is_installed(): bool {
    $root = defined('ROOTPATH') ? ROOTPATH : (dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
    $configFile = $root . 'application/config/config.installed.php';
    if (!is_file($configFile)) {
        return false;
    }
    $content = @file_get_contents($configFile);
    if ($content === false) {
        return false;
    }
    return strpos($content, "'installed' => true") !== false
        || strpos($content, '"installed" => true') !== false;
}

/**
 * Block installer scripts when the app is already installed.
 */
function install_require_not_installed(): void {
    if (!app_is_installed()) {
        return;
    }

    // Allow the final "installation complete" screen on install/index.php
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (basename($script) === 'index.php' && strpos($script, '/install') !== false) {
        $step = (int) ($_GET['step'] ?? 0);
        if ($step === 5) {
            return;
        }
    }

    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Installation is disabled. This application is already installed.';
    exit;
}
