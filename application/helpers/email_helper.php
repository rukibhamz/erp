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
 * @param array $attachments Array of attachments (file paths or ['content' => string, 'filename' => string, 'mime' => string])
 * @return bool True if email sent successfully, false otherwise
 */
if (!function_exists('send_email')) {
    function send_email($to, $subject, $message, $fromEmail = null, $fromName = null, $isHtml = true, $attachments = []) {
        try {
            // Load settings - try config.installed.php first
            $configFile = BASEPATH . 'config/config.installed.php';
            if (!file_exists($configFile)) {
                $configFile = BASEPATH . 'config/config.php';
            }
            $config = require $configFile;
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
            // Note: Attachments only work with SMTP/PHPMailer
            if (!empty($smtpHost) && !empty($smtpUsername) && !empty($smtpPassword)) {
                return send_email_smtp($to, $subject, $message, $fromEmail, $fromName, 
                    $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption, $isHtml, $attachments);
            } else {
                // PHP mail() doesn't support attachments easily, so warn if attachments provided
                if (!empty($attachments)) {
                    error_log("Warning: Attachments require SMTP configuration. Email sent without attachments.");
                }
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
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body
 * @param string $fromEmail Sender email
 * @param string $fromName Sender name
 * @param string $smtpHost SMTP host
 * @param int $smtpPort SMTP port
 * @param string $smtpUsername SMTP username
 * @param string $smtpPassword SMTP password
 * @param string $smtpEncryption Encryption type
 * @param bool $isHtml Whether message is HTML
 * @param array $attachments Array of attachments
 * @return bool True if sent successfully
 */
if (!function_exists('send_email_smtp')) {
    function send_email_smtp($to, $subject, $message, $fromEmail, $fromName,
                            $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, 
                            $smtpEncryption = 'tls', $isHtml = true, $attachments = []) {
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
            
            // Try to load vendor autoload if not already loaded
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                 $autoloadPath = BASEPATH . '../vendor/autoload.php';
                 if (file_exists($autoloadPath)) {
                     require_once $autoloadPath;
                 }
            }

            // Use PHPMailer if available
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return send_email_phpmailer($to, $subject, $message, $fromEmail, $fromName,
                    $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption, $isHtml, $attachments);
            } else {
                // FALLBACK: If PHPMailer is missing (no vendor folder), we cannot use SMTP reliably.
                // Fall back to native PHP mail() so the email at least tries to go out.
                error_log("Warning: PHPMailer dependency missing. Falling back to native mail() function.");
                
                if (!empty($attachments)) {
                    error_log("Warning: Attachments cannot be sent without PHPMailer.");
                }
                
                return send_email_php($to, $subject, $message, $fromEmail, $fromName, $isHtml);
            }
        } catch (Exception $e) {
            error_log("SMTP email error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Send email using PHPMailer (if available)
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body
 * @param string $fromEmail Sender email
 * @param string $fromName Sender name
 * @param string $smtpHost SMTP host
 * @param int $smtpPort SMTP port
 * @param string $smtpUsername SMTP username
 * @param string $smtpPassword SMTP password
 * @param string $smtpEncryption Encryption type (tls or ssl)
 * @param bool $isHtml Whether message is HTML
 * @param array $attachments Array of attachment paths or ['content' => string, 'filename' => string, 'mime' => string]
 * @return bool True if sent successfully
 */
if (!function_exists('send_email_phpmailer')) {
    function send_email_phpmailer($to, $subject, $message, $fromEmail, $fromName,
                                $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, 
                                $smtpEncryption = 'tls', $isHtml = true, $attachments = []) {
        try {
            require_once BASEPATH . '../vendor/autoload.php';
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Enable verbose error output in debug mode
            // $mail->SMTPDebug = 2; // Uncomment for debugging
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpEncryption === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;
            
            // Enable TLS/SSL certificate verification (recommended for production)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                ]
            ];
            
            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            
            // Add attachments if provided
            if (!empty($attachments) && is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        // Attachment with content string
                        if (isset($attachment['content']) && isset($attachment['filename'])) {
                            $mime = $attachment['mime'] ?? 'application/pdf';
                            $mail->addStringAttachment($attachment['content'], $attachment['filename'], 'base64', $mime);
                        }
                    } elseif (is_string($attachment) && file_exists($attachment)) {
                        // Attachment as file path
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $message;
            if (!$isHtml) {
                $mail->AltBody = strip_tags($message);
            } else {
                // Create plain text version from HTML
                $mail->AltBody = strip_tags($message);
            }
            
            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
            error_log("PHPMailer error info: " . $mail->ErrorInfo);
            return false;
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Send email using socket-based SMTP (fallback)
 * SECURITY: Basic SMTP implementation with TLS support
 */
if (!function_exists('send_email_smtp_socket')) {
    function send_email_smtp_socket($to, $subject, $message, $fromEmail, $fromName,
                                    $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption = 'tls', $isHtml = true) {
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

/**
 * Send welcome email to guest user after booking
 * SECURITY: Sends a welcome email with account activation link
 * 
 * @param string $email User email address
 * @param string $resetToken Password reset token for account activation
 * @param string $userName User's name
 * @param array $bookingDetails Optional booking details to include
 * @return bool True if email sent successfully
 */
if (!function_exists('send_guest_welcome_email')) {
    function send_guest_welcome_email($email, $resetToken, $userName = null, $bookingDetails = []) {
        try {
            $activationLink = base_url('auth/resetPassword?token=' . urlencode($resetToken));
            $loginLink = base_url('customer-portal/login');
            $bookingNumber = $bookingDetails['booking_number'] ?? '';
            
            $subject = 'Welcome! Your Account Has Been Created - Business ERP';
            
            // Create HTML email template
            $message = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .button-success { background-color: #28a745; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .info-box { background-color: #e7f3ff; border-left: 4px solid #007bff; padding: 15px; margin: 15px 0; }
        .booking-box { background-color: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Welcome to Business ERP!</h2>
        </div>
        <div class='content'>
            <p>Hello" . ($userName ? " {$userName}" : "") . ",</p>
            <p>Thank you for making a booking with us! An account has been automatically created for you so you can manage your bookings online.</p>
            " . ($bookingNumber ? "
            <div class='booking-box'>
                <strong>Your Booking Reference:</strong><br>
                <span style='font-size: 1.2em; font-weight: bold;'>{$bookingNumber}</span>
            </div>
            " : "") . "
            <div class='info-box'>
                <strong>Set Up Your Password</strong><br>
                <p>To access your account and view your bookings, please set up a password:</p>
            </div>
            <p style='text-align: center;'>
                <a href='{$activationLink}' class='button button-success'>Set Up My Password</a>
            </p>
            <p>After setting your password, you can log in anytime to:</p>
            <ul>
                <li>View and manage your bookings</li>
                <li>Make new bookings</li>
                <li>View payment history</li>
                <li>Update your profile</li>
            </ul>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all;'>{$activationLink}</p>
        </div>
        <div class='footer'>
            <p>If you did not make a booking with us, please contact our support team.</p>
            <p>&copy; " . date('Y') . " Business ERP System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>";
            
            // Send email
            $result = send_email($email, $subject, $message);
            
            if ($result) {
                error_log("Guest welcome email sent successfully to: {$email}");
            } else {
                error_log("Failed to send guest welcome email to: {$email}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Guest welcome email error: " . $e->getMessage());
            return false;
        }
    }
}
