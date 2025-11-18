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
            $regexPattern = preg_quote($pattern, '#');
            $regexPattern = str_replace('\\(:num\\)', '([0-9]+)', $regexPattern);
            $regexPattern = str_replace('\\(:any\\)', '(.+)', $regexPattern);
            $regex = '#^' . $regexPattern . '$#i'; // Added 'i' flag for case-insensitive matching
            
            // Try matching against both original path and lowercase path
            if (preg_match($regex, $path, $matches) || preg_match($regex, strtolower($path), $matches)) {
                array_shift($matches); // Remove full match
                
                $routeParts = explode('/', $route);
                $this->controller = $routeParts[0];
                $this->method = $routeParts[1] ?? 'index';
                
                // Extract parameters from route string ($1, $2, etc.)
                $params = [];
                foreach ($routeParts as $part) {
                    if (preg_match('#\$(\d+)#', $part, $paramMatch)) {
                        $paramIndex = intval($paramMatch[1]) - 1;
                        if (isset($matches[$paramIndex])) {
                            $params[] = $matches[$paramIndex];
                        }
                    }
                }
                // Add any remaining matches
                $params = array_merge($params, array_slice($matches, count($params)));
                $this->params = $params;
                return;
            }
        }
        
        // No route match, use direct controller/method parsing
        // Handle underscore controllers (e.g., tax_compliance -> Tax_compliance)
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
        }
    }
    
    public function dispatch() {
        // Handle underscore controllers (e.g., Tax_compliance)
        $controllerName = $this->controller;
        $controllerFile = BASEPATH . 'controllers/' . $controllerName . '.php';
        
        if (!file_exists($controllerFile)) {
            $this->controller = 'Error404';
            $this->method = 'index';
            $controllerFile = BASEPATH . 'controllers/Error404.php';
        }
        
        require_once $controllerFile;
        
        // Try exact match first, then case-insensitive match
        if (!class_exists($controllerName)) {
            // Try case-insensitive class lookup
            $classes = get_declared_classes();
            foreach ($classes as $class) {
                if (strtolower($class) === strtolower($controllerName)) {
                    $controllerName = $class;
                    break;
                }
            }
            
            if (!class_exists($controllerName)) {
                die("Controller {$this->controller} not found.");
            }
        }
        
        // Use the actual class name (may have been corrected by case-insensitive lookup)
        $controller = new $controllerName();
        
        if (!method_exists($controller, $this->method)) {
            die("Method {$this->method} not found in {$controllerName}.");
        }
        
        call_user_func_array([$controller, $this->method], $this->params);
    }
}

