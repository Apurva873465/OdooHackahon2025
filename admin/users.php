<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db.php';
$users = $conn->query("SELECT id, name, email, role FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Users | SkillSwap</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%); min-height: 100vh; }
        .admin-nav { display: flex; gap: 2rem; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px #b3e5fc33; padding: 1.2rem 2rem; margin: 2.5rem auto 2rem auto; max-width: 900px; align-items: center; }
        .admin-nav a { color: #1976d2; font-weight: 600; font-size: 1.1rem; text-decoration: none; padding: 0.5em 1.2em; border-radius: 8px; transition: background 0.15s; }
        .admin-nav a:hover, .admin-nav a.active { background: #eaf6ff; }
        .admin-users-container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 8px 32px #b3e5fc55; padding: 2.5rem 2rem; }
        .admin-users-title { color: #1976d2; font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem; }
        .admin-search-bar { margin-bottom: 1.5rem; }
        .admin-search-input { width: 100%; padding: 0.7em 1.1em; border-radius: 10px; border: 1.5px solid #b3e5fc; font-size: 1.07em; background: #f8faff; color: #1976d2; }
        .admin-users-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .admin-users-table th, .admin-users-table td { padding: 0.8em 1em; border-bottom: 1px solid #eaf6ff; text-align: left; }
        .admin-users-table th { color: #1976d2; font-weight: 700; background: #eaf6ff; }
        .admin-users-table td { color: #1976d2; }
        .admin-user-actions a { margin-right: 0.7em; color: #2196f3; font-weight: 600; text-decoration: none; }
        .admin-user-actions a.delete { color: #ff6b6b; }
        .admin-user-actions a.promote { color: #43e97b; }
    </style>
</head>
<body>
    <div class="admin-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php" class="active">Users</a>
        <a href="swaps.php">Swaps</a>
        <a href="feedback.php">Feedback</a>
        <a href="logout.php" style="margin-left:auto;color:#ff6b6b;">Logout</a>
    </div>
    <div class="admin-users-container">
        <div class="admin-users-title">Manage Users</div>
        <form class="admin-search-bar">
            <input type="text" class="admin-search-input" placeholder="Search users by name, email, or ID...">
        </form>
        <table class="admin-users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users && $users->num_rows): foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td class="admin-user-actions">
                        <a href="#" class="edit">Edit</a>
                        <a href="#" class="delete">Delete</a>
                        <a href="#" class="promote"><?= $user['role'] === 'admin' ? 'Demote' : 'Promote' ?></a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>