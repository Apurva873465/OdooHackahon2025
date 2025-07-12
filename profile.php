<?php
// profile.php  —  view & edit the logged‑in user's profile
require_once 'includes/db.php';
require_once 'includes/auth.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

/* ---------- Handle profile update and photo upload ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name']           ?? '');
    $email          = trim($_POST['email']          ?? '');
    $skills_offered = trim($_POST['skills_offered'] ?? '');
    $skills_wanted  = trim($_POST['skills_wanted']  ?? '');
    $availability   = trim($_POST['availability']   ?? '');
    $profile_photo  = null;
    $photo_sql = '';
    $photo_param = [];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $targetDir = 'assets/profile_photos/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $targetFile = $targetDir . $filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)) {
                $profile_photo = $targetFile;
                $photo_sql = ', profile_photo = ?';
                $photo_param[] = $profile_photo;
            }
        }
    }
    $sql = "UPDATE users SET name = ?, email = ?, skills_offered = ?, skills_wanted = ?, availability = ?$photo_sql WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $params = [$name, $email, $skills_offered, $skills_wanted, $availability];
    if ($profile_photo) $params[] = $profile_photo;
    $params[] = $user_id;
    $types = str_repeat('s', count($params)-1) . 'i';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $_SESSION['flash'] = "Profile updated successfully!";
    header("Location: profile.php");
    exit;
}

/* ---------- Fetch current user data for display ---------- */
$stmt = $conn->prepare(
    "SELECT name, email, skills_offered, skills_wanted, availability, profile_photo FROM users WHERE id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc() ?: [];

// Fetch recent swaps/feedback for activity feed
$recentSwaps = $conn->query("SELECT * FROM swaps WHERE requester_id = $user_id OR receiver_id = $user_id ORDER BY created_at DESC LIMIT 3");
$recentFeedback = $conn->query("SELECT * FROM feedback WHERE to_user = $user_id OR from_user = $user_id ORDER BY created_at DESC LIMIT 3");

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
    <title>My Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .profile-main {
            display: flex;
            gap: 2.5rem;
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1rem 1rem 1rem;
        }
        .profile-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 #b3e5fc55;
            padding: 2.5rem 2rem 2rem 2rem;
            min-width: 340px;
            max-width: 420px;
            flex: 0 0 400px;
            text-align: center;
            align-self: flex-start;
        }
        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem auto;
            box-shadow: 0 2px 12px #2196f344;
            overflow: hidden;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }
        .profile-info h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #1976d2;
            margin-bottom: 0.5rem;
        }
        .profile-info .email {
            color: #1976d2;
            font-size: 1.05rem;
            margin-bottom: 0.7rem;
        }
        .profile-info .section-label {
            font-weight: 600;
            color: #1565c0;
            margin-top: 1.1rem;
            margin-bottom: 0.2rem;
            display: block;
        }
        .skills-list {
            margin: 0.2rem 0 0.7rem 0;
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
        .profile-form {
            margin-top: 2.2rem;
            text-align: left;
        }
        .profile-form label {
            font-weight: 600;
            color: #1565c0;
            margin-bottom: 0.3rem;
            display: block;
        }
        .profile-form input[type="text"],
        .profile-form input[type="email"] {
            width: 100%;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            border: 1.5px solid #b3e5fc;
            margin-bottom: 1.1rem;
            font-size: 1rem;
            background: #f8faff;
            color: #1976d2;
            transition: border 0.2s;
        }
        .profile-form input:focus {
            border: 1.5px solid #2196f3;
            outline: none;
        }
        .profile-form input[type="file"] {
            margin-bottom: 1.1rem;
        }
        .profile-form .form-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .profile-form button {
            padding: 0.7rem 2.2rem;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 2px 8px #2196f322;
            transition: background 0.2s, transform 0.2s;
        }
        .profile-form button:hover {
            background: linear-gradient(90deg, #1565c0 0%, #2196f3 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .btn-secondary {
            color: #1976d2;
            background: #eaf6ff;
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
            border: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background: #b3e5fc;
        }
        .flash {
            padding: .75rem 1rem;
            margin-bottom: 1rem;
            background:#e6ffed;
            border:1px solid #b2f2bb;
            border-radius: 8px;
            color: #1976d2;
            font-weight: 600;
        }
        .activity-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 24px 0 #b3e5fc55;
            padding: 2rem 2rem 1.5rem 2rem;
            margin-bottom: 2rem;
            flex: 1 1 0%;
            min-width: 0;
            align-self: flex-start;
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
            .profile-main { flex-direction: column; gap: 0; }
            .profile-card, .activity-card { max-width: 100%; margin-bottom: 2rem; }
        }
    </style>
</head>
<body>
    <div class="profile-main">
        <div class="profile-card fade-in">
            <div class="profile-avatar">
                <?php if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])): ?>
                    <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile Photo">
                <?php else: ?>
                    <?= htmlspecialchars(getInitials($user['name'] ?? 'U')) ?>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($user['name'] ?? '') ?></h2>
                <div class="email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                <span class="section-label">Skills Offered:</span>
                <div class="skills-list">
                    <?php foreach (explode(',', $user['skills_offered'] ?? '') as $skill): if (trim($skill)): ?>
                        <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                    <?php endif; endforeach; ?>
                </div>
                <span class="section-label">Skills Wanted:</span>
                <div class="skills-list">
                    <?php foreach (explode(',', $user['skills_wanted'] ?? '') as $skill): if (trim($skill)): ?>
                        <span class="skill-tag" style="background:linear-gradient(90deg,#4fc3f7 0%,#2196f3 100%)"><?= htmlspecialchars(trim($skill)) ?></span>
                    <?php endif; endforeach; ?>
                </div>
                <span class="section-label">Availability:</span>
                <div style="color:#1976d2; font-weight:500; margin-bottom:0.5rem;">
                    <?= htmlspecialchars($user['availability'] ?? '') ?>
                </div>
            </div>
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
        <?php endif; ?>
            <form method="post" class="profile-form" enctype="multipart/form-data">
                <label>Profile Photo
                    <input type="file" name="profile_photo" accept="image/*">
                </label>
                <label>Name
                <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </label>
                <label>Email
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </label>
                <label>Skills Offered
                <input type="text" name="skills_offered" value="<?= htmlspecialchars($user['skills_offered'] ?? '') ?>">
            </label>
                <label>Skills Wanted
                <input type="text" name="skills_wanted" value="<?= htmlspecialchars($user['skills_wanted'] ?? '') ?>">
            </label>
                <label>Availability
                <input type="text" name="availability" value="<?= htmlspecialchars($user['availability'] ?? '') ?>">
            </label>
                <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="welcome.php" class="btn-secondary">← Back to Dashboard</a>
                </div>
        </form>
        </div>
        <div class="activity-card fade-in">
            <h4>Recent Activity</h4>
            <ul class="activity-list">
                <?php if ($recentSwaps && $recentSwaps->num_rows): foreach ($recentSwaps as $swap): ?>
                    <li><span class="icon">🔄</span>Swap with User #<?= htmlspecialchars($swap['receiver_id'] == $user_id ? $swap['requester_id'] : $swap['receiver_id']) ?> (<?= htmlspecialchars($swap['status'] ?? 'pending') ?>)</li>
                <?php endforeach; endif; ?>
                <?php if ($recentFeedback && $recentFeedback->num_rows): foreach ($recentFeedback as $fb): ?>
                    <li><span class="icon">⭐</span>Feedback: <?= htmlspecialchars($fb['rating']) ?>/5</li>
                <?php endforeach; endif; ?>
                <?php if ((!$recentSwaps || !$recentSwaps->num_rows) && (!$recentFeedback || !$recentFeedback->num_rows)): ?>
                    <li>No recent activity yet. Start swapping to see updates here!</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>
