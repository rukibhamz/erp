<?php
/**
 * PWA Manifest Generator
 * Dynamically generates manifest.json with company logo
 */

define('BASEPATH', __DIR__ . '/application/');
require_once BASEPATH . 'core/Database.php';

// Load config to get DB details
$config_installed_file = BASEPATH . 'config/config.installed.php';
$config_file = BASEPATH . 'config/config.php';

if (file_exists($config_installed_file)) {
    $config = require $config_installed_file;
} elseif (file_exists($config_file)) {
    $config = require $config_file;
} else {
    die('Not configured');
}

// Get Database instance
$db = Database::getInstance($config['db']);
$prefix = $config['db']['dbprefix'] ?? 'erp_';

// Fetch company name and logo from settings
$companyName = 'Business Management System';
$companyLogo = 'assets/img/logo.png'; // Default fallback

try {
    $settings = $db->fetchAll("SELECT setting_key, setting_value FROM `{$prefix}settings` WHERE setting_key IN ('company_name', 'company_logo')");
    foreach ($settings as $row) {
        if ($row['setting_key'] === 'company_name' && !empty($row['setting_value'])) {
            $companyName = $row['setting_value'];
        }
        if ($row['setting_key'] === 'company_logo' && !empty($row['setting_value'])) {
            $companyLogo = 'uploads/company/' . $row['setting_value'];
        }
    }
} catch (Exception $e) {
    // Fail silently, use defaults
}

header('Content-Type: application/manifest+json');

$manifest = [
    'name' => $companyName,
    'short_name' => $companyName,
    'description' => 'Business Management System for ' . $companyName,
    'start_url' => '/',
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#000000',
    'icons' => [
        [
            'src' => $companyLogo,
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $companyLogo,
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
