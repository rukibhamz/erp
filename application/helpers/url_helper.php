<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function base_url($path = '') {
    // Try to get base_url from config
    $configFile = BASEPATH . 'config/config.installed.php';
    if (!file_exists($configFile)) {
        $configFile = BASEPATH . 'config/config.php';
    }
    
    if (file_exists($configFile)) {
        $config = require $configFile;
        $baseUrl = $config['base_url'] ?? '';
    } else {
        $baseUrl = '';
    }
    
    // If base_url is empty or not set, auto-detect it
    if (empty($baseUrl)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
                    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) 
                    ? 'https://' : 'http://';
        
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Get the script directory path (where index.php is located)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $scriptDir = dirname($scriptName);
        
        // Normalize: remove leading/trailing slashes, but keep one leading slash
        $scriptDir = '/' . trim($scriptDir, '/');
        
        // If script is in root, base is just /
        if ($scriptDir === '/' || $scriptDir === '/.') {
            $baseUrl = $protocol . $host . '/';
        } else {
            // Include the subdirectory in base URL
            $baseUrl = $protocol . $host . $scriptDir . '/';
        }
    } else {
        // base_url from config - ensure proper format
        $baseUrl = trim($baseUrl);
        
        // If it's a relative path, make it absolute
        if (!preg_match('/^https?:\/\//', $baseUrl)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            
            // Add leading slash if not present
            if (!preg_match('/^\//', $baseUrl)) {
                $baseUrl = '/' . $baseUrl;
            }
            
            $baseUrl = $protocol . $host . $baseUrl;
        }
        
        // Ensure trailing slash
        $baseUrl = rtrim($baseUrl, '/') . '/';
    }
    
    // Handle the path parameter
    if (!empty($path)) {
        $path = ltrim($path, '/');
        return $baseUrl . $path;
    }
    
    return $baseUrl;
}

function site_url($path = '') {
    return base_url($path);
}

function redirect($url) {
    // Prevent open redirect vulnerability
    // Only allow absolute URLs if they match our base URL
    if (preg_match('/^https?:\/\//', $url)) {
        // Check if URL is from same domain (prevent open redirect)
        $baseUrl = base_url();
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        $redirectHost = parse_url($url, PHP_URL_HOST);
        
        // Only allow redirects to same host
        if ($redirectHost !== $baseHost && $redirectHost !== null) {
            error_log('Open redirect attempt blocked: ' . $url);
            $url = base_url('dashboard'); // Fallback to dashboard
        }
        
        header('Location: ' . $url);
        exit;
    }
    
    // Get base URL
    $redirectUrl = base_url($url);
    
    // Ensure we don't redirect to XAMPP dashboard
    // If URL contains dashboard.php or xampp indicators, force to login
    if (preg_match('/(dashboard\.php|xampp)/i', $redirectUrl)) {
        $redirectUrl = base_url('login');
    }
    
    header('Location: ' . $redirectUrl);
    exit;
}

function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

