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
    
    // Helper function to detect HTTPS (works behind proxies like cPanel, Cloudflare, etc.)
    $isHttps = function() {
        // Direct HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        // Behind proxy - check forwarded proto
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
            $visitor = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
            if (isset($visitor['scheme']) && $visitor['scheme'] === 'https') {
                return true;
            }
        }
        // Standard port check
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        // X-Forwarded-SSL header
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        return false;
    };
    
    // If base_url is empty or not set, auto-detect it
    if (empty($baseUrl)) {
        $protocol = $isHttps() ? 'https://' : 'http://';
        
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
            $protocol = $isHttps() ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            
            // Add leading slash if not present
            if (!preg_match('/^\//', $baseUrl)) {
                $baseUrl = '/' . $baseUrl;
            }
            
            $baseUrl = $protocol . $host . $baseUrl;
        } else {
            // Config has full URL - force HTTPS if we detect HTTPS connection
            if ($isHttps() && strpos($baseUrl, 'http://') === 0) {
                $baseUrl = 'https://' . substr($baseUrl, 7);
            }
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

/**
 * Hostnames allowed for payment gateway checkout redirects.
 */
function payment_gateway_trusted_hosts() {
    return [
        'paystack.com',
        'checkout.paystack.com',
        'flutterwave.com',
        'checkout.flutterwave.com',
        'dev-flutterwave.com',
        'ravepay.co',
        'rave.sh',
        'flwv.io',
        'flwprdfhsiymnsuydihtvnsx.eu-west-1.awsapprunner.com',
        'flwprdflutterwavecomsbxhsiymnsuydihtvnsx.eu-west-1.awsapprunner.com',
        'flw-prd-api.com',
        'monnify.com',
        'sandbox.monnify.com',
        'stripe.com',
        'checkout.stripe.com',
        'paypal.com',
        'sandbox.paypal.com',
    ];
}

function is_trusted_payment_gateway_url($url) {
    if (!preg_match('/^https?:\/\//i', $url)) {
        return false;
    }
    $host = parse_url($url, PHP_URL_HOST);
    if ($host === null || $host === '') {
        return false;
    }
    $host = strtolower($host);
    foreach (payment_gateway_trusted_hosts() as $trusted) {
        $trusted = strtolower($trusted);
        if ($host === $trusted || str_ends_with($host, '.' . $trusted)) {
            return true;
        }
    }
    return false;
}

function redirect($url) {
    // Prevent open redirect vulnerability
    // Only allow absolute URLs if they match our base URL OR trusted payment gateways
    if (preg_match('/^https?:\/\//', $url)) {
        $baseUrl = base_url();
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        $redirectHost = parse_url($url, PHP_URL_HOST);

        $isTrusted = ($redirectHost === $baseHost) || is_trusted_payment_gateway_url($url);

        // Block untrusted external redirects (previously sent users to ERP dashboard)
        if (!$isTrusted && $redirectHost !== null && $redirectHost !== $baseHost) {
            error_log('Open redirect attempt blocked (payment checkout URL not whitelisted): ' . $url);
            $url = base_url('booking-wizard/step5');
        } elseif ($isTrusted && $redirectHost !== $baseHost) {
            error_log('Allowing redirect to payment gateway: ' . $url);
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

