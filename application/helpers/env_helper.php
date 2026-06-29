<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Load environment variables from a .env file into getenv()/$_ENV.
 * Does not overwrite variables already set in the process environment.
 */
function load_env_file($path) {
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $equalsPos = strpos($line, '=');
        if ($equalsPos === false) {
            continue;
        }

        $name = trim(substr($line, 0, $equalsPos));
        $value = trim(substr($line, $equalsPos + 1));

        if ($name === '') {
            continue;
        }

        if ((strlen($value) >= 2)
            && (($value[0] === '"' && substr($value, -1) === '"')
                || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($name) !== false) {
            continue;
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

/**
 * Read an environment variable with an optional default.
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}
