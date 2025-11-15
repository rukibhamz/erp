<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email Sender Library
 * Uses PHPMailer for sending emails with SMTP
 */
class Email_sender {
    private $mail;
    private $config;
    
    public function __construct() {
        // Load Composer autoloader if available
        $composerAutoload = BASEPATH . '../vendor/autoload.php';
        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
        }
        
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log('PHPMailer not found. Please install via Composer: composer require phpmailer/phpmailer');
            throw new Exception('PHPMailer library not available');
        }
        
        $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $this->loadConfig();
        $this->configure();
    }
    
    /**
     * Load email configuration from config file
     */
    private function loadConfig() {
        // Try config.installed.php first, then config.php
        $configFile = BASEPATH . 'config/config.installed.php';
        if (!file_exists($configFile)) {
            $configFile = BASEPATH . 'config/config.php';
        }
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            $emailConfig = $config['email'] ?? [];
        } else {
            $emailConfig = [];
        }
        
        // Default configuration
        $this->config = [
            'smtp_host' => $emailConfig['smtp_host'] ?? 'smtp.gmail.com',
            'smtp_port' => $emailConfig['smtp_port'] ?? 587,
            'smtp_user' => $emailConfig['smtp_username'] ?? $emailConfig['smtp_user'] ?? '',
            'smtp_pass' => $emailConfig['smtp_password'] ?? $emailConfig['smtp_pass'] ?? '',
            'smtp_encryption' => $emailConfig['smtp_encryption'] ?? 'tls',
            'from_email' => $emailConfig['from_email'] ?? 'noreply@localhost',
            'from_name' => $emailConfig['from_name'] ?? 'Business ERP System'
        ];
    }
    
    /**
     * Configure PHPMailer with SMTP settings
     */
    private function configure() {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['smtp_user'];
            $this->mail->Password = $this->config['smtp_pass'];
            
            // Set encryption
            if ($this->config['smtp_encryption'] === 'ssl') {
                $this->mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $this->mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $this->mail->Port = $this->config['smtp_port'];
            
            // Enable verbose error output in debug mode (uncomment for debugging)
            // $this->mail->SMTPDebug = 2;
            
            // TLS/SSL certificate verification (recommended for production)
            $this->mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                ]
            ];
            
            // Set from address
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Character encoding
            $this->mail->CharSet = 'UTF-8';
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('Email Configuration Error: ' . $e->getMessage());
            throw new Exception('Email configuration failed: ' . $e->getMessage());
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
        try {
            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => 'Invalid recipient email address'
                ];
            }
            
            // Reset for new email
            $this->reset();
            
            // Recipients
            $this->mail->addAddress($to);
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);
            
            // Attach PDF if provided
            if ($pdfPath && file_exists($pdfPath)) {
                $attachmentName = $pdfName ?? basename($pdfPath);
                $this->mail->addAttachment($pdfPath, $attachmentName);
            }
            
            // Send
            $this->mail->send();
            
            return [
                'success' => true,
                'error' => null
            ];
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $errorMsg = $this->mail->ErrorInfo ?? $e->getMessage();
            error_log('Email Send Error: ' . $errorMsg);
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        } catch (Exception $e) {
            error_log('Email Send Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send general email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $attachments Array of file paths to attach
     * @return array ['success' => bool, 'error' => string]
     */
    public function sendEmail($to, $subject, $body, $attachments = []) {
        try {
            // Validate email
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => 'Invalid recipient email address'
                ];
            }
            
            // Reset for new email
            $this->reset();
            
            // Recipients
            $this->mail->addAddress($to);
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);
            
            // Add attachments
            if (!empty($attachments) && is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_string($attachment) && file_exists($attachment)) {
                        $this->mail->addAttachment($attachment);
                    } elseif (is_array($attachment) && isset($attachment['path']) && file_exists($attachment['path'])) {
                        $name = $attachment['name'] ?? basename($attachment['path']);
                        $this->mail->addAttachment($attachment['path'], $name);
                    }
                }
            }
            
            // Send
            $this->mail->send();
            
            return [
                'success' => true,
                'error' => null
            ];
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $errorMsg = $this->mail->ErrorInfo ?? $e->getMessage();
            error_log('Email Send Error: ' . $errorMsg);
            return [
                'success' => false,
                'error' => $errorMsg
            ];
        } catch (Exception $e) {
            error_log('Email Send Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reset PHPMailer for sending another email
     */
    public function reset() {
        $this->mail->clearAddresses();
        $this->mail->clearAttachments();
        $this->mail->clearReplyTos();
        $this->mail->clearCCs();
        $this->mail->clearBCCs();
        $this->mail->clearCustomHeaders();
    }
}

