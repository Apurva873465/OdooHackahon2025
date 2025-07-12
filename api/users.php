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
                case 'search':
                    $query = $_GET['q'] ?? '';
                    $location = $_GET['location'] ?? '';
                    $skill = $_GET['skill'] ?? '';
                    $page = max(1, intval($_GET['page'] ?? 1));
                    $limit = 10;
                    $offset = ($page - 1) * $limit;
                    
                    $whereConditions = ['privacy = "public"'];
                    $params = [];
                    
                    if (!empty($query)) {
                        $whereConditions[] = "(name LIKE ? OR skills_offered LIKE ? OR skills_wanted LIKE ?)";
                        $searchTerm = "%$query%";
                        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                    }
                    
                    if (!empty($location)) {
                        $whereConditions[] = "location LIKE ?";
                        $params[] = "%$location%";
                    }
                    
                    if (!empty($skill)) {
                        $whereConditions[] = "(skills_offered LIKE ? OR skills_wanted LIKE ?)";
                        $skillTerm = "%$skill%";
                        $params = array_merge($params, [$skillTerm, $skillTerm]);
                    }
                    
                    $whereClause = implode(' AND ', $whereConditions);
                    
                    $sql = "SELECT id, name, email, location, profile_photo, skills_offered, skills_wanted, availability 
                           FROM users WHERE $whereClause ORDER BY name LIMIT ? OFFSET ?";
                    $params[] = $limit;
                    $params[] = $offset;
                    
                    $users = $db->fetchAll($sql, $params);
                    
                    // Get total count
                    $countSql = "SELECT COUNT(*) as total FROM users WHERE $whereClause";
                    $total = $db->fetchOne($countSql, array_slice($params, 0, -2));
                    
                    echo json_encode([
                        'success' => true,
                        'users' => $users,
                        'total' => $total['total'],
                        'page' => $page,
                        'total_pages' => ceil($total['total'] / $limit)
                    ]);
                    break;
                    
                case 'profile':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $userId = $_GET['id'] ?? $_SESSION['user_id'];
                    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
                    
                    if (!$user) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        break;
                    }
                    
                    // Remove sensitive data
                    unset($user['password']);
                    
                    echo json_encode(['success' => true, 'user' => $user]);
                    break;
                    
                case 'stats':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $userId = $_SESSION['user_id'];
                    
                    // Get user statistics
                    $stats = $db->fetchOne("
                        SELECT 
                            (SELECT COUNT(*) FROM swaps WHERE requester_id = ?) as requests_sent,
                            (SELECT COUNT(*) FROM swaps WHERE receiver_id = ?) as requests_received,
                            (SELECT COUNT(*) FROM swaps WHERE (requester_id = ? OR receiver_id = ?) AND status = 'accepted') as completed_swaps,
                            (SELECT COUNT(*) FROM feedback WHERE to_user = ?) as feedback_received
                    ", [$userId, $userId, $userId, $userId, $userId]);
                    
                    echo json_encode(['success' => true, 'stats' => $stats]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'update_profile':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $userId = $_SESSION['user_id'];
                    
                    $updateData = [];
                    $allowedFields = ['name', 'location', 'skills_offered', 'skills_wanted', 'availability', 'privacy'];
                    
                    foreach ($allowedFields as $field) {
                        if (isset($input[$field])) {
                            $updateData[$field] = $input[$field];
                        }
                    }
                    
                    if (empty($updateData)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'No valid data to update']);
                        break;
                    }
                    
                    $result = $auth->updateProfile($userId, $updateData);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
                    }
                    break;
                    
                case 'change_password':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    $currentPassword = $input['current_password'] ?? '';
                    $newPassword = $input['new_password'] ?? '';
                    
                    if (empty($currentPassword) || empty($newPassword)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Both passwords are required']);
                        break;
                    }
                    
                    if (strlen($newPassword) < 6) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                        break;
                    }
                    
                    $result = $auth->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                    } else {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    }
                    break;
                    
                case 'upload_photo':
                    if (!$auth->isLoggedIn()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        break;
                    }
                    
                    if (!isset($_FILES['photo'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                        break;
                    }
                    
                    $result = $auth->uploadProfilePhoto($_SESSION['user_id'], $_FILES['photo']);
                    echo json_encode($result);
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