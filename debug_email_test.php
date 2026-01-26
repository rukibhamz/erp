<?php
// Debug script for email test
session_start();
header('Content-Type: application/json');

$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'session_id' => session_id(),
    'session_csrf_token' => isset($_SESSION['csrf_token']) ? substr($_SESSION['csrf_token'], 0, 10) . '...' : 'NOT SET',
    'post_csrf_token' => isset($_POST['csrf_token']) ? substr($_POST['csrf_token'], 0, 10) . '...' : 'NOT SET',
    'header_csrf_token' => isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? substr($_SERVER['HTTP_X_CSRF_TOKEN'], 0, 10) . '...' : 'NOT SET',
    'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'role' => $_SESSION['role'] ?? 'NOT SET',
    'all_post' => array_keys($_POST),
    'is_ajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest',
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT SET',
];

// Test token match
if (isset($_SESSION['csrf_token']) && isset($_POST['csrf_token'])) {
    $debug['token_match'] = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']) ? 'YES' : 'NO';
    if ($debug['token_match'] === 'NO') {
        $debug['full_session_token'] = $_SESSION['csrf_token'];
        $debug['full_post_token'] = $_POST['csrf_token'];
    }
} else {
    $debug['token_match'] = 'CANNOT CHECK';
}

echo json_encode($debug, JSON_PRETTY_PRINT);
