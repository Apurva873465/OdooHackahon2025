<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';
$feedback = $conn->query("SELECT f.*, u.name as user_name FROM feedback f JOIN users u ON f.from_user = u.id ORDER BY f.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Feedback | SkillSwap</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%); min-height: 100vh; }
        .admin-nav { display: flex; gap: 2rem; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px #b3e5fc33; padding: 1.2rem 2rem; margin: 2.5rem auto 2rem auto; max-width: 900px; align-items: center; }
        .admin-nav a { color: #1976d2; font-weight: 600; font-size: 1.1rem; text-decoration: none; padding: 0.5em 1.2em; border-radius: 8px; transition: background 0.15s; }
        .admin-nav a:hover, .admin-nav a.active { background: #eaf6ff; }
        .admin-feedback-container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 8px 32px #b3e5fc55; padding: 2.5rem 2rem; }
        .admin-feedback-title { color: #1976d2; font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem; }
        .admin-feedback-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .admin-feedback-table th, .admin-feedback-table td { padding: 0.8em 1em; border-bottom: 1px solid #eaf6ff; text-align: left; }
        .admin-feedback-table th { color: #1976d2; font-weight: 700; background: #eaf6ff; }
        .admin-feedback-table td { color: #1976d2; }
        .admin-feedback-actions a { margin-right: 0.7em; color: #ff6b6b; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <div class="admin-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="swaps.php">Swaps</a>
        <a href="feedback.php" class="active">Feedback</a>
        <a href="logout.php" style="margin-left:auto;color:#ff6b6b;">Logout</a>
    </div>
    <div class="admin-feedback-container">
        <div class="admin-feedback-title">Feedback & Reports</div>
        <table class="admin-feedback-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Swap</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($feedback && $feedback->num_rows): foreach ($feedback as $fb): ?>
                <tr>
                    <td><?= $fb['id'] ?></td>
                    <td><?= htmlspecialchars($fb['user_name']) ?></td>
                    <td><?= $fb['swap_id'] ?></td>
                    <td><?= $fb['rating'] ?></td>
                    <td><?= htmlspecialchars($fb['comment']) ?></td>
                    <td><?= date('Y-m-d', strtotime($fb['created_at'])) ?></td>
                    <td class="admin-feedback-actions"><a href="#" class="delete">Delete</a></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7">No feedback found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 