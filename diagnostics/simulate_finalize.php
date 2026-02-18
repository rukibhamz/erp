<?php
define('BASEPATH', __DIR__ . '/application/');
define('ROOTPATH', __DIR__ . '/');

$log = __DIR__ . "/simulation_trace.log";
file_put_contents($log, date('Y-m-d H:i:s') . " - STARTING SIMULATION (RETRY)\n");

try {
    // Load config
    $config_file = BASEPATH . 'config/config.installed.php';
    if (!file_exists($config_file)) {
        $config_file = BASEPATH . 'config/config.php';
    }
    $GLOBALS['config'] = require $config_file;

    require_once 'application/core/Autoloader.php';
    spl_autoload_register([new Autoloader(), 'load']);
    file_put_contents($log, "Autoloader registered\n", FILE_APPEND);

    // Load Helpers EXACTLY as index.php does
    require_once BASEPATH . 'helpers/url_helper.php';
    require_once BASEPATH . 'helpers/form_helper.php';
    require_once BASEPATH . 'helpers/security_helper.php';
    require_once BASEPATH . 'helpers/common_helper.php';
    require_once BASEPATH . 'helpers/permission_helper.php';
    require_once BASEPATH . 'helpers/currency_helper.php';
    require_once BASEPATH . 'helpers/module_helper.php';
    require_once BASEPATH . 'helpers/csrf_helper.php';
    require_once BASEPATH . 'helpers/number_helper.php';
    file_put_contents($log, "Helpers loaded\n", FILE_APPEND);

    require_once 'application/core/Database.php';
    file_put_contents($log, "Core Database loaded\n", FILE_APPEND);
    
    require_once 'application/core/Loader.php';
    file_put_contents($log, "Core Loader loaded\n", FILE_APPEND);
    
    require_once 'application/core/Base_Controller.php';
    file_put_contents($log, "Core Base_Controller loaded\n", FILE_APPEND);
    
    require_once 'application/controllers/Booking_wizard.php';
    file_put_contents($log, "Booking_wizard loaded\n", FILE_APPEND);

    // Mock Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Use valid IDs found earlier
    $_SESSION['booking_data'] = [
        'resource_id' => 2, // Facility ID 2 exists
        'date' => date('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'booking_type' => 'hourly',
        'quantity' => 1,
        'customer_name' => 'Test User ' . time(),
        'customer_email' => 'test_' . time() . '@example.com',
        'customer_phone' => '1234567890',
        'base_amount' => 1000.00,
        'total_amount' => 1000.00,
        'tax_amount' => 0.00,
        'discount_amount' => 0.00,
        'addons_total' => 0.00,
        'security_deposit' => 0.00,
        'is_guest' => true
    ];
    file_put_contents($log, "Session mocked\n", FILE_APPEND);

    // Mock POST
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['payment_plan'] = 'pay_later';
    $_POST['payment_method'] = 'cash';
    file_put_contents($log, "POST mocked\n", FILE_APPEND);

    // Suppress redirect if it tries to send headers
    if (!function_exists('redirect')) {
        // Redefining it here is tricky if url_helper already defined it.
        // But url_helper's redirect uses header() which will fail in CLI if output started.
    }

    $controller = new Booking_wizard();
    file_put_contents($log, "Controller instantiated\n", FILE_APPEND);
    
    $controller->finalize();
    file_put_contents($log, "finalize() completed\n", FILE_APPEND);

} catch (Throwable $e) {
    file_put_contents($log, "FATAL ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
}

echo "Simulation finished. See simulation_trace.log\n";
