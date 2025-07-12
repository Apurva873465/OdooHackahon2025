<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';
$swaps = $conn->query("SELECT s.*, u1.name as requester_name, u2.name as receiver_name FROM swaps s JOIN users u1 ON s.requester_id = u1.id JOIN users u2 ON s.receiver_id = u2.id ORDER BY s.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Swaps | SkillSwap</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%); min-height: 100vh; }
        .admin-nav { display: flex; gap: 2rem; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px #b3e5fc33; padding: 1.2rem 2rem; margin: 2.5rem auto 2rem auto; max-width: 900px; align-items: center; }
        .admin-nav a { color: #1976d2; font-weight: 600; font-size: 1.1rem; text-decoration: none; padding: 0.5em 1.2em; border-radius: 8px; transition: background 0.15s; }
        .admin-nav a:hover, .admin-nav a.active { background: #eaf6ff; }
        .admin-swaps-container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 8px 32px #b3e5fc55; padding: 2.5rem 2rem; }
        .admin-swaps-title { color: #1976d2; font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem; }
        .admin-swaps-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .admin-swaps-table th, .admin-swaps-table td { padding: 0.8em 1em; border-bottom: 1px solid #eaf6ff; text-align: left; }
        .admin-swaps-table th { color: #1976d2; font-weight: 700; background: #eaf6ff; }
        .admin-swaps-table td { color: #1976d2; }
        .admin-swap-actions a { margin-right: 0.7em; color: #2196f3; font-weight: 600; text-decoration: none; }
        .admin-swap-actions a.delete { color: #ff6b6b; }
    </style>
</head>
<body>
    <div class="admin-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="swaps.php" class="active">Swaps</a>
        <a href="feedback.php">Feedback</a>
        <a href="logout.php" style="margin-left:auto;color:#ff6b6b;">Logout</a>
    </div>
    <div class="admin-swaps-container">
        <div class="admin-swaps-title">Manage Swaps</div>
        <table class="admin-swaps-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Requester</th>
                    <th>Receiver</th>
                    <th>Requested Skill</th>
                    <th>Offered Skill</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($swaps && $swaps->num_rows): foreach ($swaps as $swap): ?>
                <tr>
                    <td><?= $swap['id'] ?></td>
                    <td><?= htmlspecialchars($swap['requester_name']) ?></td>
                    <td><?= htmlspecialchars($swap['receiver_name']) ?></td>
                    <td><?= htmlspecialchars($swap['skill_requested']) ?></td>
                    <td><?= htmlspecialchars($swap['skill_offered']) ?></td>
                    <td><?= htmlspecialchars($swap['status']) ?></td>
                    <td><?= date('Y-m-d', strtotime($swap['created_at'])) ?></td>
                    <td class="admin-swap-actions"><a href="#" class="edit">Edit</a><a href="#" class="delete">Delete</a></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="8">No swaps found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 