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
    <div class="container">
        <!-- Welcome Section -->
        <div class="card fade-in">
            <div class="card-header">
                <h1 class="card-title">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            </div>
            <div class="card-body">
                <p>Connect with people who have the skills you need and share your expertise in return.</p>
                <div class="d-flex gap-2 mt-3">
                    <a href="search.php" class="btn btn-primary">Start Swapping</a>
                    <a href="profile.php" class="btn btn-outline">Edit Profile</a>
                </div>
            </div>
        </div>

        <!-- Statistics Dashboard -->
        <div class="dashboard-stats">
            <div class="stat-card fade-in">
                <div class="stat-number"><?php echo $completedSwaps; ?></div>
                <div class="stat-label">Completed Swaps</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-number"><?php echo $pendingRequests; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-number"><?php echo $avgRating; ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-number"><?php echo $skillsOffered; ?></div>
                <div class="stat-label">Skills Offered</div>
            </div>
        </div>

        <!-- You can continue to convert the rest of the sample_template.html here, using PHP to fetch and display real data for users, swaps, feedback, etc. -->

    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
