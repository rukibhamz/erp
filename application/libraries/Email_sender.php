<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email Sender Library
 * Uses PHPMailer for sending emails with SMTP
 * 
 * IMPORTANT: Configure SMTP settings on lines 18-24
 */
class Email_sender {
    private $mail;
    private $config;
    private $usePhpMailer = false;
    private $debugMode = false;
    
    public function __construct($debugMode = false) {
        $this->debugMode = $debugMode;
        $this->loadConfig();
        
        if (file_exists(BASEPATH . '../vendor/autoload.php')) {
            require_once BASEPATH . '../vendor/autoload.php';
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $this->usePhpMailer = true;
                try {
                    $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $this->configure();
                } catch (Exception $e) {
                    error_log('PHPMailer initialization error: ' . $e->getMessage());
                    $this->usePhpMailer = false;
                }
            }
        }
    }
    
    /**
     * Load email configuration from database
     * Falls back to config file if database not available
     */
    private function loadConfig() {
        $emailSettings = [];
        
        // Try to load from database first
        try {
            if (class_exists('Database')) {
                $db = Database::getInstance();
                if ($db && $db->getConnection()) {
                    $prefix = $db->getPrefix();
                    $settingsResult = $db->fetchAll(
                        "SELECT setting_key, setting_value FROM `{$prefix}settings` 
                         WHERE setting_key IN (?, ?, ?, ?, ?, ?, ?)",
                        ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'from_email', 'from_name']
                    );
                    
                    if ($settingsResult && is_array($settingsResult)) {
                        foreach ($settingsResult as $row) {
                            if (isset($row['setting_key']) && isset($row['setting_value'])) {
                                $emailSettings[$row['setting_key']] = $row['setting_value'];
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Email_sender loadConfig database error: ' . $e->getMessage());
        } catch (Error $e) {
            error_log('Email_sender loadConfig database fatal error: ' . $e->getMessage());
        }
        
        // Fallback to config file if database settings not found
        if (empty($emailSettings['smtp_host'])) {
            $configFile = BASEPATH . 'config/config.installed.php';
            if (!file_exists($configFile)) {
                $configFile = BASEPATH . 'config/config.php';
            }
            
            if (file_exists($configFile)) {
                $config = require $configFile;
                $emailConfig = $config['email'] ?? [];
                
                if (!empty($emailConfig)) {
                    $emailSettings = array_merge($emailSettings, [
                        'smtp_host' => $emailConfig['smtp_host'] ?? '',
                        'smtp_port' => $emailConfig['smtp_port'] ?? 587,
                        'smtp_username' => $emailConfig['smtp_username'] ?? $emailConfig['smtp_user'] ?? '',
                        'smtp_password' => $emailConfig['smtp_password'] ?? $emailConfig['smtp_pass'] ?? '',
                        'from_email' => $emailConfig['from_email'] ?? '',
                        'from_name' => $emailConfig['from_name'] ?? 'Invoice System',
                        'smtp_encryption' => $emailConfig['smtp_encryption'] ?? 'tls'
                    ]);
                }
            }
        }
        
        // Set configuration with database values or defaults
        $this->config = [
            'smtp_host' => $emailSettings['smtp_host'] ?? 'smtp.gmail.com',
            'smtp_port' => intval($emailSettings['smtp_port'] ?? 587),
            'smtp_user' => $emailSettings['smtp_username'] ?? '',
            'smtp_pass' => $emailSettings['smtp_password'] ?? '',
            'from_email' => $emailSettings['from_email'] ?? '',
            'from_name' => $emailSettings['from_name'] ?? 'Invoice System',
            'encryption' => $emailSettings['smtp_encryption'] ?? 'tls'
        ];
    }
    
    /**
     * Configure PHPMailer with SMTP settings
     */
    private function configure() {
        if (!$this->usePhpMailer) return;
        
        try {
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['smtp_user'];
            $this->mail->Password = $this->config['smtp_pass'];
            
            // Set encryption based on config
            $encryption = strtolower($this->config['encryption'] ?? 'tls');
            if ($encryption === 'ssl') {
                $this->mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $this->mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $this->mail->SMTPSecure = '';
            }
            
            $this->mail->Port = $this->config['smtp_port'];
            
            // IMPORTANT: For Gmail, the "From" email must match the authenticated username
            // Use SMTP username as From email if not set, or if they don't match
            $fromEmail = $this->config['from_email'];
            if (empty($fromEmail) || (strpos($this->config['smtp_host'], 'gmail') !== false && $fromEmail !== $this->config['smtp_user'])) {
                $fromEmail = $this->config['smtp_user'];
                error_log('Email_sender: Using SMTP username as From email for Gmail compatibility');
            }
            
            $this->mail->setFrom($fromEmail, $this->config['from_name']);
            $this->mail->CharSet = 'UTF-8';
            $this->mail->SMTPDebug = $this->debugMode ? 2 : 0; // Enable debug if requested
            $this->mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug (Level {$level}): {$str}");
            };
            
            // Additional Gmail-specific settings
            if (strpos($this->config['smtp_host'], 'gmail') !== false) {
                $this->mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
            }
        } catch (Exception $e) {
            error_log('Email config error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send generic email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML email body
     * @param array $attachments Array of ['path' => '/path/to/file', 'name' => 'filename.ext']
     * @return array ['success' => bool, 'error' => string]
     */
    public function sendEmail($to, $subject, $body, $attachments = []) {
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        
        if (empty($this->config['smtp_user'])) {
            return ['success' => false, 'error' => 'Email not configured. Please configure SMTP settings in System Settings > Email Configuration'];
        }
        
        // Use PHP mail fallback if configured or PHPMailer not available
        if (!$this->usePhpMailer) {
            // Convert single attachment format from sendInvoice if needed
            $pdfPath = null;
            $pdfName = null;
            if (!empty($attachments) && isset($attachments[0]['path'])) {
                $pdfPath = $attachments[0]['path'];
                $pdfName = $attachments[0]['name'] ?? basename($pdfPath);
            }
            return $this->sendWithPhpMail($to, $subject, $body, $pdfPath, $pdfName);
        }
        
        try {
            // Reconfigure for each email to ensure fresh state
            $this->configure();
            
            // Reset for new email
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            $this->mail->clearReplyTos();
            $this->mail->clearCustomHeaders();
            $this->mail->clearAllRecipients();
            
            // Add recipient
            $this->mail->addAddress($to);
            
            // Set content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);
            
            // Add attachments
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $this->mail->addAttachment($attachment['path'], $attachment['name'] ?? basename($attachment['path']));
                }
            }
            
            // Enable verbose error reporting if debug mode
            $this->mail->SMTPDebug = $this->debugMode ? 2 : 0;
            
            // Attempt to send
            $sent = $this->mail->send();
            
            // Check for errors even if send() returned true
            if (!$sent || !empty($this->mail->ErrorInfo)) {
                $errorMsg = $this->mail->ErrorInfo ?? 'Email send failed but no error message provided';
                error_log('PHPMailer send failed: ' . $errorMsg);
                return ['success' => false, 'error' => $errorMsg];
            }
            
            // Log successful send
            error_log('Email sent successfully to: ' . $to . ' via ' . $this->config['smtp_host']);
            
            return ['success' => true, 'error' => null];
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $errorMsg = $this->mail->ErrorInfo ?? $e->getMessage();
            error_log('PHPMailer exception: ' . $errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log('Email send exception: ' . $errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }
    }

    /**
     * Send invoice email with PDF attachment
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML email body
     * @param string $pdfPath Path to PDF file (optional)
     * @param string $pdfName PDF filename for attachment (optional)
     * @return array ['success' => bool, 'error' => string]
     */
    public function sendInvoice($to, $subject, $body, $pdfPath = null, $pdfName = null) {
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address'];
        }
        
        if (empty($this->config['smtp_user'])) {
            return ['success' => false, 'error' => 'Email not configured. Please configure SMTP settings in System Settings > Email Configuration'];
        }
        
        if (!$this->usePhpMailer) {
            return $this->sendWithPhpMail($to, $subject, $body, $pdfPath, $pdfName);
        }
        
        try {
            // Reconfigure for each email to ensure fresh state
            $this->configure();
            
            // Reset for new email
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            $this->mail->clearReplyTos();
            $this->mail->clearCustomHeaders();
            $this->mail->clearAllRecipients();
            
            // Add recipient
            $this->mail->addAddress($to);
            
            // Set content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);
            
            // Add attachment if provided
            if ($pdfPath && file_exists($pdfPath)) {
                $this->mail->addAttachment($pdfPath, $pdfName ?? 'invoice.pdf');
            }
            
            // Enable verbose error reporting if debug mode
            $this->mail->SMTPDebug = $this->debugMode ? 2 : 0;
            
            // Attempt to send
            $sent = $this->mail->send();
            
            // Check for errors even if send() returned true
            if (!$sent || !empty($this->mail->ErrorInfo)) {
                $errorMsg = $this->mail->ErrorInfo ?? 'Email send failed but no error message provided';
                error_log('PHPMailer send failed: ' . $errorMsg);
                error_log('PHPMailer details - To: ' . $to . ', From: ' . $this->config['from_email']);
                return ['success' => false, 'error' => $errorMsg];
            }
            
            // Log successful send
            error_log('Email sent successfully to: ' . $to . ' via ' . $this->config['smtp_host']);
            
            return ['success' => true, 'error' => null];
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $errorMsg = $this->mail->ErrorInfo ?? $e->getMessage();
            error_log('PHPMailer exception: ' . $errorMsg);
            error_log('PHPMailer exception details: ' . $e->getTraceAsString());
            return ['success' => false, 'error' => $errorMsg];
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log('Email send exception: ' . $errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }
    }
    
    /**
     * Fallback email sending using PHP mail() function
     */
    private function sendWithPhpMail($to, $subject, $body, $pdfPath, $pdfName) {
        try {
            $boundary = md5(time());
            $headers = "From: " . $this->config['from_name'] . " <" . $this->config['from_email'] . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
            
            $message = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $body . "\r\n";
            
            if ($pdfPath && file_exists($pdfPath)) {
                $fileContent = chunk_split(base64_encode(file_get_contents($pdfPath)));
                $message .= "--{$boundary}\r\n";
                $message .= "Content-Type: application/pdf; name=\"{$pdfName}\"\r\n";
                $message .= "Content-Disposition: attachment; filename=\"{$pdfName}\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $message .= $fileContent . "\r\n";
            }
            
            $message .= "--{$boundary}--";
            
            if (mail($to, $subject, $message, $headers)) {
                return ['success' => true, 'error' => null];
            }
            throw new Exception('PHP mail() failed');
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get current email configuration (for debugging)
     */
    public function getConfig() {
        return [
            'smtp_host' => $this->config['smtp_host'],
            'smtp_port' => $this->config['smtp_port'],
            'smtp_encryption' => $this->config['encryption'],
            'from_email' => $this->config['from_email'],
            'from_name' => $this->config['from_name'],
            'using_phpmailer' => $this->usePhpMailer,
            'smtp_user_set' => !empty($this->config['smtp_user']),
            'smtp_pass_set' => !empty($this->config['smtp_pass'])
        ];
    }
}
