<?php
// Version check - verify if server has latest code
echo "<h1>Code Version Check</h1>";

$file = __DIR__ . '/application/controllers/Booking_wizard.php';
echo "<h3>Booking_wizard.php</h3>";
echo "File Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "<br>";

// Check for specific line that should exist in latest version
$content = file_get_contents($file);
$checks = [
    'Creating invoice: subtotal=' => 'NEW logging for invoice amounts',
    'reference_type' => 'OLD column (should NOT exist)',
    "'reference' => 'BKG-'" => 'NEW reference format',
    '$this->customerModel = $this->loadModel' => 'CustomerModel loading'
];

echo "<h3>Code Checks:</h3>";
echo "<table border='1'>";
echo "<tr><th>Pattern</th><th>Description</th><th>Found?</th></tr>";
foreach ($checks as $pattern => $desc) {
    $found = strpos($content, $pattern) !== false;
    $color = ($pattern === 'reference_type') ? ($found ? 'red' : 'green') : ($found ? 'green' : 'red');
    echo "<tr><td>$pattern</td><td>$desc</td><td style='color:$color'>" . ($found ? 'YES' : 'NO') . "</td></tr>";
}
echo "</table>";

// Show last git commit
echo "<h3>Checking Git (if available):</h3>";
$gitLog = shell_exec('cd ' . escapeshellarg(__DIR__) . ' && git log -1 --oneline 2>&1');
echo "<pre>" . htmlspecialchars($gitLog ?? 'Git not available') . "</pre>";
