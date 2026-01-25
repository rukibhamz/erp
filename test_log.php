<?php
$logFile = 'debug_log.txt'; // in root
if (file_put_contents($logFile, "Log test successful " . date('Y-m-d H:i:s') . "\n", FILE_APPEND) !== false) {
    echo "Write successful to $logFile";
} else {
    echo "Write FAILED to $logFile";
}
