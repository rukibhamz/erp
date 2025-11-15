<?php
/**
 * Test Email Function
 * 
 * Simple test endpoint for email functionality
 * Usage: test_email.php?to=your-email@example.com
 */

// Bootstrap the application
define('BASEPATH', __DIR__ . '/application/');
require_once BASEPATH . 'core/Base_Controller.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication (optional - remove if you want to test without login)
if (empty($_SESSION['user_id'])) {
    die('ERROR: Please log in first. Or remove this check to test without authentication.');
}

require_once BASEPATH . 'libraries/Email_sender.php';

$emailSender = new Email_sender();
$config = $emailSender->getConfig();

// Check configuration
if (empty($config['from_email']) || empty($config['smtp_user_set'])) {
    echo '<h2 style="color: red;">ERROR: Email not configured.</h2>';
    echo '<h3>Configuration Steps:</h3>';
    echo '<ol>';
    echo '<li>Open: <code>application/libraries/Email_sender.php</code></li>';
    echo '<li>Update lines 18-24 with your SMTP credentials:</li>';
    echo '<ul>';
    echo '<li><code>smtp_user</code> - Your Gmail address</li>';
    echo '<li><code>smtp_pass</code> - Your Gmail App Password (16 characters)</li>';
    echo '<li><code>from_email</code> - Your email address</li>';
    echo '</ul>';
    echo '<li>Or configure in config file: <code>application/config/config.installed.php</code></li>';
    echo '</ol>';
    echo '<h3>Gmail Setup:</h3>';
    echo '<ol>';
    echo '<li>Go to: <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>';
    echo '<li>Enable 2-Factor Authentication</li>';
    echo '<li>Go to: <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>';
    echo '<li>Generate App Password for "Mail"</li>';
    echo '<li>Copy the 16-character password</li>';
    echo '<li>Paste it in Email_sender.php line 21</li>';
    echo '</ol>';
    echo '<hr>';
    echo '<h3>Current Configuration:</h3>';
    echo '<pre>';
    print_r($config);
    echo '</pre>';
    exit;
}

// Get test email from query parameter
$testEmail = $_GET['to'] ?? 'test@example.com';

// Validate email
if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    die('ERROR: Invalid email address. Usage: test_email.php?to=your-email@example.com');
}

// Try to send test email
$result = $emailSender->sendInvoice(
    $testEmail,
    'Test Email from Invoice System',
    '<p>This is a test email. If you receive this, email is working!</p>'
);

echo '<html><head><title>Email Test Result</title>';
echo '<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }</style>';
echo '</head><body>';

echo '<h1>Email Test Result</h1>';

echo '<h2>Configuration:</h2>';
echo '<pre>';
print_r($config);
echo '</pre>';

echo '<h2>Test Result:</h2>';
echo '<pre>';
print_r($result);
echo '</pre>';

if (!$result['success']) {
    echo '<h2 class="error">ERROR: ' . htmlspecialchars($result['error']) . '</h2>';
    echo '<h3>Common Fixes:</h3>';
    echo '<ul>';
    echo '<li><strong>Gmail:</strong> Generate App Password at <a href="https://myaccount.google.com/apppasswords" target="_blank">https://myaccount.google.com/apppasswords</a></li>';
    echo '<li><strong>Check SMTP credentials</strong> in Email_sender.php (lines 18-24)</li>';
    echo '<li><strong>Verify firewall</strong> allows port 587 (TLS) or 465 (SSL)</li>';
    echo '<li><strong>Check error logs:</strong> ' . ini_get('error_log') . '</li>';
    echo '<li><strong>Enable debug mode:</strong> Uncomment <code>$this->mail->SMTPDebug = 2;</code> in Email_sender.php line 80</li>';
    echo '</ul>';
} else {
    echo '<h2 class="success">✓ SUCCESS: Test email sent!</h2>';
    echo '<p>Check your inbox at: <strong>' . htmlspecialchars($testEmail) . '</strong></p>';
}

echo '</body></html>';

