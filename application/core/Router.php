<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Router {
    private $controller = 'Dashboard';
    private $method = 'index';
    private $params = [];

    public function __construct() {
        $this->parseUrl();
    }

    private function parseUrl() {
        // Get URL from query string first (set by .htaccess RewriteRule)
        $url = $_GET['url'] ?? '';

        // If url parameter is empty, extract from REQUEST_URI (fallback for non-rewrite scenarios)
        if (empty($url) && !empty($_SERVER['REQUEST_URI'])) {
            $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
            $scriptDir = dirname($scriptName);

            // Normalize paths
            $requestUri = '/' . trim($requestUri, '/');
            $scriptDir = '/' . trim($scriptDir, '/');

            // Remove query string if present
            $requestUri = strtok($requestUri, '?');

            // Handle subdirectory installations (e.g., /erp/)
            if ($scriptDir !== '/' && $scriptDir !== '/.' && strpos($requestUri, $scriptDir) === 0) {
                // Remove the script directory from request URI
                $url = substr($requestUri, strlen($scriptDir));
            } elseif ($requestUri === '/' || $requestUri === $scriptDir . '/') {
                // Root request
                $url = '';
            } else {
                // Use the request URI as-is (minus leading slash)
                $url = trim($requestUri, '/');
            }
        }

        // Sanitize and clean URL
        $url = trim($url, '/');
        if (!empty($url)) {
            $url = filter_var($url, FILTER_SANITIZE_URL);
            // Remove any double slashes that might have been introduced
            $url = preg_replace('#/+#', '/', $url);
        }

        // Load routes
        $routes = require BASEPATH . 'config/routes.php';

        // Ensure routes is an array
        if (!is_array($routes)) {
            $routes = [];
        }

        // If URL is empty, use default controller
        // Default is Dashboard, but authentication will redirect to login if needed
        if (empty($url)) {
            if (isset($routes['default_controller'])) {
                $default = explode('/', $routes['default_controller']);
                $this->controller = $default[0] ?? 'Dashboard';
                $this->method = $default[1] ?? 'index';
            } else {
                $this->controller = 'Dashboard';
                $this->method = 'index';
            }
            return;
        }

        $urlParts = explode('/', $url);
        $path = strtolower($url); // Normalize path to lowercase for consistent matching

        // SPECIAL CASE: Handle tax/compliance routes BEFORE route matching
        // This ensures tax/compliance/* routes are handled correctly
        if (count($urlParts) >= 2 && strtolower($urlParts[0]) === 'tax' && strtolower($urlParts[1]) === 'compliance') {
            // Check if there's an exact route match first
            $pathLower = strtolower($path);
            $exactRoute = null;
            foreach ($routes as $pattern => $route) {
                if ($pattern === 'default_controller' || $pattern === '404_override') {
                    continue;
                }
                $patternLower = strtolower($pattern);
                $patternClean = rtrim($patternLower, '/');
                $pathClean = rtrim($pathLower, '/');
                if ($patternLower === $pathLower || $patternClean === $pathClean) {
                    $exactRoute = $route;
                    break;
                }
            }

            if ($exactRoute) {
                // Use the route definition
                $routeParts = explode('/', $exactRoute);
                $this->controller = $routeParts[0];
                $this->method = $routeParts[1] ?? 'index';
                if (count($routeParts) > 2) {
                    $this->params = array_slice($routeParts, 2);
                }
                return;
            } else {
                // Fallback: map directly to Tax_compliance controller
                $this->controller = 'Tax_compliance';
                if (isset($urlParts[2]) && !empty($urlParts[2])) {
                    $this->method = $urlParts[2];
                } else {
                    $this->method = 'index';
                }
                if (count($urlParts) > 3) {
                    $this->params = array_slice($urlParts, 3);
                }
                return;
            }
        }

        // Check exact route matches first (case-insensitive)
        // Sort routes by length (longest first) to match more specific routes first
        $sortedRoutes = [];
        foreach ($routes as $pattern => $route) {
            if ($pattern === 'default_controller' || $pattern === '404_override') {
                continue;
            }
            $sortedRoutes[$pattern] = strlen($pattern);
        }
        arsort($sortedRoutes); // Sort by length descending

        $pathLower = strtolower($path);
        foreach (array_keys($sortedRoutes) as $pattern) {
            $route = $routes[$pattern];
            // Exact match (case-insensitive)
            $patternLower = strtolower($pattern);
            // Also check with trailing slash removed for both
            $patternClean = rtrim($patternLower, '/');
            $pathClean = rtrim($pathLower, '/');
            if ($patternLower === $pathLower || $patternClean === $pathClean) {
                $routeParts = explode('/', $route);
                $this->controller = $routeParts[0];
                $this->method = $routeParts[1] ?? 'index';
                if (count($routeParts) > 2) {
                    $this->params = array_slice($routeParts, 2);
                }
                return;
            }
        }

        // Check pattern routes (with parameters like (:num), (:any))
        // Sort pattern routes by specificity (longest/most specific first)
        $patternRoutes = [];
        foreach ($routes as $pattern => $route) {
            if ($pattern === 'default_controller' || $pattern === '404_override') {
                continue;
            }

            // Skip exact matches (already checked)
            if (strpos($pattern, '(') === false) {
                continue;
            }

            // Calculate specificity score:
            // 1. Length (longer = more specific)
            // 2. Parameter type preference (:num before :any)
            $specificity = strlen($pattern) * 1000; // Base score from length

            // Prefer (:num) over (:any) for better matching
            // Count how many :num vs :any parameters exist
            $numCount = substr_count($pattern, '(:num)');
            $anyCount = substr_count($pattern, '(:any)');

            // Routes with :num are more specific than :any
            if ($numCount > 0 && $anyCount === 0) {
                $specificity += 100; // Bonus for :num only
            } elseif ($anyCount > 0 && $numCount === 0) {
                $specificity -= 50; // Penalty for :any only
            }
            // Mixed patterns get base score

            $patternRoutes[$pattern] = [
                'route' => $route,
                'specificity' => $specificity
            ];
        }

        // Sort by specificity (highest first)
        uasort($patternRoutes, function($a, $b) {
            return $b['specificity'] - $a['specificity'];
        });

        // Process sorted pattern routes
        foreach ($patternRoutes as $pattern => $routeData) {
            $route = $routeData['route'];

            // Convert route pattern to regex (case-insensitive for better matching)
            // Replace placeholders before preg_quote — preg_quote escapes ":" so '\(:num\)' never matches.
            $regexPattern = str_replace(['(:num)', '(:any)'], ['__CINUM__', '__CIANY__'], $pattern);
            $regexPattern = preg_quote($regexPattern, '#');
            $regexPattern = str_replace(['__CINUM__', '__CIANY__'], ['([0-9]+)', '(.+)'], $regexPattern);
            $regexPattern = str_replace('\\/', '/', $regexPattern);
            $regex = '#^' . $regexPattern . '$#i'; // Added 'i' flag for case-insensitive matching

            // Match against lowercase path (already normalized)
            if (preg_match($regex, $path, $matches)) {
                error_log("Router: Pattern '{$pattern}' MATCHED path '{$path}' with regex '{$regex}'");
                array_shift($matches); // Remove full match

                $routeParts = explode('/', $route);
                $this->controller = $routeParts[0];
                $this->method = $routeParts[1] ?? 'index';

                // Extract parameters from route string ($1, $2, etc.)
                // CRITICAL FIX: Properly map route parameters to URL matches
                $params = [];

                // First, collect all parameter placeholders from route (e.g., $1, $2)
                $paramPlaceholders = [];
                foreach ($routeParts as $part) {
                    if (preg_match('#\$(\d+)#', $part, $paramMatch)) {
                        $paramPlaceholders[] = intval($paramMatch[1]);
                    }
                }

                // Sort placeholders to ensure correct order
                sort($paramPlaceholders);

                // Map each placeholder to its corresponding match
                foreach ($paramPlaceholders as $placeholderIndex) {
                    $matchIndex = $placeholderIndex - 1; // $1 -> index 0, $2 -> index 1, etc.
                    if (isset($matches[$matchIndex])) {
                        // Convert numeric parameters to integers for better type safety
                        $paramValue = $matches[$matchIndex];
                        if (preg_match('/^[0-9]+$/', $paramValue)) {
                            $params[] = intval($paramValue);
                        } else {
                            $params[] = $paramValue;
                        }
                    }
                }

                // If no placeholders found but we have matches, use matches directly
                if (empty($paramPlaceholders) && !empty($matches)) {
                    $params = $matches;
                }

                $this->params = $params;

                // Log successful route match for debugging
                error_log("Router: Matched pattern '{$pattern}' -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));

                return;
            }
        }

        // No route match, use direct controller/method parsing
        // CRITICAL FIX: Special handling for receivables and payables
        // These modules use the module name as the controller (Receivables, Payables)
        // not a sub-controller like inventory/items
        $firstPart = strtolower($urlParts[0] ?? '');

        if ($firstPart === 'receivables') {
            // Receivables module - map directly to Receivables controller
            // Handle method names like "editCustomer", "viewCustomer", "createInvoice"
            $this->dispatchResourceActionRoute($urlParts, 'Receivables', [
                'customers:edit'    => 'editCustomer',
                'customers:view'    => 'viewCustomer',
                'customers:create'  => 'createCustomer',
                'invoices:edit'     => 'editInvoice',
                'invoices:view'     => 'viewInvoice',
                'invoices:create'   => 'createInvoice',
                'invoices:payment'  => 'recordPayment',
                'payments:create'   => 'createPayment',
                'customers:history' => 'customerHistory',
            ], ['deleteCustomer', 'editCustomer', 'viewCustomer', 'customerHistory'], 'customers');
            return;
        }

        if ($firstPart === 'cash') {
            // Cash module - map directly to Cash controller
            // Handle method names like "editAccount", "createAccount", "deleteAccount"
            $this->dispatchResourceActionRoute($urlParts, 'Cash', [
                'accounts:edit'   => 'editAccount',
                'accounts:create' => 'createAccount',
                'accounts:delete' => 'deleteAccount',
            ], [], 'index');
            return;
        }

        if ($firstPart === 'payables') {
            // Payables module - map directly to Payables controller
            // Handle method names like "editVendor", "viewBill", "createBill"
            $this->dispatchResourceActionRoute($urlParts, 'Payables', [
                'vendors:edit'    => 'editVendor',
                'vendors:view'    => 'viewVendor',
                'vendors:delete'  => 'deleteVendor',
                'vendors:create'  => 'createVendor',
                'bills:edit'      => 'editBill',
                'bills:view'      => 'viewBill',
                'bills:create'    => 'createBill',
                'bills:delete'    => 'deleteBill',
                'vendors:history' => 'vendorHistory',
            ], ['deleteVendor', 'editVendor', 'viewVendor', 'vendorHistory'], 'vendors');
            return;
        }

        // CRITICAL FIX: Handle multi-segment module URLs (e.g., inventory/items/view/123)
        // Special handling for locations routes (MUST be before module prefix handling)
        // Locations is both a module prefix AND a controller name
        if (count($urlParts) >= 2 && strtolower($urlParts[0]) === 'locations') {
            $method = strtolower($urlParts[1]);

            // Handle booking routes with hyphens
            if ($method === 'create-booking' || $method === 'booking-calendar' || $method === 'view-booking' ||
                $method === 'get-spaces-for-booking' || $method === 'check-booking-availability') {
                // Map hyphenated methods to camelCase
                $methodMap = [
                    'create-booking' => 'createBooking',
                    'booking-calendar' => 'bookingCalendar',
                    'view-booking' => 'viewBooking',
                    'get-spaces-for-booking' => 'getSpacesForBooking',
                    'check-booking-availability' => 'checkBookingAvailability'
                ];

                $this->controller = 'Locations';
                $this->method = $methodMap[$method] ?? $method;

                // Handle parameters
                if (count($urlParts) > 2) {
                    $this->params = $this->castNumericParams(array_slice($urlParts, 2));
                }

                error_log("Router: Locations booking route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
                return;
            }

            // Handle bookings route
            if ($method === 'bookings') {
                $this->controller = 'Locations';
                $this->method = 'bookings';
                if (count($urlParts) > 2) {
                    $this->params = $this->castNumericParams(array_slice($urlParts, 2));
                } else if (count($urlParts) === 2 && !empty($urlParts[1]) && is_numeric($urlParts[1])) {
                    // Handle locations/bookings/123 format
                    $this->params = [intval($urlParts[1])];
                }
                error_log("Router: Locations bookings route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
                return;
            }

            // Check if it's a Locations controller method (view, edit, create, delete)
            if (in_array($method, ['view', 'edit', 'create', 'delete', 'index'])) {
                $this->controller = 'Locations';
                $this->method = $method;
                if (count($urlParts) > 2) {
                    $this->params = $this->castNumericParams(array_slice($urlParts, 2));
                }
                error_log("Router: Locations route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
                return;
            }
        }

        // Special handling for properties routes (legacy, maps to Locations)
        if (count($urlParts) >= 2 && strtolower($urlParts[0]) === 'properties') {
            $method = strtolower($urlParts[1]);
            if (in_array($method, ['view', 'edit', 'create', 'delete', 'index'])) {
                $this->controller = 'Locations';
                $this->method = $method;
                if (count($urlParts) > 2) {
                    $this->params = $this->castNumericParams(array_slice($urlParts, 2));
                }
                error_log("Router: Properties route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
                return;
            }
        }

        // Special handling for space-bookings routes (hyphenated controller name)
        if (count($urlParts) >= 1 && strtolower($urlParts[0]) === 'space-bookings') {
            $this->controller = 'Space_bookings';
            if (isset($urlParts[1]) && !empty($urlParts[1])) {
                $method = strtolower($urlParts[1]);
                // Map common methods
                if (in_array($method, ['create', 'calendar', 'view', 'index', 'check-availability', 'confirm', 'cancel'])) {
                    if ($method === 'check-availability') {
                        $this->method = 'checkAvailability';
                    } else {
                        $this->method = $method;
                    }
                } else {
                    $this->method = 'index';
                }
            } else {
                $this->method = 'index';
            }
            if (count($urlParts) > 2) {
                $this->params = $this->castNumericParams(array_slice($urlParts, 2));
            }
            error_log("Router: Space_bookings route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
            return;
        }

        // Check if this looks like a module/controller/method/param pattern
        if (count($urlParts) >= 3) {
            // Common module prefixes that should be stripped (removed 'locations' and 'bookings' since they're handle as top-level or handled specifically)
            $modulePrefixes = ['inventory', 'utilities', 'accounting', 'tax', 'cash'];
            $firstPart = strtolower($urlParts[0]);

            // If first part is a known module prefix, treat second part as controller
            if (in_array($firstPart, $modulePrefixes)) {
                $controllerPart = $urlParts[1];
                $methodPart = $urlParts[2] ?? 'index';

                // Convert controller name (e.g., items -> Items)
                $parts = explode('_', $controllerPart);
                $parts = array_map('ucfirst', $parts);
                $this->controller = implode('_', $parts);
                $this->method = $methodPart;

                // Remaining parts are parameters
                if (count($urlParts) > 3) {
                    $this->params = $this->castNumericParams(array_slice($urlParts, 3));
                }

                error_log("Router: Multi-segment URL parsed -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
                return;
            }
        }

        // Handle underscore controllers (e.g., tax_compliance -> Tax_compliance)
        // Special handling for booking-wizard routes (MUST be before general parsing)
        if (count($urlParts) >= 1 && strtolower($urlParts[0]) === 'booking-wizard') {
            // Map booking-wizard routes to Booking_wizard controller
            $this->controller = 'Booking_wizard';
            if (isset($urlParts[1]) && !empty($urlParts[1])) {
                $method = strtolower($urlParts[1]);
                // Map step methods
                if ($method === 'step1') {
                    $this->method = 'step1';
                } elseif ($method === 'step2' && isset($urlParts[2])) {
                    $this->method = 'step2';
                    $this->params = [intval($urlParts[2])];
                } elseif ($method === 'step3' && isset($urlParts[2])) {
                    $this->method = 'step3';
                    $this->params = [intval($urlParts[2])];
                } elseif ($method === 'step4') {
                    $this->method = 'step4';
                } elseif ($method === 'step5') {
                    $this->method = 'step5';
                } elseif ($method === 'get-time-slots') {
                    $this->method = 'getTimeSlots';
                } elseif ($method === 'space' && isset($urlParts[2]) && $urlParts[2] === 'details' && isset($urlParts[3])) {
                    $this->method = 'space_details';
                    $this->params = [intval($urlParts[3])];
                } elseif ($method === 'getspacesforlocation') {
                    $this->method = 'getSpacesForLocation';
                } elseif ($method === 'save-step') {
                    $this->method = 'saveStep';
                } elseif ($method === 'validate-promo') {
                    $this->method = 'validatePromoCode';
                } elseif ($method === 'finalize') {
                    $this->method = 'finalize';
                } elseif ($method === 'confirmation' && isset($urlParts[2])) {
                    $this->method = 'confirmation';
                    $this->params = [intval($urlParts[2])];
                } else {
                    $this->method = 'step1'; // Default to step1
                }
            } else {
                $this->method = 'step1';
            }
            return;
        }

        // Special handling for customer-portal routes (MUST be before general parsing)
        if (count($urlParts) >= 1 && strtolower($urlParts[0]) === 'customer-portal') {
            // Map customer-portal routes to Customer_portal controller
            $this->controller = 'Customer_portal';
            if (isset($urlParts[1]) && !empty($urlParts[1])) {
                $method = strtolower($urlParts[1]);

                // Map hyphenated methods to camelCase if needed, or handle directly
                $customerPortalMethods = [
                    'forgot-password' => 'forgotPassword',
                    'reset-password' => 'resetPassword',
                    'view-booking' => 'viewBooking',
                    'pay-booking' => 'payBooking',
                    'reschedule-booking' => 'rescheduleBooking',
                    'reschedule-quote' => 'getRescheduleQuote',
                ];
                $this->method = $customerPortalMethods[$method] ?? $method;

                // Handle parameters
                if (count($urlParts) > 2) {
                    $this->params = $this->castNumericParams(array_slice($urlParts, 2));
                }
            } else {
                $this->method = 'index';
            }
            return;
        }

        // Special handling for tax/compliance routes (MUST be before general tax parsing)
        if (count($urlParts) >= 2 && strtolower($urlParts[0]) === 'tax' && strtolower($urlParts[1]) === 'compliance') {
            // Handle tax/compliance routes - map to Tax_compliance controller
            $this->controller = 'Tax_compliance';
            if (isset($urlParts[2]) && !empty($urlParts[2])) {
                $this->method = $urlParts[2];
            } else {
                $this->method = 'index';
            }
            if (count($urlParts) > 3) {
                $this->params = array_slice($urlParts, 3);
            }
            return;
        }

        // Special handling for settings routes with hyphens
        if (count($urlParts) >= 2 && strtolower($urlParts[0]) === 'settings') {
            $method = strtolower($urlParts[1]);

            // Map hyphenated settings routes to camelCase methods
            if ($method === 'payment-gateways') {
                $this->controller = 'Settings';

                if (count($urlParts) >= 3) {
                    $action = strtolower($urlParts[2]);
                    if ($action === 'edit' && isset($urlParts[3])) {
                        $this->method = 'editGateway';
                        $this->params = [intval($urlParts[3])];
                    } elseif ($action === 'toggle' && isset($urlParts[3])) {
                        $this->method = 'toggleGateway';
                        $this->params = [intval($urlParts[3])];
                    } else {
                        $this->method = 'paymentGateways';
                        $this->params = [];
                    }
                } else {
                    $this->method = 'paymentGateways';
                    $this->params = [];
                }

                error_log("Router: Settings payment-gateways route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
                return;
            }

            // Handle settings/roles routes
            if ($method === 'roles') {
                $this->controller = 'Settings';
                $this->method = 'roles';
                $this->params = [];
                error_log("Router: Settings roles route matched -> Controller: {$this->controller}, Method: {$this->method}");
                return;
            }

            // Handle settings/edit-role/ID routes
            if ($method === 'edit-role' && isset($urlParts[2])) {
                $this->controller = 'Settings';
                $this->method = 'editRole';
                $this->params = [intval($urlParts[2])];
                error_log("Router: Settings edit-role route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
                return;
            }
        }

        // Special handling for payment routes
        if (count($urlParts) >= 2 && strtolower($urlParts[0]) === 'payment') {
            $method = strtolower($urlParts[1]);
            $this->controller = 'Payment';
            $this->method = $method;

            if (count($urlParts) > 2) {
                $this->params = $this->castNumericParams(array_slice($urlParts, 2));
            } else {
                $this->params = [];
            }

            error_log("Router: Payment route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
            return;
        }
        if (isset($urlParts[0]) && !empty($urlParts[0])) {
            // Convert tax_compliance to Tax_compliance (preserve underscores)
            $parts = explode('_', $urlParts[0]);
            $parts = array_map('ucfirst', $parts);
            $this->controller = implode('_', $parts);
        }

        if (isset($urlParts[1]) && !empty($urlParts[1])) {
            $this->method = $urlParts[1];
        }

        if (count($urlParts) > 2) {
            $this->params = $this->castNumericParams(array_slice($urlParts, 2));
        }

        // Log fallback parsing result
        error_log("Router: Fallback parsing -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
    }

    /**
     * Generic dispatcher for "<controller>/<resource>/<action>[/<id>]" style modules
     * (Receivables, Payables, Cash) that map resource+action pairs to camelCase methods.
     *
     * @param array $actionMap '<resource>:<action>' => method name. The id (if any) is read
     *                         from $urlParts[3], except for "create" actions which take none.
     * @param array $directMethods Resource segments that are already a full method name
     *                              (e.g. "editCustomer"), with the id in the next segment.
     */
    private function dispatchResourceActionRoute(array $urlParts, string $controller, array $actionMap, array $directMethods, string $defaultMethod): void {
        $this->controller = $controller;

        if (count($urlParts) >= 3) {
            $resource = $urlParts[1] ?? '';
            $action = $urlParts[2] ?? '';
            $key = $resource . ':' . $action;

            if (isset($actionMap[$key])) {
                $this->method = $actionMap[$key];
                $this->params = ($action !== 'create' && count($urlParts) > 3) ? [intval($urlParts[3])] : [];
            } elseif (in_array($resource, $directMethods, true)) {
                $this->method = $resource;
                $this->params = $action ? [intval($action)] : [];
            } else {
                // Generic fallback mapping: <controller>/<method>/<param>
                $this->method = $action ?: ($resource ?: $defaultMethod);
                $this->params = count($urlParts) > 3 ? array_slice($urlParts, 3) : [];
            }
        } elseif (count($urlParts) === 2) {
            $this->method = $urlParts[1] ?? $defaultMethod;
            $this->params = [];
        } else {
            $this->method = $defaultMethod;
            $this->params = [];
        }

        $this->params = $this->castNumericParams($this->params);
        error_log("Router: {$controller} URL parsed -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
    }

    /**
     * Convert numeric-looking string URL segments to integers.
     */
    private function castNumericParams(array $params): array {
        return array_map(function ($param) {
            return is_string($param) && preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
        }, $params);
    }

    public function dispatch() {
        // Log routing information for debugging
        error_log("Router dispatch: Controller={$this->controller}, Method={$this->method}, Params=" . json_encode($this->params));

        // Handle underscore controllers (e.g., Tax_compliance)
        $controllerName = $this->controller;
        $controllerFile = BASEPATH . 'controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            // Try to find Error404 controller
            $error404File = BASEPATH . 'controllers/Error404.php';
            if (file_exists($error404File)) {
                require_once $error404File;
                if (class_exists('Error404')) {
                    $this->controller = 'Error404';
                    $this->method = 'index';
                    $controllerName = 'Error404';
                    $controllerFile = $error404File;
                    error_log("Router: Controller file not found, using Error404");
                } else {
                    http_response_code(404);
                    error_log("Router ERROR: Controller '{$this->controller}' not found and Error404 class not found.");
                    die("404 - Page not found. Controller '{$this->controller}' not found. Error404 class also not found.");
                }
            } else {
                http_response_code(404);
                error_log("Router ERROR: Controller file '{$controllerFile}' not found.");
                die("404 - Page not found. Controller '{$this->controller}' not found.");
            }
        } else {
            require_once $controllerFile;
        }

        // Try exact match first, then case-insensitive match
        if (!class_exists($controllerName)) {
            // Try case-insensitive class lookup
            $classes = get_declared_classes();
            foreach ($classes as $class) {
                if (strtolower($class) === strtolower($controllerName)) {
                    $controllerName = $class;
                    error_log("Router: Found controller class via case-insensitive lookup: {$controllerName}");
                    break;
                }
            }

            if (!class_exists($controllerName)) {
                http_response_code(404);
                error_log("Router ERROR: Controller class '{$controllerName}' not found in file '{$controllerFile}'");
                die("Controller '{$this->controller}' class not found in file.");
            }
        }

        // Global CSRF for state-changing requests (before controller instantiation)
        if (function_exists('enforce_global_csrf')) {
            enforce_global_csrf($controllerName, $this->method);
        }

        // Use the actual class name (may have been corrected by case-insensitive lookup)
        try {
            $controller = new $controllerName();
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Router ERROR: Failed to instantiate controller '{$controllerName}': " . $e->getMessage());
            die("Error instantiating controller: " . $e->getMessage());
        }

        if (!method_exists($controller, $this->method)) {
            http_response_code(404);
            error_log("Router ERROR: Method '{$this->method}' not found in controller '{$controllerName}'. Available methods: " . implode(', ', get_class_methods($controller)));
            die("Method {$this->method} not found in {$controllerName}.");
        }

        // Log method call details
        error_log("Router: Calling {$controllerName}::{$this->method}(" . implode(', ', array_map(function($p) {
            return is_scalar($p) ? var_export($p, true) : gettype($p);
        }, $this->params)) . ")");

        // Call the controller method with parameters
        try {
            call_user_func_array([$controller, $this->method], $this->params);
        } catch (TypeError $e) {
            http_response_code(500);
            error_log("Router ERROR: Type error calling {$controllerName}::{$this->method}: " . $e->getMessage());
            error_log("Router ERROR: Expected parameters: " . json_encode($this->params));
            die("Type error: " . $e->getMessage());
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Router ERROR: Exception calling {$controllerName}::{$this->method}: " . $e->getMessage());
            error_log("Router ERROR: Stack trace: " . $e->getTraceAsString());
            die("Error: " . $e->getMessage());
        }
    }
}
