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
            // CRITICAL FIX: Properly escape and convert route patterns
            // First, replace parameter placeholders with regex groups (before escaping)
            $regexPattern = $pattern;
            $regexPattern = str_replace('(:num)', '([0-9]+)', $regexPattern);
            $regexPattern = str_replace('(:any)', '(.+)', $regexPattern);
            // Now escape remaining special regex characters (but not the groups we just added)
            $regexPattern = preg_quote($regexPattern, '#');
            // Unescape the regex groups we added (they need to remain as groups)
            $regexPattern = str_replace('\\(\[0-9\]\+\)', '([0-9]+)', $regexPattern);
            $regexPattern = str_replace('\\(\.\+\)', '(.+)', $regexPattern);
            // Unescape forward slashes and hyphens (they're safe in our context)
            $regexPattern = str_replace('\\/', '/', $regexPattern);
            $regexPattern = str_replace('\\-', '-', $regexPattern);
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
            $this->controller = 'Receivables';
            
            if (count($urlParts) >= 3) {
                // Handle method names like "editCustomer", "viewCustomer", "createInvoice"
                $methodPart = $urlParts[1] ?? '';
                $actionPart = $urlParts[2] ?? '';
                
                // Map common patterns: customers/edit -> editCustomer, invoices/view -> viewInvoice
                if ($methodPart === 'customers' && $actionPart === 'edit') {
                    $this->method = 'editCustomer';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'customers' && $actionPart === 'view') {
                    $this->method = 'viewCustomer';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'customers' && $actionPart === 'create') {
                    $this->method = 'createCustomer';
                    $this->params = [];
                } elseif ($methodPart === 'invoices' && $actionPart === 'edit') {
                    $this->method = 'editInvoice';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'invoices' && $actionPart === 'view') {
                    $this->method = 'viewInvoice';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'invoices' && $actionPart === 'create') {
                    $this->method = 'createInvoice';
                    $this->params = [];
                } elseif ($methodPart === 'invoices' && $actionPart === 'payment') {
                    $this->method = 'recordPayment';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'payments' && $actionPart === 'create') {
                    $this->method = 'createPayment';
                    $this->params = [];
                } else {
                    // Generic mapping: receivables/method/param
                    $this->method = $actionPart ?: ($methodPart ?: 'customers');
                    $this->params = count($urlParts) > 3 ? array_slice($urlParts, 3) : [];
                }
            } elseif (count($urlParts) === 2) {
                // receivables/customers or receivables/invoices
                $this->method = $urlParts[1] ?? 'customers';
                $this->params = [];
            } else {
                // receivables only
                $this->method = 'customers';
                $this->params = [];
            }
            
            // Convert numeric parameters to integers
            $this->params = array_map(function($param) {
                return is_string($param) && preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
            }, $this->params);
            error_log("Router: Receivables URL parsed -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
            return;
        }
        
        if ($firstPart === 'cash') {
            // Cash module - map directly to Cash controller
            $this->controller = 'Cash';
            
            if (count($urlParts) >= 3) {
                // Handle method names like "editAccount", "createAccount", "deleteAccount"
                $methodPart = $urlParts[1] ?? '';
                $actionPart = $urlParts[2] ?? '';
                
                // Map common patterns: accounts/edit -> editAccount, accounts/create -> createAccount
                if ($methodPart === 'accounts' && $actionPart === 'edit') {
                    $this->method = 'editAccount';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'accounts' && $actionPart === 'create') {
                    $this->method = 'createAccount';
                    $this->params = [];
                } elseif ($methodPart === 'accounts' && $actionPart === 'delete') {
                    $this->method = 'deleteAccount';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } else {
                    // Generic mapping: cash/method/param
                    $this->method = $actionPart ?: ($methodPart ?: 'index');
                    $this->params = count($urlParts) > 3 ? array_slice($urlParts, 3) : [];
                }
            } elseif (count($urlParts) === 2) {
                // cash/accounts or cash/receipts or cash/payments
                $this->method = $urlParts[1] ?? 'index';
                $this->params = [];
            } else {
                // cash only
                $this->method = 'index';
                $this->params = [];
            }
            
            // Convert numeric parameters to integers
            $this->params = array_map(function($param) {
                return is_string($param) && preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
            }, $this->params);
            error_log("Router: Cash URL parsed -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
            return;
        }
        
        if ($firstPart === 'payables') {
            // Payables module - map directly to Payables controller
            $this->controller = 'Payables';
            
            if (count($urlParts) >= 3) {
                // Handle method names like "editVendor", "viewBill", "createBill"
                $methodPart = $urlParts[1] ?? '';
                $actionPart = $urlParts[2] ?? '';
                
                // Map common patterns: vendors/edit -> editVendor, bills/view -> viewBill
                if ($methodPart === 'vendors' && $actionPart === 'edit') {
                    $this->method = 'editVendor';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'vendors' && $actionPart === 'delete') {
                    $this->method = 'deleteVendor';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'vendors' && $actionPart === 'create') {
                    $this->method = 'createVendor';
                    $this->params = [];
                } elseif ($methodPart === 'bills' && $actionPart === 'edit') {
                    $this->method = 'editBill';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'bills' && $actionPart === 'view') {
                    $this->method = 'viewBill';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } elseif ($methodPart === 'bills' && $actionPart === 'create') {
                    $this->method = 'createBill';
                    $this->params = [];
                } elseif ($methodPart === 'bills' && $actionPart === 'delete') {
                    $this->method = 'deleteBill';
                    $this->params = count($urlParts) > 3 ? [intval($urlParts[3])] : [];
                } else {
                    // Generic mapping: payables/method/param
                    $this->method = $actionPart ?: ($methodPart ?: 'vendors');
                    $this->params = count($urlParts) > 3 ? array_slice($urlParts, 3) : [];
                }
            } elseif (count($urlParts) === 2) {
                // payables/vendors or payables/bills
                $this->method = $urlParts[1] ?? 'vendors';
                $this->params = [];
            } else {
                // payables only
                $this->method = 'vendors';
                $this->params = [];
            }
            
            // Convert numeric parameters to integers
            $this->params = array_map(function($param) {
                return is_string($param) && preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
            }, $this->params);
            error_log("Router: Payables URL parsed -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
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
                    $this->params = array_slice($urlParts, 2);
                    $this->params = array_map(function($param) {
                        return preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
                    }, $this->params);
                }
                
                error_log("Router: Locations booking route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
                return;
            }
            
            // Handle bookings route
            if ($method === 'bookings') {
                $this->controller = 'Locations';
                $this->method = 'bookings';
                if (count($urlParts) > 2) {
                    $this->params = array_slice($urlParts, 2);
                    $this->params = array_map(function($param) {
                        return preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
                    }, $this->params);
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
                    $this->params = array_slice($urlParts, 2);
                    $this->params = array_map(function($param) {
                        return preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
                    }, $this->params);
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
                    $this->params = array_slice($urlParts, 2);
                    $this->params = array_map(function($param) {
                        return preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
                    }, $this->params);
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
                $this->params = array_slice($urlParts, 2);
                $this->params = array_map(function($param) {
                    return preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
                }, $this->params);
            }
            error_log("Router: Space_bookings route matched -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
            return;
        }
        
        // Check if this looks like a module/controller/method/param pattern
        if (count($urlParts) >= 3) {
            // Common module prefixes that should be stripped (removed 'locations' since it's handled above)
            $modulePrefixes = ['inventory', 'utilities', 'accounting', 'tax', 'bookings', 'cash'];
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
                    $this->params = array_slice($urlParts, 3);
                    // Convert numeric parameters to integers
                    $this->params = array_map(function($param) {
                        return preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
                    }, $this->params);
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
                $this->params = array_slice($urlParts, 2);
                $this->params = array_map(function($param) {
                    return preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
                }, $this->params);
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
            $this->params = array_slice($urlParts, 2);
            // Convert numeric parameters to integers for type safety
            $this->params = array_map(function($param) {
                return preg_match('/^[0-9]+$/', $param) ? intval($param) : $param;
            }, $this->params);
        }
        
        // Log fallback parsing result
        error_log("Router: Fallback parsing -> Controller: {$this->controller}, Method: {$this->method}, Params: " . json_encode($this->params));
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

