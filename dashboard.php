<?php
require_once 'includes/auth.php';
require_once 'includes/database.php';

// Ensure user is logged in
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

// Fetch dashboard stats
$db = Database::getInstance();
$user_id = $user['id'];

// Total users
$totalMembers = $db->fetchOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0;
// Completed swaps
$completedSwaps = $db->fetchOne("SELECT COUNT(*) as c FROM swaps WHERE status = 'accepted'")['c'] ?? 0;
// Pending requests for this user
$pendingRequests = $db->fetchOne("SELECT COUNT(*) as c FROM swaps WHERE receiver_id = ? AND status = 'pending'", [$user_id])['c'] ?? 0;
// Average rating for this user
$avgRatingRow = $db->fetchOne("SELECT AVG(rating) as avg FROM feedback WHERE to_user = ?", [$user_id]);
$avgRating = $avgRatingRow && $avgRatingRow['avg'] ? round($avgRatingRow['avg'], 1) : 'N/A';
// Skills offered
$skillsOffered = $user['skills_offered'] ? count(explode(',', $user['skills_offered'])) : 0;
// Fetch recent swaps/feedback for activity feed
$recentSwaps = $db->fetchAll("SELECT s.*, u1.name as requester_name, u1.profile_photo as requester_photo, u2.name as receiver_name, u2.profile_photo as receiver_photo FROM swaps s JOIN users u1 ON s.requester_id = u1.id JOIN users u2 ON s.receiver_id = u2.id WHERE s.requester_id = ? OR s.receiver_id = ? ORDER BY s.updated_at DESC LIMIT 3", [$user_id, $user_id]);
$recentFeedback = $db->fetchAll("SELECT * FROM feedback WHERE to_user = ? OR from_user = ? ORDER BY created_at DESC LIMIT 3", [$user_id, $user_id]);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SkillSwap</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%);
            min-height: 100vh;
        }
        .dashboard-main {
            display: flex;
            gap: 2.5rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 1rem 1rem 1rem;
        }
        .profile-summary {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 24px 0 #b3e5fc55;
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            min-width: 260px;
            max-width: 320px;
            flex: 0 0 300px;
            text-align: center;
            align-self: flex-start;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-size: 2.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            box-shadow: 0 2px 12px #2196f344;
            overflow: hidden;
        }
        .profile-avatar img {
            width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;
        }
        .profile-summary h3 {
            color: #1976d2;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        .profile-summary .email {
            color: #1976d2;
            font-size: 1rem;
            margin-bottom: 0.7rem;
        }
        .profile-summary .skills-list {
            margin: 0.2rem 0 0.7rem 0;
        }
        .profile-summary .skill-tag {
            display: inline-block;
            background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-size: 0.92rem;
            font-weight: 600;
            border-radius: 6px;
            padding: 0.2em 0.7em;
            margin: 0 0.15em 0.3em 0;
            box-shadow: 0 2px 8px 0 #2196f322;
            letter-spacing: 0.5px;
        }
        .dashboard-content {
            flex: 1 1 0%;
            min-width: 0;
        }
        .card, .stat-card, .activity-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 24px 0 #b3e5fc55;
            padding: 2rem 2rem 1.5rem 2rem;
            margin-bottom: 2rem;
        }
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            text-align: center;
            padding: 1.5rem 1rem;
            box-shadow: 0 2px 12px #b3e5fc33;
            border-radius: 18px;
            background: #fff;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 6px 24px #2196f344;
            transform: translateY(-2px) scale(1.02);
        }
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1976d2;
        }
        .stat-label {
            color: #2196f3;
            font-weight: 600;
            margin-top: 0.5rem;
            letter-spacing: 0.5px;
        }
        .card-header {
            border-bottom: 1px solid #eaf6ff;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1976d2;
            margin: 0;
        }
        .card-body {
            color: #1976d2;
        }
        .d-flex { display: flex; gap: 1rem; }
        .btn, .btn-primary, .btn-outline {
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 0.7rem 1.7rem;
            margin-bottom: 0;
        }
        .btn-primary {
            background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            border: none;
            box-shadow: 0 2px 8px #2196f322;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #1565c0 0%, #2196f3 100%);
        }
        .btn-outline {
            background: #eaf6ff;
            color: #1976d2;
            border: 2px solid #b3e5fc;
        }
        .btn-outline:hover {
            background: #b3e5fc;
            color: #1565c0;
        }
        .activity-card {
            margin-top: 0;
        }
        .activity-card h4 {
            color: #1976d2;
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .activity-list li {
            margin-bottom: 1.1rem;
            color: #1976d2;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .activity-list .icon {
            font-size: 1.2rem;
            color: #2196f3;
        }
        @media (max-width: 900px) {
            .dashboard-main { flex-direction: column; gap: 0; }
            .profile-summary { max-width: 100%; margin-bottom: 2rem; }
        }
        /* Add or override chat button styles for avatar overlay */
        .chat-btn-avatar {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #2196f3 0%, #4fc3f7 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px #2196f322;
            border: 2px solid #fff;
            z-index: 2;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .chat-btn-avatar:hover {
            box-shadow: 0 4px 16px #2196f344;
            transform: scale(1.08);
        }
        .chat-btn-avatar i {
            color: #fff;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="#" class="navbar-brand">
                <i class="fas fa-exchange-alt"></i> SkillSwap
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                <li><a href="search.php" class="nav-link">Search</a></li>
                <li><a href="requests.php" class="nav-link">My Requests</a></li>
                <li><a href="profile.php" class="nav-link">Profile</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-main">
        <div class="profile-summary fade-in">
            <div class="profile-avatar" style="position:relative;">
                <?php if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])): ?>
                    <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile Photo">
                <?php else: ?>
                    <?= htmlspecialchars(getInitials($user['name'] ?? 'U')) ?>
                <?php endif; ?>
                <a href="chat.php" title="Open Chat" class="chat-btn-avatar">
                    <i class="fas fa-comments"></i>
                </a>
            </div>
            <!-- Large Chat Button Below Avatar -->
            <a href="chat.php" class="btn btn-primary w-100" style="margin:1.1rem 0 0.7rem 0;display:flex;align-items:center;justify-content:center;gap:0.7em;font-size:1.13rem;">
                <i class="fas fa-comments"></i> Open Chat
            </a>
            <h3><?= htmlspecialchars($user['name'] ?? '') ?></h3>
            <div class="email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            <div class="skills-list">
                <?php foreach (explode(',', $user['skills_offered'] ?? '') as $skill): if (trim($skill)): ?>
                    <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <div class="dashboard-content">
            <div class="card fade-in">
                <div class="card-header">
                    <h1 class="card-title">Welcome, <?= htmlspecialchars($user['name']); ?>!</h1>
                </div>
                <div class="card-body">
                    <p>Connect with people who have the skills you need and share your expertise in return.</p>
                    <div class="d-flex mt-3">
                        <a href="search.php" class="btn btn-primary">Start Swapping</a>
                        <a href="profile.php" class="btn btn-outline">Edit Profile</a>
                    </div>
                </div>
            </div>
            <div class="dashboard-stats">
                <a href="requests.php#completed" style="text-decoration:none;">
                    <div class="stat-card fade-in" tabindex="0">
                        <div class="stat-number"><?= $completedSwaps; ?></div>
                        <div class="stat-label">Completed Swaps</div>
                    </div>
                </a>
                <a href="requests.php#pending" style="text-decoration:none;">
                    <div class="stat-card fade-in" tabindex="0">
                        <div class="stat-number"><?= $pendingRequests; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </a>
                <a href="profile.php#ratings" style="text-decoration:none;">
                    <div class="stat-card fade-in" tabindex="0">
                        <div class="stat-number"><?= $avgRating; ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </a>
                <a href="profile.php#skills" style="text-decoration:none;">
                    <div class="stat-card fade-in" tabindex="0">
                        <div class="stat-number"><?= $skillsOffered; ?></div>
                        <div class="stat-label">Skills Offered</div>
                    </div>
                </a>
            </div>
            <div class="activity-card fade-in">
                <h4>Recent Activity</h4>
                <ul class="activity-list">
                    <?php if ($recentSwaps): foreach ($recentSwaps as $swap): ?>
                        <?php
                        $otherName = $swap['requester_id'] == $user_id ? $swap['receiver_name'] : $swap['requester_name'];
                        $otherPhoto = $swap['requester_id'] == $user_id ? $swap['receiver_photo'] : $swap['requester_photo'];
                        ?>
                        <li>
                            <span class="icon">🔄</span>
                            <?php if (!empty($otherPhoto) && file_exists($otherPhoto)): ?>
                                <img src="<?= htmlspecialchars($otherPhoto) ?>" alt="<?= htmlspecialchars($otherName) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:0.5em;vertical-align:middle;">
                            <?php else: ?>
                                <span style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2196f3 0%,#4fc3f7 100%);color:#fff;font-weight:700;border-radius:50%;font-size:1rem;margin-right:0.5em;vertical-align:middle;">
                                    <?= htmlspecialchars(getInitials($otherName)) ?>
                                </span>
                            <?php endif; ?>
                            Swap with <?= htmlspecialchars($otherName) ?> (<?= htmlspecialchars($swap['status']) ?>)
                        </li>
                    <?php endforeach; endif; ?>
                    <?php if ($recentFeedback): foreach ($recentFeedback as $fb): ?>
                        <li><span class="icon">⭐</span>Feedback: <?= htmlspecialchars($fb['rating']) ?>/5</li>
                    <?php endforeach; endif; ?>
                    <?php if (!$recentSwaps && !$recentFeedback): ?>
                        <li>No recent activity yet. Start swapping to see updates here!</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
