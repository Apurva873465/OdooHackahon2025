<?php
// profile.php  —  view & edit the logged‑in user's profile
require_once 'includes/db.php';
require_once 'includes/auth.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

/* ---------- Handle profile update ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple trim / sanitize
    $name           = trim($_POST['name']           ?? '');
    $email          = trim($_POST['email']          ?? '');
    $skills_offered = trim($_POST['skills_offered'] ?? '');
    $skills_wanted  = trim($_POST['skills_wanted']  ?? '');
    $availability   = trim($_POST['availability']   ?? '');

    $stmt = $conn->prepare(
        "UPDATE users
         SET name = ?, email = ?, skills_offered = ?, skills_wanted = ?, availability = ?
         WHERE id = ?"
    );
    $stmt->bind_param("sssssi",
        $name, $email, $skills_offered, $skills_wanted, $availability, $user_id
    );
    $stmt->execute();

    $_SESSION['flash'] = "Profile updated successfully!";
    header("Location: profile.php");
    exit;
}

/* ---------- Fetch current user data for display ---------- */
$stmt = $conn->prepare(
    "SELECT name, email, skills_offered, skills_wanted, availability
     FROM users
     WHERE id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc() ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Minimal in‑page styling; you can move this to style.css */
        .profile-container { max-width: 560px; margin: 40px auto; padding: 2rem; background: #fff; border-radius: 8px; }
        .profile-container h2 { margin-bottom: 1rem; }
        .profile-form label { display: block; margin-bottom: .75rem; }
        .profile-form input { width: 100%; padding: .5rem .75rem; border: 1px solid #ccc; border-radius: 4px; }
        .profile-form button { margin-top: 1rem; padding: .6rem 1.2rem; border: none; background: #5e00ff; color: #fff; border-radius: 4px; cursor: pointer; }
        .profile-form .btn-secondary { margin-left: 1rem; color: #555; text-decoration: none; }
        .flash { padding: .75rem 1rem; margin-bottom: 1rem; background:#e6ffed; border:1px solid #b2f2bb; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>My Profile</h2>

        <!-- Flash message -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
        <?php endif; ?>

        <!-- Profile update form -->
        <form method="post" class="profile-form">
            <label>
                Name
                <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
            </label>

            <label>
                Email
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </label>

            <label>
                Skills Offered
                <input type="text" name="skills_offered" value="<?= htmlspecialchars($user['skills_offered'] ?? '') ?>">
            </label>

            <label>
                Skills Wanted
                <input type="text" name="skills_wanted" value="<?= htmlspecialchars($user['skills_wanted'] ?? '') ?>">
            </label>

            <label>
                Availability
                <input type="text" name="availability" value="<?= htmlspecialchars($user['availability'] ?? '') ?>">
            </label>

            <button type="submit">Save Changes</button>
            <a href="welcome.php" class="btn-secondary">← Back to Dashboard</a>
        </form>
    </div>
</body>
</html>
