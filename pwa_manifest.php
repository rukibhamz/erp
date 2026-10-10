<?php
/**
 * PWA Manifest Generator
 * Dynamically generates manifest.json with company logo
 */

define('BASEPATH', __DIR__ . '/application/');
define('ROOTPATH', __DIR__ . '/');
require_once BASEPATH . 'helpers/config_helper.php';
require_once BASEPATH . 'core/Database.php';

$config = load_app_config();
if (empty($config['installed'])) {
    die('Not configured');
}

// Get Database instance
$db = Database::getInstance();
$prefix = $config['db']['dbprefix'] ?? 'erp_';

$settings = [];
try {
    $rows = $db->fetchAll(
        "SELECT setting_key, setting_value FROM `{$prefix}settings`
         WHERE setting_key IN ('company_name', 'seo_site_title', 'seo_description', 'brand_color')"
    );
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = trim(html_entity_decode((string) $row['setting_value'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
} catch (Exception $e) {
    // Fail silently, use defaults
}

$siteName = ($settings['seo_site_title'] ?? '') ?: (($settings['company_name'] ?? '') ?: 'Acropolis Park');
$description = ($settings['seo_description'] ?? '')
    ?: "Book event spaces, picnics, photo and video shoots, and workspaces at {$siteName}.";
$themeColor = preg_match('/^#[0-9a-fA-F]{6}$/', $settings['brand_color'] ?? '') ? $settings['brand_color'] : '#000000';

// Home-screen labels truncate around 12 characters
$shortName = $siteName;
if (mb_strlen($shortName) > 12) {
    $firstWord = strtok($siteName, ' ');
    $shortName = ($firstWord !== false && mb_strlen($firstWord) <= 12) ? $firstWord : mb_substr($siteName, 0, 12);
}

header('Content-Type: application/manifest+json');

// Relative URLs resolve against this manifest's location, so subfolder installs work.
$manifest = [
    'name' => $siteName,
    'short_name' => $shortName,
    'description' => $description,
    'start_url' => './',
    'scope' => './',
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => $themeColor,
    'icons' => [
        [
            'src' => 'assets/img/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => 'assets/img/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
