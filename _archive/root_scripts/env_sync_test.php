<?php
/**
 * Environment Synchronization Test
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ERP Environment Sync Tool</h1>";

echo "<h2>1. Path Information</h2>";
echo "<ul>";
echo "<li>Current Working Directory: " . getcwd() . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "</ul>";

echo "<h2>2. Critical File Integrity Check</h2>";
$files = [
    '.htaccess' => 'Root configuration',
    'index.php' => 'Main entry point',
    'repair.php' => 'Repair script (Root)',
    'debug_tools/repair.php' => 'Repair script (Debug)',
    'application/controllers/Spaces.php' => 'Space Controller (Multi-image logic)',
    'application/models/Space_model.php' => 'Space Model (Multi-image DB logic)',
    'application/views/spaces/view.php' => 'Space View (Carousel logic)',
    'application/config/database.php' => 'Database configuration'
];

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr><th>File Path</th><th>Description</th><th>Status</th><th>Size (Bytes)</th><th>Modified</th></tr>";

foreach ($files as $path => $desc) {
    $fullPath = __DIR__ . '/' . $path;
    echo "<tr>";
    echo "<td>$path</td>";
    echo "<td>$desc</td>";
    if (file_exists($fullPath)) {
        echo "<td style='color:green;'>EXISTS</td>";
        echo "<td>" . filesize($fullPath) . "</td>";
        echo "<td>" . date("Y-m-d H:i:s", filemtime($fullPath)) . "</td>";
    } else {
        echo "<td style='color:red;'>MISSING</td>";
        echo "<td>-</td>";
        echo "<td>-</td>";
    }
    echo "</tr>";
}
echo "</table>";

echo "<h2>3. Configuration Check</h2>";
$db_config = __DIR__ . '/application/config/database.php';
if (file_exists($db_config)) {
    echo "<p>Database config found. Testing connection...</p>";
    $db = [];
    $active_group = 'default';
    $query_builder = TRUE;
    // We can't easily require it here because of BASEPATH dependency usually
    echo "<p><em>Note: Connection test skipped in this tool to avoid side effects. Use repair.php for DB fixes.</em></p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>If you see 'MISSING' for critical files above, please re-upload them to the current working directory shown in section 1.</p>";
echo "<p><b>Security:</b> Please delete this file after use.</p>";
