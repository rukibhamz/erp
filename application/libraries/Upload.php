<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custom Upload Library
 * Mimics CodeIgniter Upload library functionality for compatibility
 */
class Upload {
    private $config = [];
    private $error = '';
    private $data = [];

    public function __construct($config = []) {
        if (!empty($config)) {
            $this->initialize($config);
        }
    }

    public function initialize($config = []) {
        $defaults = [
            'upload_path' => './uploads/',
            'allowed_types' => '*',
            'max_size' => 0,
            'encrypt_name' => FALSE,
            'overwrite' => FALSE
        ];

        foreach ($defaults as $key => $val) {
            $this->config[$key] = isset($config[$key]) ? $config[$key] : $val;
        }

        // Clean up path
        $this->config['upload_path'] = rtrim($this->config['upload_path'], '/') . '/';
        
        // Ensure directory exists
        if (!is_dir($this->config['upload_path'])) {
            @mkdir($this->config['upload_path'], 0755, true);
        }

        return $this;
    }

    public function do_upload($field = 'userfile') {
        if (!isset($_FILES[$field])) {
            $this->error = 'No file was uploaded.';
            return FALSE;
        }

        $file = $_FILES[$field];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = $this->get_php_upload_error($file['error']);
            return FALSE;
        }

        // Check types
        if ($this->config['allowed_types'] !== '*') {
            $types = explode('|', $this->config['allowed_types']);
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $types)) {
                $this->error = 'Invalid file type.';
                return FALSE;
            }
        }

        // Check size
        if ($this->config['max_size'] > 0 && ($file['size'] / 1024) > $this->config['max_size']) {
            $this->error = 'File is too large.';
            return FALSE;
        }

        $fileName = $file['name'];
        if ($this->config['encrypt_name']) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = md5(uniqid(rand(), true)) . '.' . $ext;
        }

        $targetFile = $this->config['upload_path'] . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $this->data = [
                'file_name' => $fileName,
                'full_path' => realpath($targetFile),
                'file_ext' => '.' . pathinfo($fileName, PATHINFO_EXTENSION),
                'file_size' => $file['size'] / 1024,
                'is_image' => $this->is_image($targetFile)
            ];
            return TRUE;
        }

        $this->error = 'Failed to move uploaded file.';
        return FALSE;
    }

    public function data() {
        return $this->data;
    }

    public function display_errors($prefix = '', $suffix = '') {
        return $prefix . $this->error . $suffix;
    }

    private function get_php_upload_error($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE: return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE: return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL: return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE: return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR: return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE: return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION: return 'A PHP extension stopped the file upload.';
            default: return 'Unknown upload error.';
        }
    }

    private function is_image($path) {
        $a = getimagesize($path);
        return ($a !== FALSE);
    }
}
