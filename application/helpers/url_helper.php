<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function base_url($path = '') {
    $config = require BASEPATH . 'config/config.php';
    $baseUrl = rtrim($config['base_url'], '/');
    $path = ltrim($path, '/');
    return $baseUrl . '/' . $path;
}

function site_url($path = '') {
    return base_url($path);
}

function redirect($url) {
    header('Location: ' . base_url($url));
    exit;
}

function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

