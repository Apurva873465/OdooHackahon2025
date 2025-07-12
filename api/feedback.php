<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'user_feedback':
                    $userId = $_GET['user_id'] ?? 0;
                    
                    if (!$userId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'User ID required']);
                        break;
                    }
                    
                    $feedback = $db->fetchAll("
                        SELECT f.*, u.name as from_user_name
                        FROM feedback f
                        JOIN users u ON f.from_user = u.id
                        WHERE f.to_user = ?
                        ORDER BY f.created_at DESC
                    ", [$userId]);
                    
                    // Calculate average rating
                    $avgRating = $db->fetchOne("
                        SELECT AVG(rating) as avg_rating, COUNT(*) as total_feedback
                        FROM feedback 
                        WHERE to_user = ?
                    ", [$userId]);
                    
                    echo json_encode([
                        'success' => true,
                        'feedback' => $feedback,
                        'average_rating' => round($avgRating['avg_rating'], 1),
                        'total_feedback' => $avgRating['total_feedback']
                    ]);
                    break;
                    
                case 'swap_feedback':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $swapId = $_GET['swap_id'] ?? 0;
                    $userId = $_SESSION['user_id'];
                    
                    // Check if user is part of this swap
                    $swap = $db->fetchOne("
                        SELECT * FROM swaps 
                        WHERE id = ? AND (requester_id = ? OR receiver_id = ?) AND status = 'accepted'
                    ", [$swapId, $userId, $userId]);
                    
                    if (!$swap) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Swap not found or not completed']);
                        break;
                    }
                    
                    // Get existing feedback for this swap
                    $existingFeedback = $db->fetchAll("
                        SELECT * FROM feedback WHERE swap_id = ?
                    ", [$swapId]);
                    
                    echo json_encode(['success' => true, 'feedback' => $existingFeedback]);
                    break;
                    
                case 'my_feedback':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $feedback = $db->fetchAll("
                        SELECT f.*, u.name as to_user_name, s.skill_requested, s.skill_offered
                        FROM feedback f
                        JOIN users u ON f.to_user = u.id
                        JOIN swaps s ON f.swap_id = s.id
                        WHERE f.from_user = ?
                        ORDER BY f.created_at DESC
                    ", [$userId]);
                    
                    echo json_encode(['success' => true, 'feedback' => $feedback]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'submit':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $swapId = $input['swap_id'] ?? 0;
                    $toUserId = $input['to_user_id'] ?? 0;
                    $rating = intval($input['rating'] ?? 0);
                    $comments = $input['comments'] ?? '';
                    $fromUserId = $_SESSION['user_id'];
                    
                    if (empty($swapId) || empty($toUserId) || $rating < 1 || $rating > 5) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
                        break;
                    }
                    
                    // Check if user is part of this swap
                    $swap = $db->fetchOne("
                        SELECT * FROM swaps 
                        WHERE id = ? AND (requester_id = ? OR receiver_id = ?) AND status = 'accepted'
                    ", [$swapId, $fromUserId, $fromUserId]);
                    
                    if (!$swap) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Swap not found or not completed']);
                        break;
                    }
                    
                    // Check if user is giving feedback to the other participant
                    if ($swap['requester_id'] == $fromUserId && $swap['receiver_id'] != $toUserId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid recipient']);
                        break;
                    }
                    
                    if ($swap['receiver_id'] == $fromUserId && $swap['requester_id'] != $toUserId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid recipient']);
                        break;
                    }
                    
                    // Check if feedback already exists
                    $existing = $db->fetchOne("
                        SELECT id FROM feedback 
                        WHERE swap_id = ? AND from_user = ? AND to_user = ?
                    ", [$swapId, $fromUserId, $toUserId]);
                    
                    if ($existing) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Feedback already submitted for this swap']);
                        break;
                    }
                    
                    $feedbackId = $db->insert('feedback', [
                        'swap_id' => $swapId,
                        'from_user' => $fromUserId,
                        'to_user' => $toUserId,
                        'rating' => $rating,
                        'comments' => $comments,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($feedbackId) {
                        echo json_encode(['success' => true, 'feedback_id' => $feedbackId, 'message' => 'Feedback submitted successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
                    }
                    break;
                    
                case 'update':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $feedbackId = $input['feedback_id'] ?? 0;
                    $rating = intval($input['rating'] ?? 0);
                    $comments = $input['comments'] ?? '';
                    $userId = $_SESSION['user_id'];
                    
                    if (empty($feedbackId) || $rating < 1 || $rating > 5) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
                        break;
                    }
                    
                    // Check if user owns this feedback
                    $feedback = $db->fetchOne("
                        SELECT * FROM feedback WHERE id = ? AND from_user = ?
                    ", [$feedbackId, $userId]);
                    
                    if (!$feedback) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Feedback not found']);
                        break;
                    }
                    
                    $result = $db->update('feedback', [
                        'rating' => $rating,
                        'comments' => $comments
                    ], 'id = ?', [$feedbackId]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Feedback updated successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to update feedback']);
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
                    
                    $feedbackId = $_GET['id'] ?? 0;
                    $userId = $_SESSION['user_id'];
                    
                    // Check if user owns this feedback
                    $feedback = $db->fetchOne("
                        SELECT * FROM feedback WHERE id = ? AND from_user = ?
                    ", [$feedbackId, $userId]);
                    
                    if (!$feedback) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Feedback not found']);
                        break;
                    }
                    
                    $result = $db->delete('feedback', 'id = ?', [$feedbackId]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to delete feedback']);
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