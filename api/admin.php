<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = Database::getInstance();

// Require admin access
$auth->requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'dashboard_stats':
                    // Get system statistics
                    $stats = $db->fetchOne("
                        SELECT 
                            (SELECT COUNT(*) FROM users) as total_users,
                            (SELECT COUNT(*) FROM users WHERE role = 'admin') as admin_users,
                            (SELECT COUNT(*) FROM swaps) as total_swaps,
                            (SELECT COUNT(*) FROM swaps WHERE status = 'pending') as pending_swaps,
                            (SELECT COUNT(*) FROM swaps WHERE status = 'accepted') as completed_swaps,
                            (SELECT COUNT(*) FROM feedback) as total_feedback,
                            (SELECT AVG(rating) FROM feedback) as avg_rating
                    ");
                    
                    // Get recent activity
                    $recentSwaps = $db->fetchAll("
                        SELECT s.*, 
                               r.name as requester_name,
                               rc.name as receiver_name
                        FROM swaps s
                        JOIN users r ON s.requester_id = r.id
                        JOIN users rc ON s.receiver_id = rc.id
                        ORDER BY s.created_at DESC
                        LIMIT 10
                    ");
                    
                    // Get top users by completed swaps
                    $topUsers = $db->fetchAll("
                        SELECT u.name, u.email,
                               COUNT(s.id) as completed_swaps,
                               AVG(f.rating) as avg_rating
                        FROM users u
                        LEFT JOIN swaps s ON (u.id = s.requester_id OR u.id = s.receiver_id) AND s.status = 'accepted'
                        LEFT JOIN feedback f ON u.id = f.to_user
                        WHERE u.role = 'user'
                        GROUP BY u.id
                        ORDER BY completed_swaps DESC, avg_rating DESC
                        LIMIT 10
                    ");
                    
                    echo json_encode([
                        'success' => true,
                        'stats' => $stats,
                        'recent_swaps' => $recentSwaps,
                        'top_users' => $topUsers
                    ]);
                    break;
                    
                case 'users':
                    $page = max(1, intval($_GET['page'] ?? 1));
                    $limit = 20;
                    $offset = ($page - 1) * $limit;
                    $search = $_GET['search'] ?? '';
                    $role = $_GET['role'] ?? '';
                    
                    $whereConditions = ['1=1'];
                    $params = [];
                    
                    if (!empty($search)) {
                        $whereConditions[] = "(name LIKE ? OR email LIKE ?)";
                        $searchTerm = "%$search%";
                        $params = array_merge($params, [$searchTerm, $searchTerm]);
                    }
                    
                    if (!empty($role)) {
                        $whereConditions[] = "role = ?";
                        $params[] = $role;
                    }
                    
                    $whereClause = implode(' AND ', $whereConditions);
                    
                    $sql = "SELECT id, name, email, location, role, created_at, last_login 
                           FROM users WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
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
                    
                case 'user_details':
                    $userId = $_GET['id'] ?? 0;
                    
                    if (!$userId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'User ID required']);
                        break;
                    }
                    
                    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
                    
                    if (!$user) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        break;
                    }
                    
                    // Get user statistics
                    $userStats = $db->fetchOne("
                        SELECT 
                            (SELECT COUNT(*) FROM swaps WHERE requester_id = ?) as requests_sent,
                            (SELECT COUNT(*) FROM swaps WHERE receiver_id = ?) as requests_received,
                            (SELECT COUNT(*) FROM swaps WHERE (requester_id = ? OR receiver_id = ?) AND status = 'accepted') as completed_swaps,
                            (SELECT COUNT(*) FROM feedback WHERE to_user = ?) as feedback_received,
                            (SELECT AVG(rating) FROM feedback WHERE to_user = ?) as avg_rating
                    ", [$userId, $userId, $userId, $userId, $userId, $userId]);
                    
                    // Get recent swaps
                    $recentSwaps = $db->fetchAll("
                        SELECT s.*, 
                               r.name as requester_name,
                               rc.name as receiver_name
                        FROM swaps s
                        JOIN users r ON s.requester_id = r.id
                        JOIN users rc ON s.receiver_id = rc.id
                        WHERE s.requester_id = ? OR s.receiver_id = ?
                        ORDER BY s.created_at DESC
                        LIMIT 10
                    ", [$userId, $userId]);
                    
                    echo json_encode([
                        'success' => true,
                        'user' => $user,
                        'stats' => $userStats,
                        'recent_swaps' => $recentSwaps
                    ]);
                    break;
                    
                case 'swaps':
                    $page = max(1, intval($_GET['page'] ?? 1));
                    $limit = 20;
                    $offset = ($page - 1) * $limit;
                    $status = $_GET['status'] ?? '';
                    
                    $whereConditions = ['1=1'];
                    $params = [];
                    
                    if (!empty($status)) {
                        $whereConditions[] = "status = ?";
                        $params[] = $status;
                    }
                    
                    $whereClause = implode(' AND ', $whereConditions);
                    
                    $sql = "SELECT s.*, 
                                   r.name as requester_name, r.email as requester_email,
                                   rc.name as receiver_name, rc.email as receiver_email
                           FROM swaps s
                           JOIN users r ON s.requester_id = r.id
                           JOIN users rc ON s.receiver_id = rc.id
                           WHERE $whereClause 
                           ORDER BY s.created_at DESC 
                           LIMIT ? OFFSET ?";
                    $params[] = $limit;
                    $params[] = $offset;
                    
                    $swaps = $db->fetchAll($sql, $params);
                    
                    // Get total count
                    $countSql = "SELECT COUNT(*) as total FROM swaps WHERE $whereClause";
                    $total = $db->fetchOne($countSql, array_slice($params, 0, -2));
                    
                    echo json_encode([
                        'success' => true,
                        'swaps' => $swaps,
                        'total' => $total['total'],
                        'page' => $page,
                        'total_pages' => ceil($total['total'] / $limit)
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'update_user':
                    $input = json_decode(file_get_contents('php://input'), true);
                    $userId = $input['user_id'] ?? 0;
                    $role = $input['role'] ?? '';
                    $status = $input['status'] ?? ''; // active, suspended
                    
                    if (!$userId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'User ID required']);
                        break;
                    }
                    
                    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
                    if (!$user) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        break;
                    }
                    
                    $updateData = [];
                    if (!empty($role) && in_array($role, ['user', 'admin'])) {
                        $updateData['role'] = $role;
                    }
                    
                    if (!empty($status)) {
                        $updateData['status'] = $status;
                    }
                    
                    if (empty($updateData)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'No valid data to update']);
                        break;
                    }
                    
                    $result = $db->update('users', $updateData, 'id = ?', [$userId]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
                    }
                    break;
                    
                case 'delete_user':
                    $input = json_decode(file_get_contents('php://input'), true);
                    $userId = $input['user_id'] ?? 0;
                    
                    if (!$userId) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'User ID required']);
                        break;
                    }
                    
                    // Check if user exists and is not an admin
                    $user = $db->fetchOne("SELECT role FROM users WHERE id = ?", [$userId]);
                    if (!$user) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        break;
                    }
                    
                    if ($user['role'] === 'admin') {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Cannot delete admin users']);
                        break;
                    }
                    
                    // Start transaction
                    $db->beginTransaction();
                    
                    try {
                        // Delete related data
                        $db->delete('feedback', 'from_user = ? OR to_user = ?', [$userId, $userId]);
                        $db->delete('swaps', 'requester_id = ? OR receiver_id = ?', [$userId, $userId]);
                        $db->delete('users', 'id = ?', [$userId]);
                        
                        $db->commit();
                        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                    } catch (Exception $e) {
                        $db->rollback();
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
                    }
                    break;
                    
                case 'update_swap':
                    $input = json_decode(file_get_contents('php://input'), true);
                    $swapId = $input['swap_id'] ?? 0;
                    $status = $input['status'] ?? '';
                    
                    if (!$swapId || !in_array($status, ['pending', 'accepted', 'rejected'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid input']);
                        break;
                    }
                    
                    $result = $db->update('swaps', ['status' => $status], 'id = ?', [$swapId]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'Swap status updated successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Failed to update swap status']);
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