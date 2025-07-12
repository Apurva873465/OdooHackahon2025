<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_user_id = $_POST['to_user_id'];
    $skill_offered = $_POST['skill_offered'];

    $stmt = $conn->prepare("INSERT INTO swap_requests (from_user_id, to_user_id, skill_offered) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $to_user_id, $skill_offered);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>Swap request sent!</p>";
    } else {
        echo "<p style='color: red;'>Error: {$stmt->error}</p>";
    }
}

// Fetch list of users to show in dropdown
$users = $conn->query("SELECT id, name FROM users WHERE id != $user_id");
?>
<link rel="stylesheet" href="assets/css/style.css">

<h2>Send Swap Request</h2>
<form method="POST">
    <label for="to_user_id">Send To:</label><br>
    <select name="to_user_id" required>
        <?php while($u = $users->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="skill_offered">Skill Offered:</label><br>
    <input type="text" name="skill_offered" required><br><br>

    <button type="submit">Send Request</button>
</form>
