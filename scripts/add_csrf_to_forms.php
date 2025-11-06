<?php
/**
 * Script to help add CSRF tokens to all forms
 * 
 * This script scans views for forms and helps identify which ones need CSRF tokens
 * Run this from command line: php scripts/add_csrf_to_forms.php
 */

$viewDir = __DIR__ . '/../application/views';
$formsFound = [];
$formsWithCsrf = [];
$formsWithoutCsrf = [];

function scanDirectory($dir) {
    global $formsFound, $formsWithCsrf, $formsWithoutCsrf;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            
            // Check if file contains a form
            if (preg_match('/<form[^>]*method\s*=\s*["\']POST["\']/i', $content)) {
                $relativePath = str_replace(__DIR__ . '/../', '', $path);
                $formsFound[] = $relativePath;
                
                // Check if CSRF token is already present
                if (strpos($content, 'csrf_field()') !== false || strpos($content, 'csrf_token') !== false) {
                    $formsWithCsrf[] = $relativePath;
                } else {
                    $formsWithoutCsrf[] = $relativePath;
                }
            }
        }
    }
}

echo "Scanning for forms...\n\n";
scanDirectory($viewDir);

echo "=== FORM CSRF AUDIT ===\n\n";
echo "Total forms found: " . count($formsFound) . "\n";
echo "Forms with CSRF: " . count($formsWithCsrf) . "\n";
echo "Forms without CSRF: " . count($formsWithoutCsrf) . "\n\n";

if (!empty($formsWithCsrf)) {
    echo "✅ Forms WITH CSRF protection:\n";
    foreach ($formsWithCsrf as $form) {
        echo "  - $form\n";
    }
    echo "\n";
}

if (!empty($formsWithoutCsrf)) {
    echo "⚠️  Forms WITHOUT CSRF protection:\n";
    foreach ($formsWithoutCsrf as $form) {
        echo "  - $form\n";
    }
    echo "\n";
}

echo "=== INSTRUCTIONS ===\n";
echo "For each form without CSRF:\n";
echo "1. Open the file\n";
echo "2. Find the <form> tag\n";
echo "3. Add <?php echo csrf_field(); ?> immediately after the opening <form> tag\n";
echo "4. In the corresponding controller, add check_csrf(); at the start of POST handlers\n\n";

echo "Example:\n";
echo "  Before: <form method=\"POST\" action=\"...\">\n";
echo "  After:  <form method=\"POST\" action=\"...\">\n";
echo "          <?php echo csrf_field(); ?>\n";



