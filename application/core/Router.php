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
        // Get URL from query string
        $url = $_GET['url'] ?? '';
        $url = trim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Load routes
        $routes = require BASEPATH . 'config/routes.php';
        
        // Ensure routes is an array
        if (!is_array($routes)) {
            $routes = [];
        }
        
        // If URL is empty, use default controller
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
        $path = $url;
        
        // Check exact route matches first (case-insensitive)
        $pathLower = strtolower($path);
        foreach ($routes as $pattern => $route) {
            if ($pattern === 'default_controller' || $pattern === '404_override') {
                continue;
            }
            if (strtolower($pattern) === $pathLower) {
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
        foreach ($routes as $pattern => $route) {
            if ($pattern === 'default_controller' || $pattern === '404_override') {
                continue;
            }
            
            // Skip exact matches (already checked)
            if (strpos($pattern, '(') === false) {
                continue;
            }
            
            // Convert route pattern to regex
            $regexPattern = preg_quote($pattern, '#');
            $regexPattern = str_replace('\\(:num\\)', '([0-9]+)', $regexPattern);
            $regexPattern = str_replace('\\(:any\\)', '(.+)', $regexPattern);
            $regex = '#^' . $regexPattern . '$#';
            
            if (preg_match($regex, $path, $matches)) {
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
        if (isset($urlParts[0]) && !empty($urlParts[0])) {
            $this->controller = ucfirst($urlParts[0]);
        }
        
        if (isset($urlParts[1]) && !empty($urlParts[1])) {
            $this->method = $urlParts[1];
        }
        
        if (count($urlParts) > 2) {
            $this->params = array_slice($urlParts, 2);
        }
    }
    
    public function dispatch() {
        $controllerFile = BASEPATH . 'controllers/' . $this->controller . '.php';
        
        if (!file_exists($controllerFile)) {
            $this->controller = 'Error404';
            $this->method = 'index';
            $controllerFile = BASEPATH . 'controllers/Error404.php';
        }
        
        require_once $controllerFile;
        
        if (!class_exists($this->controller)) {
            die("Controller {$this->controller} not found.");
        }
        
        $controller = new $this->controller();
        
        if (!method_exists($controller, $this->method)) {
            die("Method {$this->method} not found in {$this->controller}.");
        }
        
        call_user_func_array([$controller, $this->method], $this->params);
    }
}

