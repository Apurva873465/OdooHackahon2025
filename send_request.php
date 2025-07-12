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
    $to_user_id = $_POST['to_user_id'] ?? '';
    $skill_requested = isset($_POST['skill_requested']) ? trim($_POST['skill_requested']) : '';
    $skill_offered = isset($_POST['skill_offered']) ? trim($_POST['skill_offered']) : '';
    $message_text = trim($_POST['message'] ?? '');

    if ($to_user_id && $skill_requested && $skill_offered) {
        $stmt = $conn->prepare("INSERT INTO swaps (requester_id, receiver_id, skill_requested, skill_offered, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("iisss", $user_id, $to_user_id, $skill_requested, $skill_offered, $message_text);
        if ($stmt->execute()) {
            $message = "<p class='success'>✅ Swap request sent!" . ($message_text ? "<br>Message: " . htmlspecialchars($message_text) : "") . "</p>";
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
        <h2 class="mb-3" style="text-align:center;">Send Swap Request</h2>
        <?php if ($message) echo $message; ?>
        <div class="form-group">
            <label for="to_user_id" class="form-label">Send To:</label>
            <select name="to_user_id" class="form-control" required>
                <option value="">-- Select User --</option>
                <?php while($u = $users->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="skill_requested" class="form-label">Skill Requested:</label>
            <input type="text" name="skill_requested" class="form-control" placeholder="e.g. Python, Photoshop" required>
        </div>
        <div class="form-group">
            <label for="skill_offered" class="form-label">Skill Offered:</label>
            <input type="text" name="skill_offered" class="form-control" placeholder="e.g. Cooking, Java" required>
        </div>
        <div class="form-group">
            <label for="message" class="form-label">Message (optional):</label>
            <textarea name="message" class="form-control" placeholder="Add a message for the recipient..." rows="3"></textarea>
        </div>
        <div class="form-group" style="text-align:center;">
            <button type="submit" class="btn btn-primary">Send Request</button>
        </div>
    </form>
</div>
