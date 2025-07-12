<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_user_id = $_POST['to_user_id'];
    $skill_offered = trim($_POST['skill_offered']);

    if ($to_user_id && $skill_offered) {
        $stmt = $conn->prepare("INSERT INTO swap_requests (from_user_id, to_user_id, skill_offered) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $to_user_id, $skill_offered);
        if ($stmt->execute()) {
            $message = "<p class='success'>✅ Swap request sent!</p>";
        } else {
            $message = "<p class='error'>❌ Error: {$stmt->error}</p>";
        }
    } else {
        $message = "<p class='error'>❌ Please fill in all fields.</p>";
    }
}

// Fetch list of users to show in dropdown
$users = $conn->query("SELECT id, name FROM users WHERE id != $user_id");
?>

<link rel="stylesheet" href="assets/css/style.css">

<div class="form-container">
    <form method="POST">
        <h2>Send Swap Request</h2>
        
        <?= $message ?>

        <label for="to_user_id">Send To:</label>
        <select name="to_user_id" required>
            <option value="">-- Select User --</option>
            <?php while($u = $users->fetch_assoc()): ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="skill_offered">Skill Offered:</label>
        <input type="text" name="skill_offered" placeholder="e.g. Python, Photoshop" required>

        <button type="submit">Send Request</button>
    </form>
</div>
