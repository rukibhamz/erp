<?php
/**
 * Quick check: pay-booking route is registered and regex matches.
 */
define('BASEPATH', dirname(__DIR__, 2) . '/application/');
define('BASE_URL', 'http://localhost/newerp/');

$routes = require BASEPATH . 'config/routes.php';
$pattern = 'customer-portal/pay-booking/(:num)';
$route = $routes[$pattern] ?? null;

echo "Route registered: " . ($route ? 'yes -> ' . $route : 'NO') . "\n";

$regexPattern = str_replace(['(:num)', '(:any)'], ['__CINUM__', '__CIANY__'], $pattern);
$regexPattern = preg_quote($regexPattern, '#');
$regexPattern = str_replace(['__CINUM__', '__CIANY__'], ['([0-9]+)', '(.+)'], $regexPattern);
$regexPattern = str_replace('\\/', '/', $regexPattern);
$regex = '#^' . $regexPattern . '$#i';

$path = 'customer-portal/pay-booking/123';
if (!preg_match($regex, $path)) {
    fwrite(STDERR, "FAIL: pay-booking route regex did not match\n");
    exit(1);
}
echo "OK: pay-booking route regex matches\n";
exit(0);
