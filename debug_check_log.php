<?php
// Script to read the last 50 lines of the PHP error log
$logFile = ini_get('error_log');
echo "<h1>PHP Error Log Reader</h1>";
echo "Configured Log File: " . $logFile . "<br>";

if (!file_exists($logFile)) {
    echo "<h3>Log file not found! Checking standard locations...</h3>";
    $candidates = [
        'c:\xampp\php\logs\php_error_log',
        'c:\xampp\apache\logs\error.log',
        __DIR__ . '/application/logs/log-' . date('Y-m-d') . '.php'
    ];
    
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $logFile = $candidate;
            echo "Found log at: $logFile <br>";
            break;
        }
    }
}

if ($logFile && file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo "<pre>";
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<h3 style='color:red'>No log file found.</h3>";
}
