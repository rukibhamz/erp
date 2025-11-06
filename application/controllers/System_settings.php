<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class System_settings extends Base_Controller {
    private $settingsModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
        $this->settingsModel = $this->loadModel('Settings_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $tab = $_GET['tab'] ?? 'company';
        
        // Get current settings from database
        try {
            $prefix = $this->db->getPrefix();
            $settingsResult = $this->db->fetchAll(
                "SELECT setting_key, setting_value FROM `{$prefix}settings`"
            );
            $settings = [];
            foreach ($settingsResult as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log('System_settings index error: ' . $e->getMessage());
            $settings = [];
        }
        
        $data = [
            'page_title' => 'System Settings',
            'active_tab' => $tab,
            'settings' => $settings,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/system', $data);
    }
    
    public function save() {
        $this->requirePermission('settings', 'update');
        check_csrf(); // Validate CSRF token
        
        $tab = sanitize_input($_POST['tab'] ?? 'company');
        $prefix = $this->db->getPrefix();
        $saved = 0;
        
        try {
            switch ($tab) {
                case 'company':
                    $settings = [
                        'company_name' => sanitize_input($_POST['company_name'] ?? ''),
                        'company_address' => sanitize_input($_POST['company_address'] ?? ''),
                        'company_phone' => sanitize_input($_POST['company_phone'] ?? ''),
                        'company_email' => sanitize_input($_POST['company_email'] ?? ''),
                        'company_website' => sanitize_input($_POST['company_website'] ?? ''),
                        'tax_id' => sanitize_input($_POST['tax_id'] ?? ''),
                        'brand_color' => sanitize_input($_POST['brand_color'] ?? '#000000'),
                    ];
                    
                    // Handle logo upload
                    if (!empty($_FILES['company_logo']['name'])) {
                        $uploadDir = BASEPATH . '../uploads/company/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $validation = validateFileUpload($_FILES['company_logo'], ['image/jpeg', 'image/png', 'image/gif']);
                        if ($validation['valid']) {
                            $extension = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
                            $filename = 'logo_' . time() . '.' . $extension;
                            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $uploadDir . $filename)) {
                                $settings['company_logo'] = $filename;
                            }
                        }
                    }
                    break;
                    
                case 'email':
                    $settings = [
                        'smtp_host' => sanitize_input($_POST['smtp_host'] ?? ''),
                        'smtp_port' => intval($_POST['smtp_port'] ?? 587),
                        'smtp_username' => sanitize_input($_POST['smtp_username'] ?? ''),
                        'smtp_encryption' => sanitize_input($_POST['smtp_encryption'] ?? 'tls'),
                        'from_email' => sanitize_input($_POST['from_email'] ?? ''),
                        'from_name' => sanitize_input($_POST['from_name'] ?? ''),
                    ];
                    
                    // Only update password if provided
                    if (!empty($_POST['smtp_password'])) {
                        $settings['smtp_password'] = sanitize_input($_POST['smtp_password']);
                    }
                    break;
                    
                case 'sms':
                    $settings = [
                        'sms_gateway' => sanitize_input($_POST['sms_gateway'] ?? ''),
                        'sms_sender_id' => sanitize_input($_POST['sms_sender_id'] ?? ''),
                        'sms_api_key' => sanitize_input($_POST['sms_api_key'] ?? ''),
                        'sms_api_endpoint' => sanitize_input($_POST['sms_api_endpoint'] ?? ''),
                    ];
                    
                    if (!empty($_POST['sms_api_secret'])) {
                        $settings['sms_api_secret'] = sanitize_input($_POST['sms_api_secret']);
                    }
                    break;
                    
                case 'preferences':
                    $sessionTimeout = intval($_POST['session_timeout'] ?? 60);
                    $settings = [
                        'timezone' => sanitize_input($_POST['timezone'] ?? 'Africa/Lagos'),
                        'date_format' => sanitize_input($_POST['date_format'] ?? 'Y-m-d'),
                        'time_format' => sanitize_input($_POST['time_format'] ?? 'H:i:s'),
                        'currency_code' => sanitize_input($_POST['currency_code'] ?? 'NGN'),
                        'items_per_page' => intval($_POST['items_per_page'] ?? 25),
                        'session_timeout' => $sessionTimeout * 60, // Convert to seconds
                        'default_dashboard' => sanitize_input($_POST['default_dashboard'] ?? 'super_admin'),
                        'password_policy' => sanitize_input($_POST['password_policy'] ?? 'basic'),
                        'maintenance_mode' => !empty($_POST['maintenance_mode']) ? 1 : 0,
                    ];
                    break;
            }
            
            // Save each setting
            foreach ($settings as $key => $value) {
                $existing = $this->db->fetchOne(
                    "SELECT id FROM `{$prefix}settings` WHERE setting_key = ?",
                    [$key]
                );
                
                if ($existing) {
                    $this->db->update(
                        'settings',
                        ['setting_value' => $value],
                        "setting_key = ?",
                        [$key]
                    );
                } else {
                    $this->db->insert('settings', [
                        'setting_key' => $key,
                        'setting_value' => $value
                    ]);
                }
                $saved++;
            }
            
            if ($saved > 0) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Settings', "Updated {$tab} settings");
                $this->setFlashMessage('success', 'Settings saved successfully.');
            }
        } catch (Exception $e) {
            error_log('System_settings save error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error saving settings: ' . $e->getMessage());
        }
        
        redirect('settings/system?tab=' . $tab);
    }
    
    public function testEmail() {
        $this->requirePermission('settings', 'read');
        header('Content-Type: application/json');
        
        // TODO: Implement email test
        echo json_encode(['success' => false, 'message' => 'Email test not yet implemented']);
        exit;
    }
    
    public function testSMS() {
        $this->requirePermission('settings', 'read');
        header('Content-Type: application/json');
        
        // TODO: Implement SMS test
        echo json_encode(['success' => false, 'message' => 'SMS test not yet implemented']);
        exit;
    }
}


