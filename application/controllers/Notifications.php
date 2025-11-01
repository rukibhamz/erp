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
     * Notification settings page (admin)
     */
    public function settings() {
        $this->requirePermission('settings', 'read');
        
        $data = [
            'page_title' => 'Notification Settings',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('notifications/settings', $data);
    }
}

