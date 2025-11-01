<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Autoloader {
    
    public function load($class) {
        $paths = [
            BASEPATH . 'controllers/' . $class . '.php',
            BASEPATH . 'models/' . $class . '.php',
            BASEPATH . 'core/' . $class . '.php',
            BASEPATH . 'helpers/' . $class . '.php',
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return true;
            }
        }
        
        return false;
    }
}

