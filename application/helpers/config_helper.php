<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unified application config loading and seamless legacy → .env migration.
 */

function config_bootstrap_paths() {
    if (!defined('BASEPATH')) {
        throw new RuntimeException('BASEPATH must be defined before loading config.');
    }
    if (!defined('ROOTPATH')) {
        define('ROOTPATH', dirname(rtrim(BASEPATH, '/\\')) . DIRECTORY_SEPARATOR);
    }
    if (!function_exists('env')) {
        require_once BASEPATH . 'helpers/env_helper.php';
    }
}

function config_is_env_wrapper($path) {
    if (!is_readable($path)) {
        return false;
    }
    $content = file_get_contents($path);
    return is_string($content) && strpos($content, 'config.php.example') !== false;
}

function config_read_legacy_array() {
    $candidates = [
        BASEPATH . 'config/config.installed.php',
        BASEPATH . 'config/config.php',
        BASEPATH . 'config/config.installed.php.legacy.bak',
        BASEPATH . 'config/config.php.legacy.bak',
    ];

    foreach ($candidates as $path) {
        if (!is_readable($path) || config_is_env_wrapper($path)) {
            continue;
        }

        $config = require $path;
        if (!is_array($config)) {
            continue;
        }

        $db = $config['db'] ?? $config['database'] ?? [];
        if (!empty($db['hostname']) && !empty($db['database']) && isset($db['password'])) {
            return $config;
        }
        if (!empty($config['encryption_key'])) {
            return $config;
        }
    }

    return null;
}

function config_env_key_missing() {
    $key = env('APP_ENCRYPTION_KEY', '');
    return $key === null || $key === '';
}

function config_env_quote($value) {
    $value = (string) $value;
    if ($value === '') {
        return '';
    }
    if (preg_match('/[\s#="\']/', $value)) {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }
    return $value;
}

function config_build_env_content(array $config) {
    $db = $config['db'] ?? $config['database'] ?? [];
    $lines = [
        '# Auto-generated from legacy config — customize as needed',
        'APP_INSTALLED=' . (($config['installed'] ?? true) ? 'true' : 'false'),
        'APP_ENV=' . config_env_quote($config['environment'] ?? 'production'),
        'APP_BASE_URL=' . config_env_quote($config['base_url'] ?? ''),
        'DB_HOST=' . config_env_quote($db['hostname'] ?? 'localhost'),
        'DB_USER=' . config_env_quote($db['username'] ?? 'root'),
        'DB_PASSWORD=' . config_env_quote($db['password'] ?? ''),
        'DB_NAME=' . config_env_quote($db['database'] ?? ''),
        'DB_PREFIX=' . config_env_quote($db['dbprefix'] ?? 'erp_'),
        'APP_ENCRYPTION_KEY=' . config_env_quote($config['encryption_key'] ?? ''),
    ];

    $restore = $config['db_restore'] ?? [];
    if (!empty($restore['username'])) {
        $lines[] = 'DB_RESTORE_USER=' . config_env_quote($restore['username']);
        $lines[] = 'DB_RESTORE_PASSWORD=' . config_env_quote($restore['password'] ?? '');
    }

    return implode("\n", $lines) . "\n";
}

function config_backup_legacy_file($path) {
    if (!is_readable($path) || config_is_env_wrapper($path)) {
        return;
    }
    $backup = $path . '.legacy.bak';
    if (!file_exists($backup)) {
        copy($path, $backup);
        @chmod($backup, 0600);
    }
}

function config_write_env_wrapper($path) {
    $content = "<?php\n"
        . "defined('BASEPATH') OR exit('No direct script access allowed');\n\n"
        . "return require __DIR__ . '/config.php.example';\n";
    file_put_contents($path, $content);
    @chmod($path, 0644);
}

function config_write_env_file($path, $content) {
    if (file_exists($path) && is_readable($path)) {
        $existing = file_get_contents($path);
        if (is_string($existing) && preg_match('/^APP_ENCRYPTION_KEY=(?!\\s*$).+/m', $existing)) {
            return false;
        }
    }

    if (file_put_contents($path, $content) === false) {
        error_log('Config migration: failed to write ' . $path);
        return false;
    }
    @chmod($path, 0600);
    return true;
}

/**
 * On first request after pull: move inline secrets to .env and convert config files to wrappers.
 */
function migrate_config_to_env() {
    static $migrated = false;
    if ($migrated) {
        return;
    }
    $migrated = true;

    config_bootstrap_paths();
    load_env_file(ROOTPATH . '.env');

    $legacy = config_read_legacy_array();
    $envPath = ROOTPATH . '.env';
    $configDir = BASEPATH . 'config/';
    $configPhp = $configDir . 'config.php';
    $configInstalled = $configDir . 'config.installed.php';

    if ($legacy && config_env_key_missing()) {
        if (config_write_env_file($envPath, config_build_env_content($legacy))) {
            load_env_file($envPath);
            error_log('Config migration: moved application secrets to .env');
        }
    }

    foreach ([$configInstalled, $configPhp] as $path) {
        if (!is_readable($path)) {
            continue;
        }
        if (!config_is_env_wrapper($path)) {
            config_backup_legacy_file($path);
            config_write_env_wrapper($path);
            error_log('Config migration: converted ' . basename($path) . ' to environment wrapper');
        }
    }

    if (!file_exists($configPhp)) {
        config_write_env_wrapper($configPhp);
    }
}

/**
 * Single entry point for application configuration (post-migration safe).
 */
function load_app_config() {
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    config_bootstrap_paths();
    migrate_config_to_env();
    load_env_file(ROOTPATH . '.env');

    $example = BASEPATH . 'config/config.php.example';
    if (!is_readable($example)) {
        throw new RuntimeException('Missing application/config/config.php.example');
    }

    $config = require $example;
    if (!is_array($config)) {
        throw new RuntimeException('Application configuration must return an array.');
    }

    return $config;
}

function app_config_installed() {
    $configDir = BASEPATH . 'config/';
    return file_exists($configDir . 'config.php')
        || file_exists($configDir . 'config.installed.php')
        || file_exists(ROOTPATH . '.env');
}
