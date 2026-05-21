<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cached read of a row from the settings table.
 */
function get_app_setting(string $key, $default = null) {
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            if (!class_exists('Database', false)) {
                return $default;
            }
            $db = Database::getInstance();
            $prefix = $db->getPrefix();
            $rows = $db->fetchAll("SELECT setting_key, setting_value FROM `{$prefix}settings`");
            foreach ($rows as $row) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log('get_app_setting: ' . $e->getMessage());
        }
    }

    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

/**
 * Session idle timeout in seconds (admin preference, min 5 min, max 24h).
 */
function get_session_timeout_seconds(): int {
    $seconds = (int) get_app_setting('session_timeout', 1800);
    if ($seconds < 300) {
        $seconds = 300;
    }
    if ($seconds > 86400) {
        $seconds = 86400;
    }
    return $seconds;
}
