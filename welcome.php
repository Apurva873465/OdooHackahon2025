<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome, <?= htmlspecialchars($user['name'] ?? 'User') ?></h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
        <p><strong>Skills Offered:</strong> <?= htmlspecialchars($user['skills_offered'] ?? 'None') ?></p>
        <p><strong>Skills Wanted:</strong> <?= htmlspecialchars($user['skills_wanted'] ?? 'None') ?></p>
        <p><strong>Availability:</strong> <?= htmlspecialchars($user['availability'] ?? 'Not set') ?></p>

        <ul class="dashboard-links">
            <li><a href="profile.php">👤 Profile</a></li>
            <li><a href="dashboard.php">📊 Dashboard</a></li>
            <li><a href="add_skill.php">✏️ Edit Skills</a></li>
            <li><a href="search.php">🔍 Search Skills</a></li>
            <li><a href="requests.php">🔄 Swap Requests</a></li>
            <li><a href="feedback.php">⭐ Feedback</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>
</body>
</html>
