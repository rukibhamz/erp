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
    
    public function __construct() {
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
     * Load email configuration
     * 
     * CONFIGURE THESE SETTINGS BELOW:
     * For Gmail: Generate App Password at https://myaccount.google.com/apppasswords
     */
    private function loadConfig() {
        // Try to load from config file first
        $configFile = BASEPATH . 'config/config.installed.php';
        if (!file_exists($configFile)) {
            $configFile = BASEPATH . 'config/config.php';
        }
        
        $emailConfig = [];
        if (file_exists($configFile)) {
            $config = require $configFile;
            $emailConfig = $config['email'] ?? [];
        }
        
        // CONFIGURE THESE SETTINGS - Update with your SMTP credentials
        $this->config = [
            'smtp_host' => $emailConfig['smtp_host'] ?? 'smtp.gmail.com',
            'smtp_port' => $emailConfig['smtp_port'] ?? 587,
            'smtp_user' => $emailConfig['smtp_username'] ?? $emailConfig['smtp_user'] ?? '', // YOUR GMAIL HERE
            'smtp_pass' => $emailConfig['smtp_password'] ?? $emailConfig['smtp_pass'] ?? '', // YOUR APP PASSWORD HERE
            'from_email' => $emailConfig['from_email'] ?? '', // YOUR EMAIL HERE
            'from_name' => $emailConfig['from_name'] ?? 'Invoice System',
            'encryption' => $emailConfig['smtp_encryption'] ?? 'tls'
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
            $this->mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = $this->config['smtp_port'];
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mail->CharSet = 'UTF-8';
            $this->mail->SMTPDebug = 0; // Set to 2 for debugging
        } catch (Exception $e) {
            error_log('Email config error: ' . $e->getMessage());
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
            return ['success' => false, 'error' => 'Email not configured. Update Email_sender.php line 18-24 or configure in config file'];
        }
        
        if (!$this->usePhpMailer) {
            return $this->sendWithPhpMail($to, $subject, $body, $pdfPath, $pdfName);
        }
        
        try {
            // Reset for new email
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);
            
            if ($pdfPath && file_exists($pdfPath)) {
                $this->mail->addAttachment($pdfPath, $pdfName ?? 'invoice.pdf');
            }
            
            $this->mail->send();
            return ['success' => true, 'error' => null];
            
        } catch (Exception $e) {
            $errorMsg = $this->mail->ErrorInfo ?? $e->getMessage();
            error_log('PHPMailer error: ' . $errorMsg);
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
            'from_email' => $this->config['from_email'],
            'using_phpmailer' => $this->usePhpMailer,
            'smtp_user_set' => !empty($this->config['smtp_user'])
        ];
    }
}
