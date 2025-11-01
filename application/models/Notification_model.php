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
     * Send booking confirmation notification
     */
    public function sendBookingConfirmation($bookingId, $booking) {
        try {
            // Create in-app notification
            $this->createNotification([
                'customer_email' => $booking['customer_email'],
                'type' => 'booking_confirmation',
                'title' => 'Booking Confirmed',
                'message' => "Your booking {$booking['booking_number']} has been confirmed for {$booking['facility_name']} on " . date('M d, Y', strtotime($booking['booking_date'])),
                'related_module' => 'booking',
                'related_id' => $bookingId
            ]);
            
            // Queue email
            $this->queueEmail([
                'to_email' => $booking['customer_email'],
                'to_name' => $booking['customer_name'],
                'subject' => "Booking Confirmed - {$booking['booking_number']}",
                'body' => $this->renderBookingConfirmationEmail($booking),
                'body_html' => $this->renderBookingConfirmationEmail($booking, true)
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Notification_model sendBookingConfirmation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Queue email for sending
     */
    private function queueEmail($data) {
        try {
            return $this->db->insert('email_queue', [
                'to_email' => $data['to_email'],
                'to_name' => $data['to_name'] ?? null,
                'subject' => $data['subject'],
                'body' => $data['body'],
                'body_html' => $data['body_html'] ?? null,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Notification_model queueEmail error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Render booking confirmation email
     */
    private function renderBookingConfirmationEmail($booking, $html = false) {
        $break = $html ? '<br>' : "\n";
        
        $message = $html ? '<html><body>' : '';
        $message .= "Dear {$booking['customer_name']},{$break}{$break}";
        $message .= "Your booking has been confirmed!{$break}{$break}";
        $message .= "Booking Details:{$break}";
        $message .= "Booking Number: {$booking['booking_number']}{$break}";
        $message .= "Resource: {$booking['facility_name']}{$break}";
        $message .= "Date: " . date('M d, Y', strtotime($booking['booking_date'])) . "{$break}";
        $message .= "Time: " . date('g:i A', strtotime($booking['start_time'])) . " - " . date('g:i A', strtotime($booking['end_time'])) . "{$break}";
        $message .= "Total Amount: " . format_currency($booking['total_amount']) . "{$break}{$break}";
        $message .= "Thank you for your booking!";
        $message .= $html ? '</body></html>' : '';
        
        return $message;
    }
}

