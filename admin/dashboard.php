<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | SkillSwap</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%); min-height: 100vh; }
        .admin-nav { display: flex; gap: 2rem; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px #b3e5fc33; padding: 1.2rem 2rem; margin: 2.5rem auto 2rem auto; max-width: 900px; align-items: center; }
        .admin-nav a { color: #1976d2; font-weight: 600; font-size: 1.1rem; text-decoration: none; padding: 0.5em 1.2em; border-radius: 8px; transition: background 0.15s; }
        .admin-nav a:hover, .admin-nav a.active { background: #eaf6ff; }
        .admin-dashboard-container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 8px 32px #b3e5fc55; padding: 2.5rem 2rem; }
        .admin-dashboard-title { color: #1976d2; font-size: 1.5rem; font-weight: 700; margin-bottom: 2rem; }
        .admin-stats { display: flex; gap: 2rem; flex-wrap: wrap; }
        .admin-stat-card { background: #eaf6ff; border-radius: 14px; box-shadow: 0 2px 8px #b3e5fc22; padding: 1.5rem 2rem; flex: 1 1 180px; text-align: center; margin-bottom: 1.5rem; }
        .admin-stat-number { font-size: 2.1rem; font-weight: 700; color: #1976d2; }
        .admin-stat-label { color: #2196f3; font-weight: 600; margin-top: 0.5rem; font-size: 1.1rem; }
    </style>
</head>
<body>
    <div class="admin-nav">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="swaps.php">Swaps</a>
        <a href="feedback.php">Feedback</a>
        <a href="logout.php" style="margin-left:auto;color:#ff6b6b;">Logout</a>
    </div>
    <div class="admin-dashboard-container">
        <div class="admin-dashboard-title">Admin Dashboard</div>
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-number">--</div>
                <div class="admin-stat-label">Total Users</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-number">--</div>
                <div class="admin-stat-label">Total Swaps</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-number">--</div>
                <div class="admin-stat-label">Pending Swaps</div>
            </div>
            <div class="admin-stat-card">
                <div class="admin-stat-number">--</div>
                <div class="admin-stat-label">Feedback</div>
            </div>
        </div>
        <p style="color:#1976d2;text-align:center;margin-top:2rem;">Welcome, Admin! Use the navigation above to manage the platform.</p>
    </div>
</body>
</html>