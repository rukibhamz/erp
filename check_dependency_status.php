<?php
// Script to check dependency status on the server
header('Content-Type: text/plain');

$vendorDir = __DIR__ . '/vendor';
$autoload = $vendorDir . '/autoload.php';
$dompdf = $vendorDir . '/dompdf/dompdf';
$phpmailer = $vendorDir . '/phpmailer/phpmailer';

echo "Dependency Status Check\n";
echo "=======================\n\n";

echo "Checking vendor directory: " . $vendorDir . "\n";
if (is_dir($vendorDir)) {
    echo "[OK] Vendor directory exists.\n";
} else {
    echo "[FAIL] Vendor directory is MISSING.\n";
}

echo "\nChecking autoload.php: " . $autoload . "\n";
if (file_exists($autoload)) {
    echo "[OK] autoload.php found.\n";
} else {
    echo "[FAIL] autoload.php is MISSING.\n";
}

echo "\nChecking DomPDF: \n";
if (is_dir($dompdf) || file_exists($vendorDir . '/dompdf/dompdf/src/Dompdf.php')) {
    echo "[OK] DomPDF appears to be installed.\n";
} else {
    echo "[FAIL] DomPDF is MISSING.\n";
}

echo "\nChecking PHPMailer: \n";
if (is_dir($phpmailer) || file_exists($vendorDir . '/phpmailer/phpmailer/src/PHPMailer.php')) {
    echo "[OK] PHPMailer appears to be installed.\n";
} else {
    echo "[FAIL] PHPMailer is MISSING.\n";
}

echo "\n\nSUMMARY:\n";
if (file_exists($autoload)) {
    echo "Dependencies appear to be present. PDF export and Email should work if versions are correct.";
} else {
    echo "CRITICAL: The 'vendor' folder is incomplete or missing. Please upload the 'vendor' folder to the document root.";
}
?>
