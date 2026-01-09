<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class System_settings extends Base_Controller {
    private $settingsModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->settingsModel = $this->loadModel('Settings_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $this->requirePermission('settings', 'read');
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
                        'portal_return_link' => sanitize_input($_POST['portal_return_link'] ?? 'https://acropolispark.com/'),
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
                    // SECURITY: Validate and sanitize all SMTP settings
                    $smtpHost = sanitize_input($_POST['smtp_host'] ?? '');
                    $smtpPort = intval($_POST['smtp_port'] ?? 587);
                    $smtpUsername = sanitize_input($_POST['smtp_username'] ?? '');
                    $smtpEncryption = sanitize_input($_POST['smtp_encryption'] ?? 'tls');
                    $fromEmail = sanitize_input($_POST['from_email'] ?? '');
                    $fromName = sanitize_input($_POST['from_name'] ?? '');
                    
                    // SECURITY: Validate SMTP host (prevent injection)
                    if (!empty($smtpHost) && !preg_match('/^[a-zA-Z0-9.-]+$/', $smtpHost)) {
                        throw new Exception('Invalid SMTP host format');
                    }
                    
                    // SECURITY: Validate port range
                    if ($smtpPort < 1 || $smtpPort > 65535) {
                        throw new Exception('SMTP port must be between 1 and 65535');
                    }
                    
                    // SECURITY: Validate encryption type
                    if (!in_array($smtpEncryption, ['tls', 'ssl', 'none'])) {
                        $smtpEncryption = 'tls';
                    }
                    
                    // SECURITY: Validate email format
                    if (!empty($fromEmail) && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Invalid from email address');
                    }
                    
                    // SECURITY: Limit field lengths
                    $smtpHost = substr($smtpHost, 0, 255);
                    $smtpUsername = substr($smtpUsername, 0, 255);
                    $fromEmail = substr($fromEmail, 0, 255);
                    $fromName = substr($fromName, 0, 100);
                    
                    $settings = [
                        'smtp_host' => $smtpHost,
                        'smtp_port' => $smtpPort,
                        'smtp_username' => $smtpUsername,
                        'smtp_encryption' => $smtpEncryption,
                        'from_email' => $fromEmail,
                        'from_name' => $fromName,
                    ];
                    
                    // SECURITY: Only update password if provided (and validate length)
                    if (!empty($_POST['smtp_password'])) {
                        $smtpPassword = sanitize_input($_POST['smtp_password']);
                        // SECURITY: Limit password length (reasonable limit)
                        if (strlen($smtpPassword) > 500) {
                            throw new Exception('SMTP password is too long');
                        }
                        $settings['smtp_password'] = $smtpPassword;
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
        // SECURITY: Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        // SECURITY: Validate session exists and user is authenticated
        if (empty($this->session['user_id'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required. Please log in.']);
            exit;
        }
        
        // SECURITY: Only super admin and admin can test email
        $userRole = $this->session['role'] ?? '';
        $userId = $this->session['user_id'] ?? 'unknown';
        
        // Debug: Log role check for troubleshooting
        error_log("testEmail role check - User ID: {$userId}, Role: {$userRole}");
        
        if ($userRole !== 'super_admin' && $userRole !== 'admin') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => 'Access denied. Only administrators (super_admin or admin) can test email. Your current role: ' . ($userRole ?: 'not set') . '. Please contact your system administrator.'
            ]);
            exit;
        }
        
        // SECURITY: Validate CSRF token first (before rate limiting to avoid wasting rate limit on invalid requests)
        // Note: check_csrf() will die with JSON response if validation fails
        check_csrf();
        
        // SECURITY: Rate limiting - max 5 test emails per 15 minutes per user
        require_once BASEPATH . 'helpers/security_helper.php';
        $rateLimitKey = 'test_email_' . $this->session['user_id'];
        try {
            if (!checkRateLimit($rateLimitKey, 5, 900)) {
                header('Content-Type: application/json');
                http_response_code(429);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Too many test email requests. Please wait 15 minutes before trying again.'
                ]);
                exit;
            }
        } catch (Exception $e) {
            // If rate limiting fails (e.g., database error), log but allow the request
            error_log('Rate limiting check failed: ' . $e->getMessage());
            // Continue with the request - fail open for rate limiting to avoid blocking legitimate users
        }
        
        header('Content-Type: application/json');
        
        try {
            // SECURITY: Get SMTP settings from database using parameterized query
            $prefix = $this->db->getPrefix();
            $settingsResult = $this->db->fetchAll(
                "SELECT setting_key, setting_value FROM `{$prefix}settings` 
                 WHERE setting_key IN (?, ?, ?, ?, ?, ?, ?)",
                ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'from_email', 'from_name']
            );
            
            $emailSettings = [];
            foreach ($settingsResult as $row) {
                $emailSettings[$row['setting_key']] = $row['setting_value'];
            }
            
            // SECURITY: Validate required settings exist
            if (empty($emailSettings['smtp_host']) || empty($emailSettings['smtp_username']) || empty($emailSettings['smtp_password'])) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Please configure SMTP Host, Username, and Password before testing.'
                ]);
                exit;
            }
            
            // SECURITY: Validate and sanitize test email address
            $testEmail = sanitize_input($_POST['test_email'] ?? '');
            
            // SECURITY: Limit email length to prevent abuse
            if (strlen($testEmail) > 255) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Email address is too long.'
                ]);
                exit;
            }
            
            if (empty($testEmail)) {
                // Use current user's email as fallback
                $user = $this->loadModel('User_model')->getById($this->session['user_id']);
                if (!$user || empty($user['email'])) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Please provide a test email address or ensure your account has a valid email.'
                    ]);
                    exit;
                }
                $testEmail = $user['email'];
            }
            
            // SECURITY: Strict email validation
            $testEmail = filter_var(trim($testEmail), FILTER_VALIDATE_EMAIL);
            if ($testEmail === false) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Please provide a valid test email address.'
                ]);
                exit;
            }
            
            // SECURITY: Additional email format validation
            if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $testEmail)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid email format.'
                ]);
                exit;
            }
            
            // Prepare email configuration for the helper
            // The email helper reads from config, so we need to temporarily set it
            // Or we can call send_email_smtp directly with the settings
            
            // Use the email helper with database settings
            $subject = 'Test Email - Business ERP System';
            $message = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>✓ Email Test Successful</h2>
        </div>
        <div class='content'>
            <p>Hello,</p>
            <p>This is a test email from your Business ERP System.</p>
            <div class='success'>
                <strong>✓ SMTP Configuration Verified</strong><br>
                Your email settings are working correctly. You can now send emails from the system.
            </div>
            <p><strong>Test Details:</strong></p>
            <ul>
                <li>SMTP Host: " . htmlspecialchars($emailSettings['smtp_host'] ?? '') . "</li>
                <li>SMTP Port: " . htmlspecialchars($emailSettings['smtp_port'] ?? '587') . "</li>
                <li>Encryption: " . htmlspecialchars(strtoupper($emailSettings['smtp_encryption'] ?? 'tls')) . "</li>
                <li>From Email: " . htmlspecialchars($emailSettings['from_email'] ?? '') . "</li>
                <li>From Name: " . htmlspecialchars($emailSettings['from_name'] ?? 'Business ERP System') . "</li>
                <li>Test Time: " . date('Y-m-d H:i:s') . "</li>
            </ul>
            <p>If you received this email, your SMTP configuration is correct and working.</p>
        </div>
    </div>
</body>
</html>";
            
            // SECURITY: Validate SMTP port is within safe range
            $smtpPort = intval($emailSettings['smtp_port'] ?? 587);
            if ($smtpPort < 1 || $smtpPort > 65535) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Invalid SMTP port. Port must be between 1 and 65535.'
                ]);
                exit;
            }
            
            // SECURITY: Validate encryption type
            $smtpEncryption = $emailSettings['smtp_encryption'] ?? 'tls';
            if (!in_array($smtpEncryption, ['tls', 'ssl', 'none'])) {
                $smtpEncryption = 'tls'; // Default to TLS
            }
            
            // SECURITY: Validate from email
            $fromEmail = filter_var($emailSettings['from_email'] ?? '', FILTER_VALIDATE_EMAIL);
            if ($fromEmail === false) {
                $fromEmail = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            }
            
            // SECURITY: Sanitize from name
            $fromName = htmlspecialchars($emailSettings['from_name'] ?? 'Business ERP System', ENT_QUOTES, 'UTF-8');
            if (strlen($fromName) > 100) {
                $fromName = substr($fromName, 0, 100);
            }
            
            // Use Email_sender library - it now reads from database automatically
            require_once BASEPATH . 'libraries/Email_sender.php';
            // Enable debug mode for test emails to help troubleshoot
            $emailSender = new Email_sender(true); // Enable debug mode
            
            // Send test email using Email_sender library
            $result = $emailSender->sendInvoice(
                $testEmail,
                $subject,
                $message
            );
            
            if ($result['success']) {
                // SECURITY: Log activity without exposing sensitive data
                $this->activityModel->log(
                    $this->session['user_id'], 
                    'test_email', 
                    'Settings', 
                    "Test email sent successfully"
                );
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Test email sent successfully. Please check your inbox."
                ]);
            } else {
                // Provide helpful error message
                $errorMsg = $result['error'] ?? 'Unknown error';
                
                // SECURITY: Don't expose detailed error information, but provide helpful hints
                $message = 'Failed to send test email. ';
                if (strpos($errorMsg, 'not configured') !== false) {
                    $message .= 'Please configure SMTP settings in System Settings > Email Configuration.';
                } elseif (strpos($errorMsg, 'Connection') !== false || strpos($errorMsg, 'timeout') !== false) {
                    $message .= 'Please verify your SMTP host and port settings.';
                } elseif (strpos($errorMsg, 'authentication') !== false || strpos($errorMsg, 'login') !== false || strpos($errorMsg, '535') !== false) {
                    $message .= 'Please verify your SMTP username and password. For Gmail, use an App Password (not your regular password).';
                } else {
                    $message .= 'Error: ' . htmlspecialchars($errorMsg);
                }
                
                echo json_encode([
                    'success' => false, 
                    'message' => $message
                ]);
            }
        } catch (Exception $e) {
            // SECURITY: Log full error but don't expose to user
            $errorMsg = $e->getMessage();
            $userId = $this->session['user_id'] ?? 'unknown';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            error_log("System_settings testEmail error [User: {$userId}, IP: {$ipAddress}]: " . $errorMsg);
            
            // SECURITY: Generic error message to prevent information disclosure
            // But provide slightly more helpful message for common issues
            $message = 'An error occurred while sending the test email. ';
            if (strpos($errorMsg, 'Connection') !== false || strpos($errorMsg, 'timeout') !== false) {
                $message .= 'Please verify your SMTP host and port settings.';
            } elseif (strpos($errorMsg, 'authentication') !== false || strpos($errorMsg, 'login') !== false) {
                $message .= 'Please verify your SMTP username and password.';
            } else {
                $message .= 'Please check your configuration and try again.';
            }
            
            echo json_encode([
                'success' => false, 
                'message' => $message
            ]);
        }
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


