<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email Helper Functions
 * SECURITY: Provides secure email sending functionality
 */

/**
 * Send email using configured SMTP or PHP mail()
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML or plain text)
 * @param string $fromEmail Sender email address
 * @param string $fromName Sender name
 * @param bool $isHtml Whether message is HTML
 * @return bool True if email sent successfully, false otherwise
 */
if (!function_exists('send_email')) {
    function send_email($to, $subject, $message, $fromEmail = null, $fromName = null, $isHtml = true) {
        try {
            // Load settings
            $config = require BASEPATH . 'config/config.php';
            $settings = $config['email'] ?? [];
            
            // Get SMTP settings or use defaults
            $smtpHost = $settings['smtp_host'] ?? null;
            $smtpPort = $settings['smtp_port'] ?? 587;
            $smtpUsername = $settings['smtp_username'] ?? null;
            $smtpPassword = $settings['smtp_password'] ?? null;
            $smtpEncryption = $settings['smtp_encryption'] ?? 'tls';
            $fromEmail = $fromEmail ?? ($settings['from_email'] ?? 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $fromName = $fromName ?? ($settings['from_name'] ?? 'Business ERP System');
            
            // If SMTP is configured, use it; otherwise use PHP mail()
            if (!empty($smtpHost) && !empty($smtpUsername) && !empty($smtpPassword)) {
                return send_email_smtp($to, $subject, $message, $fromEmail, $fromName, $isHtml, 
                    $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption);
            } else {
                return send_email_php($to, $subject, $message, $fromEmail, $fromName, $isHtml);
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Send email using PHP mail() function
 * SECURITY: Uses proper headers and escapes content
 */
if (!function_exists('send_email_php')) {
    function send_email_php($to, $subject, $message, $fromEmail, $fromName, $isHtml = true) {
        try {
            // Validate email addresses
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid recipient email: {$to}");
                return false;
            }
            
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid sender email: {$fromEmail}");
                return false;
            }
            
            // Prepare headers
            $headers = [];
            $headers[] = "From: " . ($fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail);
            $headers[] = "Reply-To: {$fromEmail}";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            $headers[] = "MIME-Version: 1.0";
            
            if ($isHtml) {
                $headers[] = "Content-Type: text/html; charset=UTF-8";
                // Convert plain text to HTML if needed
                if (strip_tags($message) === $message) {
                    $message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
                }
            } else {
                $headers[] = "Content-Type: text/plain; charset=UTF-8";
            }
            
            // Send email
            $result = mail($to, $subject, $message, implode("\r\n", $headers));
            
            if (!$result) {
                error_log("Failed to send email to {$to}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("PHP mail() error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Send email using SMTP
 * SECURITY: Uses secure SMTP connection with authentication
 */
if (!function_exists('send_email_smtp')) {
    function send_email_smtp($to, $subject, $message, $fromEmail, $fromName, $isHtml = true,
                            $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption = 'tls') {
        try {
            // Validate email addresses
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid recipient email: {$to}");
                return false;
            }
            
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                error_log("Invalid sender email: {$fromEmail}");
                return false;
            }
            
            // Use PHPMailer if available, otherwise use socket-based SMTP
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return send_email_phpmailer($to, $subject, $message, $fromEmail, $fromName, $isHtml,
                    $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption);
            } else {
                // Fallback to socket-based SMTP (basic implementation)
                return send_email_smtp_socket($to, $subject, $message, $fromEmail, $fromName, $isHtml,
                    $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption);
            }
        } catch (Exception $e) {
            error_log("SMTP email error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Send email using PHPMailer (if available)
 */
if (!function_exists('send_email_phpmailer')) {
    function send_email_phpmailer($to, $subject, $message, $fromEmail, $fromName, $isHtml = true,
                                $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption = 'tls') {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;
            
            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $message;
            if (!$isHtml) {
                $mail->AltBody = strip_tags($message);
            }
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Send email using socket-based SMTP (fallback)
 * SECURITY: Basic SMTP implementation with TLS support
 */
if (!function_exists('send_email_smtp_socket')) {
    function send_email_smtp_socket($to, $subject, $message, $fromEmail, $fromName, $isHtml = true,
                                    $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption = 'tls') {
        // This is a simplified implementation
        // For production, consider using PHPMailer or SwiftMailer
        error_log("Socket-based SMTP not fully implemented. Please install PHPMailer or configure PHP mail().");
        return false;
    }
}

/**
 * Send password reset email
 * SECURITY: Sends secure password reset link via email
 * 
 * @param string $email User email address
 * @param string $resetToken Password reset token
 * @param string $userName User's name (optional)
 * @return bool True if email sent successfully
 */
if (!function_exists('send_password_reset_email')) {
    function send_password_reset_email($email, $resetToken, $userName = null) {
        try {
            $resetLink = base_url('auth/resetPassword?token=' . urlencode($resetToken));
            $expiryTime = date('M d, Y \a\t g:i A', strtotime('+1 hour'));
            
            $subject = 'Password Reset Request - Business ERP';
            
            // Create HTML email template
            $message = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Password Reset Request</h2>
        </div>
        <div class='content'>
            <p>Hello" . ($userName ? " {$userName}" : "") . ",</p>
            <p>You have requested to reset your password for your Business ERP account.</p>
            <p>Click the button below to reset your password:</p>
            <p style='text-align: center;'>
                <a href='{$resetLink}' class='button'>Reset Password</a>
            </p>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all;'>{$resetLink}</p>
            <div class='warning'>
                <strong>Security Notice:</strong> This link will expire in 1 hour. If you did not request this password reset, please ignore this email or contact support if you have concerns.
            </div>
            <p>This link expires on: <strong>{$expiryTime}</strong></p>
        </div>
        <div class='footer'>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; " . date('Y') . " Business ERP System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>";
            
            // Send email
            $result = send_email($email, $subject, $message);
            
            if ($result) {
                error_log("Password reset email sent successfully to: {$email}");
            } else {
                error_log("Failed to send password reset email to: {$email}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Password reset email error: " . $e->getMessage());
            return false;
        }
    }
}

