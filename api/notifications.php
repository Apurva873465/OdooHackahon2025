<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/notifications.php';

header('Content-Type: application/json');

$auth = new Auth();
$notification = new Notification();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $page = max(1, intval($_GET['page'] ?? 1));
                    $limit = 20;
                    $offset = ($page - 1) * $limit;
                    $userId = $_SESSION['user_id'];
                    
                    $notifications = $notification->getNotifications($userId, $limit, $offset);
                    
                    echo json_encode([
                        'success' => true,
                        'notifications' => $notifications,
                        'page' => $page
                    ]);
                    break;
                    
                case 'unread_count':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $count = $notification->getUnreadCount($userId);
                    
                    echo json_encode(['success' => true, 'count' => $count]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'mark_read':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $notificationId = $input['notification_id'] ?? 0;
                    $userId = $_SESSION['user_id'];
                    
                    if (!$notificationId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
                        break;
                    }
                    
                    $result = $notification->markAsRead($notificationId, $userId);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
                    }
                    break;
                    
                case 'mark_all_read':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $result = $notification->markAllAsRead($userId);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'DELETE':
            switch ($action) {
                case 'delete':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $notificationId = $_GET['id'] ?? 0;
                    $userId = $_SESSION['user_id'];
                    
                    if (!$notificationId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
                        break;
                    }
                    
                    $result = $notification->deleteNotification($notificationId, $userId);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Notification deleted']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 