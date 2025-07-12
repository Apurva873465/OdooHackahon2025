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

function getInitials($name) {
    $words = explode(' ', trim($name));
    $ini = '';
    foreach ($words as $w) {
        if ($w) $ini .= strtoupper($w[0]);
    }
    return substr($ini, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2196f3 0%, #4fc3f7 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dashboard-card {
            background: rgba(255,255,255,0.97);
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 rgba(33,150,243,0.15);
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 400px;
            width: 100%;
            margin: 2rem auto;
            color: #1976d2;
            position: relative;
            animation: floatIn 0.8s cubic-bezier(.39,.575,.56,1.000);
        }
        .profile-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            box-shadow: 0 2px 12px #2196f344;
        }
        .dashboard-card h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .dashboard-card p {
            color: #1976d2;
            font-size: 1.05rem;
            margin: 0.2rem 0 0.7rem 0;
            text-align: center;
        }
        .dashboard-card strong {
            color: #1565c0;
        }
        .skills-list {
            margin: 0.2rem 0 0.7rem 0;
            text-align: center;
        }
        .skill-tag {
            display: inline-block;
            background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 6px;
            padding: 0.3em 0.9em;
            margin: 0 0.2em 0.3em 0;
            box-shadow: 0 2px 8px 0 #2196f322;
            letter-spacing: 0.5px;
        }
        .dashboard-links {
            list-style: none;
            padding: 0;
            margin: 2rem 0 0 0;
        }
        .dashboard-links li {
            margin-bottom: 1.1rem;
        }
        .dashboard-links a {
            display: flex;
            align-items: center;
            font-size: 1.15rem;
            font-weight: 600;
            color: #1976d2;
            text-decoration: none;
            padding: 0.7rem 1.2rem;
            border-radius: 10px;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
        }
        .dashboard-links a:hover {
            background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            box-shadow: 0 2px 8px #2196f322;
        }
        @media (max-width: 500px) {
            .dashboard-card { padding: 1.2rem 0.5rem; }
        }
        @keyframes floatIn {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>
    <div class="dashboard-card fade-in">
        <div class="profile-avatar"><?= htmlspecialchars(getInitials($user['name'] ?? 'U')) ?></div>
        <h2>Welcome, <?= htmlspecialchars($user['name'] ?? 'User') ?></h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
        <div class="skills-list">
            <strong>Skills Offered:</strong>
            <?php foreach (explode(',', $user['skills_offered'] ?? '') as $skill): if (trim($skill)): ?>
                <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
            <?php endif; endforeach; ?>
        </div>
        <div class="skills-list">
            <strong>Skills Wanted:</strong>
            <?php foreach (explode(',', $user['skills_wanted'] ?? '') as $skill): if (trim($skill)): ?>
                <span class="skill-tag" style="background:linear-gradient(90deg,#4fc3f7 0%,#2196f3 100%)"><?= htmlspecialchars(trim($skill)) ?></span>
            <?php endif; endforeach; ?>
        </div>
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
