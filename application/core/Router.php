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
        $url = $_GET['url'] ?? '';
        $url = rtrim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $url = explode('/', $url);
        
        // Load routes
        $routes = require BASEPATH . 'config/routes.php';
        
        // Check routes
        $path = implode('/', $url);
        if (isset($routes[$path])) {
            $path = $routes[$path];
            $url = explode('/', $path);
        }
        
        // Set controller
        if (isset($url[0]) && !empty($url[0])) {
            $this->controller = ucfirst($url[0]);
        }
        
        // Set method
        if (isset($url[1]) && !empty($url[1])) {
            $this->method = $url[1];
        }
        
        // Set params
        if (count($url) > 2) {
            $this->params = array_slice($url, 2);
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

