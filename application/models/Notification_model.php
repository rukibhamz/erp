<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends Base_Model {
    protected $table = 'notifications';
    
    /**
     * Create notification
     */
    public function createNotification($data) {
        try {
            $notificationData = [
                'user_id' => $data['user_id'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'type' => $data['type'] ?? 'other',
                'title' => $data['title'] ?? '',
                'message' => $data['message'] ?? '',
                'related_module' => $data['related_module'] ?? null,
                'related_id' => $data['related_id'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->create($notificationData);
        } catch (Exception $e) {
            error_log('Notification_model createNotification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notifications for user
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE user_id = ?";
            $params = [$userId];
            
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Notification_model getUserNotifications error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get notifications for customer
     */
    public function getCustomerNotifications($customerEmail, $unreadOnly = false, $limit = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE customer_email = ?";
            $params = [$customerEmail];
            
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Notification_model getCustomerNotifications error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        try {
            return $this->update($notificationId, [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Notification_model markAsRead error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all as read for user
     */
    public function markAllAsRead($userId) {
        try {
            return $this->db->update(
                $this->table,
                ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')],
                "user_id = ? AND is_read = 0",
                [$userId]
            );
        } catch (Exception $e) {
            error_log('Notification_model markAllAsRead error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId = null, $customerEmail = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE is_read = 0";
            $params = [];
            
            if ($userId) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            } elseif ($customerEmail) {
                $sql .= " AND customer_email = ?";
                $params[] = $customerEmail;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            return intval($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Notification_model getUnreadCount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send booking pending email (before payment)
     * This is sent immediately after booking is created
     */
    public function sendBookingPendingEmail($booking, $paymentUrl = null) {
        try {
            // Create in-app notification
            $this->createNotification([
                'customer_email' => $booking['customer_email'],
                'type' => 'booking_pending',
                'title' => 'Booking Created - Payment Required',
                'message' => "Your booking {$booking['booking_number']} has been created. Please complete payment within 30 minutes.",
                'related_module' => 'booking',
                'related_id' => $booking['id']
            ]);
            
            // Queue and send email
            $this->queueEmail([
                'to_email' => $booking['customer_email'],
                'to_name' => $booking['customer_name'],
                'subject' => "Booking Created - Payment Required #{$booking['booking_number']}",
                'body' => $this->renderBookingPendingEmail($booking, $paymentUrl, false),
                'body_html' => $this->renderBookingPendingEmail($booking, $paymentUrl, true)
            ]);
            
            error_log("sendBookingPendingEmail: Sent to " . $booking['customer_email']);
            return true;
        } catch (Exception $e) {
            error_log('Notification_model sendBookingPendingEmail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking confirmation notification (after successful payment)
     */
    public function sendBookingConfirmation($bookingId, $booking) {
        try {
            // Create in-app notification
            $this->createNotification([
                'customer_email' => $booking['customer_email'],
                'type' => 'booking_confirmation',
                'title' => 'Booking Confirmed',
                'message' => "Your booking {$booking['booking_number']} has been confirmed for " . ($booking['facility_name'] ?? $booking['space_name'] ?? 'your space') . " on " . date('M d, Y', strtotime($booking['booking_date'])),
                'related_module' => 'booking',
                'related_id' => $bookingId
            ]);
            
            // Queue email
            $this->queueEmail([
                'to_email' => $booking['customer_email'],
                'to_name' => $booking['customer_name'],
                'subject' => "Payment Confirmed - Booking #{$booking['booking_number']}",
                'body' => $this->renderBookingConfirmationEmail($booking, false),
                'body_html' => $this->renderBookingConfirmationEmail($booking, true)
            ]);
            
            error_log("sendBookingConfirmation: Sent to " . $booking['customer_email']);
            return true;
        } catch (Exception $e) {
            error_log('Notification_model sendBookingConfirmation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send payment failed email
     */
    public function sendPaymentFailedEmail($booking, $retryUrl = null) {
        try {
            // Create in-app notification
            $this->createNotification([
                'customer_email' => $booking['customer_email'],
                'type' => 'payment_failed',
                'title' => 'Payment Failed',
                'message' => "Payment for booking {$booking['booking_number']} was unsuccessful. Please try again.",
                'related_module' => 'booking',
                'related_id' => $booking['id']
            ]);
            
            // Queue email
            $this->queueEmail([
                'to_email' => $booking['customer_email'],
                'to_name' => $booking['customer_name'],
                'subject' => "Payment Failed - Booking #{$booking['booking_number']}",
                'body' => $this->renderPaymentFailedEmail($booking, $retryUrl, false),
                'body_html' => $this->renderPaymentFailedEmail($booking, $retryUrl, true)
            ]);
            
            error_log("sendPaymentFailedEmail: Sent to " . $booking['customer_email']);
            return true;
        } catch (Exception $e) {
            error_log('Notification_model sendPaymentFailedEmail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Queue email for sending
     */
    private function queueEmail($data) {
        try {
            $status = 'pending';
            $sentAt = null;
            
            // Ensure email helper is loaded
            if (!function_exists('send_email')) {
                $helperPaths = [
                    APPPATH . 'helpers/email_helper.php',
                    BASEPATH . '../application/helpers/email_helper.php'
                ];
                
                foreach ($helperPaths as $path) {
                    if (file_exists($path)) {
                        include_once $path;
                        break;
                    }
                }
            }
            
            // Try to send email immediately
            if (function_exists('send_email')) {
                $message = $data['body_html'] ?? $data['body'];
                if (send_email($data['to_email'], $data['subject'], $message, null, null, true)) {
                    $status = 'sent';
                    $sentAt = date('Y-m-d H:i:s');
                    error_log("queueEmail: Email sent successfully to " . $data['to_email']);
                } else {
                    error_log("queueEmail: send_email returned false for " . $data['to_email']);
                }
            } else {
                error_log("queueEmail: send_email function not available after loading helper");
            }

            return $this->db->insert('email_queue', [
                'to_email' => $data['to_email'],
                'to_name' => $data['to_name'] ?? null,
                'subject' => $data['subject'],
                'body' => $data['body'],
                'body_html' => $data['body_html'] ?? null,
                'status' => $status,
                'sent_at' => $sentAt,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Notification_model queueEmail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get base email styles
     */
    private function getEmailStyles() {
        return '
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .email-wrapper { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { padding: 30px 20px; text-align: center; }
            .header-success { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
            .header-pending { background: linear-gradient(135deg, #ffc107, #fd7e14); color: #333; }
            .header-failed { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; background: #fff; }
            .booking-details { background: #f8f9fa; padding: 20px; margin: 20px 0; border-left: 4px solid #17a2b8; border-radius: 4px; }
            .detail-row { margin: 10px 0; }
            .label { font-weight: bold; color: #555; display: inline-block; min-width: 120px; }
            .button { 
                display: inline-block; 
                padding: 15px 30px; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px; 
                font-weight: bold;
                margin: 20px 0;
            }
            .button-primary { background: #28a745; }
            .button-warning { background: #ffc107; color: #333; }
            .button-danger { background: #dc3545; }
            .warning-box { 
                background: #fff3cd; 
                border-left: 4px solid #ffc107; 
                padding: 15px; 
                margin: 20px 0; 
                border-radius: 4px;
            }
            .success-box { 
                background: #d4edda; 
                border-left: 4px solid #28a745; 
                padding: 15px; 
                margin: 20px 0; 
                border-radius: 4px;
                text-align: center;
            }
            .footer { text-align: center; color: #777; font-size: 12px; padding: 20px; background: #f8f9fa; }
        ';
    }
    
    /**
     * Render booking pending email (before payment)
     */
    private function renderBookingPendingEmail($booking, $paymentUrl = null, $html = false) {
        $facilityName = $booking['facility_name'] ?? $booking['space_name'] ?? 'Reserved Space';
        $currency = $booking['currency'] ?? 'NGN';
        $totalAmount = number_format($booking['total_amount'] ?? 0, 2);
        $bookingDate = date('F j, Y', strtotime($booking['booking_date']));
        $startTime = date('g:i A', strtotime($booking['start_time']));
        $endTime = date('g:i A', strtotime($booking['end_time']));
        
        if (!$html) {
            $msg = "Dear {$booking['customer_name']},\n\n";
            $msg .= "Your booking has been created and is awaiting payment.\n\n";
            $msg .= "Booking Number: {$booking['booking_number']}\n";
            $msg .= "Facility: {$facilityName}\n";
            $msg .= "Date: {$bookingDate}\n";
            $msg .= "Time: {$startTime} - {$endTime}\n";
            $msg .= "Total: {$currency} {$totalAmount}\n\n";
            $msg .= "IMPORTANT: Complete payment within 30 minutes or your booking will be cancelled.\n\n";
            if ($paymentUrl) {
                $msg .= "Payment Link: {$paymentUrl}\n\n";
            }
            $msg .= "Thank you!";
            return $msg;
        }
        
        return '<!DOCTYPE html>
<html>
<head><style>' . $this->getEmailStyles() . '</style></head>
<body>
<div class="container">
    <div class="email-wrapper">
        <div class="header header-pending">
            <h1>⏳ Booking Created</h1>
            <p style="margin: 5px 0;">Payment Required</p>
        </div>
        
        <div class="content">
            <p>Dear ' . htmlspecialchars($booking['customer_name']) . ',</p>
            
            <p>Thank you for your booking! Your reservation has been created and is <strong>pending payment confirmation</strong>.</p>
            
            <div class="booking-details">
                <h3 style="margin-top: 0;">📋 Booking Details</h3>
                <div class="detail-row"><span class="label">Booking #:</span> ' . htmlspecialchars($booking['booking_number']) . '</div>
                <div class="detail-row"><span class="label">Facility:</span> ' . htmlspecialchars($facilityName) . '</div>
                <div class="detail-row"><span class="label">Date:</span> ' . $bookingDate . '</div>
                <div class="detail-row"><span class="label">Time:</span> ' . $startTime . ' - ' . $endTime . '</div>
                <div class="detail-row"><span class="label">Duration:</span> ' . ($booking['duration_hours'] ?? 'N/A') . ' hours</div>
                <div class="detail-row"><span class="label">Total Amount:</span> <strong>' . $currency . ' ' . $totalAmount . '</strong></div>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ Action Required</strong><br>
                Your booking is currently <strong>PENDING</strong> and will be automatically cancelled if payment is not completed within <strong>30 minutes</strong>.
            </div>
            
            ' . ($paymentUrl ? '<div style="text-align: center;">
                <a href="' . $paymentUrl . '" class="button button-primary">💳 Complete Payment Now</a>
            </div>' : '') . '
            
            <p>If you have any questions, please contact our support team.</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
            <p>&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</div>
</body>
</html>';
    }
    
    /**
     * Render booking confirmation email (after payment)
     */
    private function renderBookingConfirmationEmail($booking, $html = false) {
        $facilityName = $booking['facility_name'] ?? $booking['space_name'] ?? 'Reserved Space';
        $currency = $booking['currency'] ?? 'NGN';
        $totalAmount = number_format($booking['total_amount'] ?? 0, 2);
        $paidAmount = number_format($booking['paid_amount'] ?? $booking['total_amount'] ?? 0, 2);
        $bookingDate = date('F j, Y', strtotime($booking['booking_date']));
        $startTime = date('g:i A', strtotime($booking['start_time']));
        $endTime = date('g:i A', strtotime($booking['end_time']));
        
        if (!$html) {
            $msg = "Dear {$booking['customer_name']},\n\n";
            $msg .= "🎉 Your booking has been CONFIRMED!\n\n";
            $msg .= "Booking Number: {$booking['booking_number']}\n";
            $msg .= "Status: CONFIRMED\n";
            $msg .= "Facility: {$facilityName}\n";
            $msg .= "Date: {$bookingDate}\n";
            $msg .= "Time: {$startTime} - {$endTime}\n";
            $msg .= "Amount Paid: {$currency} {$paidAmount}\n\n";
            $msg .= "What's Next:\n";
            $msg .= "- Your time slot has been reserved\n";
            $msg .= "- Please arrive 15 minutes early\n";
            $msg .= "- Bring a valid ID for verification\n\n";
            $msg .= "Thank you for your booking!";
            return $msg;
        }
        
        return '<!DOCTYPE html>
<html>
<head><style>' . $this->getEmailStyles() . '</style></head>
<body>
<div class="container">
    <div class="email-wrapper">
        <div class="header header-success">
            <h1>✓ Payment Confirmed</h1>
            <p style="margin: 5px 0;">Your booking is now confirmed!</p>
        </div>
        
        <div class="content">
            <div class="success-box">
                <h2 style="margin: 0; color: #155724;">🎉 Booking Confirmed!</h2>
                <p style="margin: 5px 0;">Your payment has been received and your booking is now confirmed.</p>
            </div>
            
            <p>Dear ' . htmlspecialchars($booking['customer_name']) . ',</p>
            
            <p>Thank you for your payment! Your booking has been confirmed and your time slot has been reserved.</p>
            
            <div class="booking-details">
                <h3 style="margin-top: 0;">📋 Booking Details</h3>
                <div class="detail-row"><span class="label">Booking #:</span> ' . htmlspecialchars($booking['booking_number']) . '</div>
                <div class="detail-row"><span class="label">Status:</span> <strong style="color: #28a745;">✓ CONFIRMED</strong></div>
                <div class="detail-row"><span class="label">Facility:</span> ' . htmlspecialchars($facilityName) . '</div>
                <div class="detail-row"><span class="label">Date:</span> ' . $bookingDate . '</div>
                <div class="detail-row"><span class="label">Time:</span> ' . $startTime . ' - ' . $endTime . '</div>
                <div class="detail-row"><span class="label">Duration:</span> ' . ($booking['duration_hours'] ?? 'N/A') . ' hours</div>
            </div>
            
            <div class="booking-details" style="border-left-color: #28a745;">
                <h3 style="margin-top: 0;">💳 Payment Information</h3>
                <div class="detail-row"><span class="label">Amount Paid:</span> <strong>' . $currency . ' ' . $paidAmount . '</strong></div>
                <div class="detail-row"><span class="label">Payment Date:</span> ' . date('F j, Y g:i A') . '</div>
                <div class="detail-row"><span class="label">Payment Method:</span> Online Payment</div>
            </div>
            
            <p><strong>What\'s Next?</strong></p>
            <ul>
                <li>✓ Your time slot has been reserved</li>
                <li>📧 You will receive a reminder 24 hours before your booking</li>
                <li>⏰ Please arrive 15 minutes early for check-in</li>
                <li>🪪 Bring a valid ID for verification</li>
            </ul>
            
            <p>We look forward to seeing you!</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
            <p>&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</div>
</body>
</html>';
    }
    
    /**
     * Render payment failed email
     */
    private function renderPaymentFailedEmail($booking, $retryUrl = null, $html = false) {
        $facilityName = $booking['facility_name'] ?? $booking['space_name'] ?? 'Reserved Space';
        $currency = $booking['currency'] ?? 'NGN';
        $totalAmount = number_format($booking['total_amount'] ?? 0, 2);
        $bookingDate = date('F j, Y', strtotime($booking['booking_date']));
        $startTime = date('g:i A', strtotime($booking['start_time']));
        $endTime = date('g:i A', strtotime($booking['end_time']));
        
        if (!$html) {
            $msg = "Dear {$booking['customer_name']},\n\n";
            $msg .= "Unfortunately, your payment for booking #{$booking['booking_number']} was unsuccessful.\n\n";
            $msg .= "Booking Details:\n";
            $msg .= "- Facility: {$facilityName}\n";
            $msg .= "- Date: {$bookingDate}\n";
            $msg .= "- Time: {$startTime} - {$endTime}\n";
            $msg .= "- Amount: {$currency} {$totalAmount}\n\n";
            $msg .= "What you can do:\n";
            $msg .= "- Try the payment again with a different payment method\n";
            $msg .= "- Check your card details and try again\n";
            $msg .= "- Contact your bank if the issue persists\n\n";
            if ($retryUrl) {
                $msg .= "Retry Payment: {$retryUrl}\n\n";
            }
            $msg .= "If you need assistance, please contact our support team.";
            return $msg;
        }
        
        return '<!DOCTYPE html>
<html>
<head><style>' . $this->getEmailStyles() . '</style></head>
<body>
<div class="container">
    <div class="email-wrapper">
        <div class="header header-failed">
            <h1>⚠️ Payment Failed</h1>
            <p style="margin: 5px 0;">Your payment could not be processed</p>
        </div>
        
        <div class="content">
            <p>Dear ' . htmlspecialchars($booking['customer_name']) . ',</p>
            
            <p>Unfortunately, we were unable to process your payment for booking <strong>#' . htmlspecialchars($booking['booking_number']) . '</strong>.</p>
            
            <div class="booking-details" style="border-left-color: #dc3545;">
                <h3 style="margin-top: 0;">📋 Booking Details</h3>
                <div class="detail-row"><span class="label">Booking #:</span> ' . htmlspecialchars($booking['booking_number']) . '</div>
                <div class="detail-row"><span class="label">Facility:</span> ' . htmlspecialchars($facilityName) . '</div>
                <div class="detail-row"><span class="label">Date:</span> ' . $bookingDate . '</div>
                <div class="detail-row"><span class="label">Time:</span> ' . $startTime . ' - ' . $endTime . '</div>
                <div class="detail-row"><span class="label">Amount:</span> ' . $currency . ' ' . $totalAmount . '</div>
            </div>
            
            <p><strong>What you can do:</strong></p>
            <ul>
                <li>Try the payment again with a different payment method</li>
                <li>Check your card details and try again</li>
                <li>Contact your bank if the issue persists</li>
                <li>Contact us for alternative payment options</li>
            </ul>
            
            ' . ($retryUrl ? '<div style="text-align: center;">
                <a href="' . $retryUrl . '" class="button button-danger">🔄 Retry Payment</a>
            </div>' : '') . '
            
            <p>If you need assistance, please contact our support team.</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
            <p>&copy; ' . date('Y') . ' All rights reserved.</p>
        </div>
    </div>
</div>
</body>
</html>';
    }
}

