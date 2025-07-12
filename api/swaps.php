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
                case 'my_requests':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $type = $_GET['type'] ?? 'sent'; // sent or received
                    
                    if ($type === 'sent') {
                        $sql = "SELECT s.*, u.name as receiver_name, u.email as receiver_email 
                               FROM swaps s 
                               JOIN users u ON s.receiver_id = u.id 
                               WHERE s.requester_id = ? 
                               ORDER BY s.created_at DESC";
                    } else {
                        $sql = "SELECT s.*, u.name as requester_name, u.email as requester_email 
                               FROM swaps s 
                               JOIN users u ON s.requester_id = u.id 
                               WHERE s.receiver_id = ? 
                               ORDER BY s.created_at DESC";
                    }
                    
                    $swaps = $db->fetchAll($sql, [$userId]);
                    echo json_encode(['success' => true, 'swaps' => $swaps]);
                    break;
                    
                case 'swap_details':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $swapId = $_GET['id'] ?? 0;
                    $userId = $_SESSION['user_id'];
                    
                    $swap = $db->fetchOne("
                        SELECT s.*, 
                               r.name as requester_name, r.email as requester_email,
                               rc.name as receiver_name, rc.email as receiver_email
                        FROM swaps s
                        JOIN users r ON s.requester_id = r.id
                        JOIN users rc ON s.receiver_id = rc.id
                        WHERE s.id = ? AND (s.requester_id = ? OR s.receiver_id = ?)
                    ", [$swapId, $userId, $userId]);
                    
                    if (!$swap) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Swap not found']);
                        break;
                    }
                    
                    echo json_encode(['success' => true, 'swap' => $swap]);
                    break;
                    
                case 'pending_count':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $count = $db->fetchOne("
                        SELECT COUNT(*) as count 
                        FROM swaps 
                        WHERE receiver_id = ? AND status = 'pending'
                    ", [$userId]);
                    
                    echo json_encode(['success' => true, 'count' => $count['count']]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'create_request':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $requesterId = $_SESSION['user_id'];
                    $receiverId = $input['receiver_id'] ?? 0;
                    $skillRequested = $input['skill_requested'] ?? '';
                    $skillOffered = $input['skill_offered'] ?? '';
                    $message = $input['message'] ?? '';
                    
                    if (empty($receiverId) || empty($skillRequested) || empty($skillOffered)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                        break;
                    }
                    
                    if ($requesterId == $receiverId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Cannot request swap with yourself']);
                        break;
                    }
                    
                    // Check if receiver exists
                    $receiver = $db->fetchOne("SELECT id FROM users WHERE id = ?", [$receiverId]);
                    if (!$receiver) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Receiver not found']);
                        break;
                    }
                    
                    // Check if there's already a pending request
                    $existing = $db->fetchOne("
                        SELECT id FROM swaps 
                        WHERE requester_id = ? AND receiver_id = ? AND status = 'pending'
                    ", [$requesterId, $receiverId]);
                    
                    if ($existing) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'You already have a pending request with this user']);
                        break;
                    }
                    
                    $swapId = $db->insert('swaps', [
                        'requester_id' => $requesterId,
                        'receiver_id' => $receiverId,
                        'skill_requested' => $skillRequested,
                        'skill_offered' => $skillOffered,
                        'message' => $message,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($swapId) {
                        echo json_encode(['success' => true, 'swap_id' => $swapId, 'message' => 'Swap request created successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to create swap request']);
                    }
                    break;
                    
                case 'respond':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $swapId = $input['swap_id'] ?? 0;
                    $response = $input['response'] ?? ''; // 'accept' or 'reject'
                    $userId = $_SESSION['user_id'];
                    
                    if (!in_array($response, ['accept', 'reject'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid response']);
                        break;
                    }
                    
                    // Check if user is the receiver of this swap
                    $swap = $db->fetchOne("
                        SELECT * FROM swaps 
                        WHERE id = ? AND receiver_id = ? AND status = 'pending'
                    ", [$swapId, $userId]);
                    
                    if (!$swap) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Swap not found or not pending']);
                        break;
                    }
                    
                    $status = ($response === 'accept') ? 'accepted' : 'rejected';
                    $result = $db->update('swaps', ['status' => $status], 'id = ?', [$swapId]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Swap request ' . $response . 'ed successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to update swap status']);
                    }
                    break;
                    
                case 'cancel':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $swapId = $input['swap_id'] ?? 0;
                    $userId = $_SESSION['user_id'];
                    
                    // Check if user is the requester of this swap
                    $swap = $db->fetchOne("
                        SELECT * FROM swaps 
                        WHERE id = ? AND requester_id = ? AND status = 'pending'
                    ", [$swapId, $userId]);
                    
                    if (!$swap) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Swap not found or cannot be cancelled']);
                        break;
                    }
                    
                    $result = $db->delete('swaps', 'id = ?', [$swapId]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Swap request cancelled successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to cancel swap request']);
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