<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Loader {
    
    public function model($model) {
        $model = ucfirst($model);
        $modelFile = BASEPATH . 'models/' . $model . '.php';
        
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        }
        
        return null;
    }
    
    public function view($view, $data = []) {
        extract($data);
        $viewFile = BASEPATH . 'views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            error_log("View file not found: {$viewFile}");
            die("View {$view} not found. Path: {$viewFile}");
        }
    }
    
    public function helper($helper) {
        $helperFile = BASEPATH . 'helpers/' . $helper . '_helper.php';
        
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }
    
    public function library($library) {
        $library = ucfirst($library);
        $libraryFile = BASEPATH . 'libraries/' . $library . '.php';
        
        if (file_exists($libraryFile)) {
            require_once $libraryFile;
            return new $library();
        }
        
        return null;
    }
}

