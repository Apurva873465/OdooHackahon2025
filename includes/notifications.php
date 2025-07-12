<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createNotification($userId, $type, $title, $message, $data = []) {
        $notificationId = $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => json_encode($data),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $notificationId;
    }
    
    public function getNotifications($userId, $limit = 20, $offset = 0) {
        return $this->db->fetchAll("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ", [$userId, $limit, $offset]);
    }
    
    public function getUnreadCount($userId) {
        $result = $this->db->fetchOne("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ", [$userId]);
        
        return $result['count'];
    }
    
    public function markAsRead($notificationId, $userId) {
        return $this->db->update('notifications', 
            ['is_read' => 1], 
            'id = ? AND user_id = ?', 
            [$notificationId, $userId]
        );
    }
    
    public function markAllAsRead($userId) {
        return $this->db->update('notifications', 
            ['is_read' => 1], 
            'user_id = ?', 
            [$userId]
        );
    }
    
    public function deleteNotification($notificationId, $userId) {
        return $this->db->delete('notifications', 
            'id = ? AND user_id = ?', 
            [$notificationId, $userId]
        );
    }
    
    // Notification types
    public function notifySwapRequest($receiverId, $requesterId, $swapId) {
        $requester = $this->db->fetchOne("SELECT name FROM users WHERE id = ?", [$requesterId]);
        
        return $this->createNotification(
            $receiverId,
            'swap_request',
            'New Swap Request',
            $requester['name'] . ' has sent you a skill swap request.',
            [
                'swap_id' => $swapId,
                'requester_id' => $requesterId,
                'requester_name' => $requester['name']
            ]
        );
    }
    
    public function notifySwapResponse($requesterId, $receiverId, $swapId, $status) {
        $receiver = $this->db->fetchOne("SELECT name FROM users WHERE id = ?", [$receiverId]);
        
        $message = $status === 'accepted' 
            ? $receiver['name'] . ' has accepted your swap request.'
            : $receiver['name'] . ' has declined your swap request.';
        
        return $this->createNotification(
            $requesterId,
            'swap_response',
            'Swap Request ' . ucfirst($status),
            $message,
            [
                'swap_id' => $swapId,
                'receiver_id' => $receiverId,
                'receiver_name' => $receiver['name'],
                'status' => $status
            ]
        );
    }
    
    public function notifyFeedback($userId, $fromUserId, $swapId, $rating) {
        $fromUser = $this->db->fetchOne("SELECT name FROM users WHERE id = ?", [$fromUserId]);
        
        return $this->createNotification(
            $userId,
            'feedback',
            'New Feedback Received',
            $fromUser['name'] . ' has left you ' . $rating . '-star feedback.',
            [
                'swap_id' => $swapId,
                'from_user_id' => $fromUserId,
                'from_user_name' => $fromUser['name'],
                'rating' => $rating
            ]
        );
    }
    
    public function notifySystemMessage($userId, $title, $message) {
        return $this->createNotification(
            $userId,
            'system',
            $title,
            $message
        );
    }
    
    public function notifyAdminAction($userId, $action, $details) {
        return $this->createNotification(
            $userId,
            'admin_action',
            'Account Update',
            'Your account has been ' . $action . '. ' . $details,
            ['action' => $action, 'details' => $details]
        );
    }
}

// Global notification instance
$notification = new Notification();
?> 