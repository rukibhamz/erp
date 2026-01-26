<?php
// Session and CSRF Debug Script
echo "<h1>Session & CSRF Debug</h1>";

// Check session status
echo "<h2>1. PHP Session Status</h2>";
echo "Session Status: ";
switch (session_status()) {
    case PHP_SESSION_DISABLED:
        echo "<span style='color:red'>DISABLED</span>";
        break;
    case PHP_SESSION_NONE:
        echo "<span style='color:orange'>NONE (not started)</span>";
        break;
    case PHP_SESSION_ACTIVE:
        echo "<span style='color:green'>ACTIVE</span>";
        break;
}
echo "<br>";

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    $started = session_start();
    echo "Session Start Attempt: " . ($started ? "SUCCESS" : "FAILED") . "<br>";
}

// Session info
echo "<h2>2. Session Configuration</h2>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session Save Path Writable: " . (is_writable(session_save_path()) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Cookie Params: <pre>" . print_r(session_get_cookie_params(), true) . "</pre>";

// CSRF Token
echo "<h2>3. CSRF Token Status</h2>";
echo "CSRF Token In Session: " . (isset($_SESSION['csrf_token']) ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "<br>";
if (isset($_SESSION['csrf_token'])) {
    echo "CSRF Token Value: " . substr($_SESSION['csrf_token'], 0, 10) . "..." . "<br>";
    echo "CSRF Token Time: " . ($_SESSION['csrf_token_time'] ?? 'Not set') . "<br>";
}

// Test CSRF generation
echo "<h2>4. CSRF Test</h2>";
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    echo "Generated new CSRF token<br>";
}
echo "Current Token (first 10 chars): " . substr($_SESSION['csrf_token'], 0, 10) . "...<br>";

// Test form
echo "<h2>5. Test Form</h2>";
echo "<form method='POST'>";
echo "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>";
echo "<button type='submit' name='test_submit'>Test CSRF Submission</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Received!</h3>";
    $submittedToken = $_POST['csrf_token'] ?? 'MISSING';
    $sessionToken = $_SESSION['csrf_token'] ?? 'MISSING';
    echo "Submitted Token: " . substr($submittedToken, 0, 10) . "...<br>";
    echo "Session Token: " . substr($sessionToken, 0, 10) . "...<br>";
    echo "Match: " . ($submittedToken === $sessionToken ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "<br>";
}

// All session data
echo "<h2>6. All Session Variables</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
