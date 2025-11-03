<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends Base_Controller {
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read'); // Or create a notifications permission
        $this->notificationModel = $this->loadModel('Notification_model');
    }
    
    /**
     * Get notifications (AJAX)
     */
    public function getNotifications() {
        header('Content-Type: application/json');
        
        $userId = $this->session['user_id'] ?? null;
        $unreadOnly = !empty($_GET['unread_only']);
        $limit = intval($_GET['limit'] ?? 20);
        
        try {
            $notifications = $this->notificationModel->getUserNotifications($userId, $unreadOnly, $limit);
            $unreadCount = $this->notificationModel->getUnreadCount($userId);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Mark notification as read
     */
    public function markRead($id) {
        header('Content-Type: application/json');
        
        try {
            if ($this->notificationModel->markAsRead($id)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Mark all as read
     */
    public function markAllRead() {
        header('Content-Type: application/json');
        
        $userId = $this->session['user_id'] ?? null;
        
        try {
            if ($this->notificationModel->markAllAsRead($userId)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark all as read']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Notification settings/preferences page
     */
    public function settings() {
        $userId = $this->session['user_id'] ?? null;
        $userEmail = $this->session['email'] ?? null;
        
        // Get user preferences
        try {
            $prefix = $this->db->getPrefix();
            $preferences = $this->db->fetchAll(
                "SELECT * FROM `{$prefix}notification_preferences` 
                 WHERE (user_id = ? OR customer_email = ?) 
                 ORDER BY preference_type, notification_type",
                [$userId, $userEmail]
            );
            
            // Organize preferences by type
            $prefsByType = [];
            foreach ($preferences as $pref) {
                $key = $pref['preference_type'];
                if (!isset($prefsByType[$key])) {
                    $prefsByType[$key] = [];
                }
                $typeKey = $pref['notification_type'] ?? 'all';
                $prefsByType[$key][$typeKey] = $pref;
            }
        } catch (Exception $e) {
            error_log('Notifications settings error: ' . $e->getMessage());
            $prefsByType = [];
        }
        
        $data = [
            'page_title' => 'Notification Preferences',
            'preferences' => $prefsByType,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('notifications/settings', $data);
    }
    
    /**
     * Save notification preferences
     */
    public function savePreferences() {
        header('Content-Type: application/json');
        
        $userId = $this->session['user_id'] ?? null;
        $userEmail = $this->session['email'] ?? null;
        $prefix = $this->db->getPrefix();
        
        try {
            $preferences = json_decode($_POST['preferences'] ?? '{}', true);
            
            foreach ($preferences as $prefType => $types) {
                foreach ($types as $notifType => $settings) {
                    $notifType = $notifType === 'all' ? null : sanitize_input($notifType);
                    
                    // Check if preference exists
                    $whereClause = "preference_type = ?";
                    $params = [$prefType];
                    
                    if ($userId) {
                        $whereClause .= " AND user_id = ?";
                        $params[] = $userId;
                    } else {
                        $whereClause .= " AND customer_email = ?";
                        $params[] = $userEmail;
                    }
                    
                    if ($notifType) {
                        $whereClause .= " AND notification_type = ?";
                        $params[] = $notifType;
                    } else {
                        $whereClause .= " AND notification_type IS NULL";
                    }
                    
                    $existing = $this->db->fetchOne(
                        "SELECT id FROM `{$prefix}notification_preferences` WHERE {$whereClause}",
                        $params
                    );
                    
                    $data = [
                        'preference_type' => sanitize_input($prefType),
                        'notification_type' => $notifType,
                        'enabled' => !empty($settings['enabled']) ? 1 : 0,
                        'frequency' => sanitize_input($settings['frequency'] ?? 'instant'),
                        'quiet_hours_start' => !empty($settings['quiet_start']) ? sanitize_input($settings['quiet_start']) : null,
                        'quiet_hours_end' => !empty($settings['quiet_end']) ? sanitize_input($settings['quiet_end']) : null,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($userId) {
                        $data['user_id'] = $userId;
                    } else {
                        $data['customer_email'] = $userEmail;
                    }
                    
                    if ($existing) {
                        $this->db->update(
                            'notification_preferences',
                            $data,
                            "id = ?",
                            [$existing['id']]
                        );
                    } else {
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $this->db->insert('notification_preferences', $data);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Preferences saved successfully']);
        } catch (Exception $e) {
            error_log('Notifications savePreferences error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

