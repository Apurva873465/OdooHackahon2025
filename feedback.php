<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
?>

<link rel="stylesheet" href="assets/css/style.css">

<div class="form-container">
    <form method="POST">
        <h2>Feedback</h2>

        <label for="swap_id">Swap ID:</label>
        <input type="number" name="swap_id" required><br>

        <label for="rating">Rating (1-5):</label>
        <input type="number" name="rating" min="1" max="5" required><br>

        <label for="comments">Comments:</label>
        <textarea name="comments" required></textarea><br>

        <button type="submit">Submit</button>
    </form>
</div>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $swap_id = (int) $_POST['swap_id'];
    $rating = (int) $_POST['rating'];
    $comments = trim($_POST['comments']);
    $from_user = $_SESSION['user_id'];

    // Validate if swap exists and get receiver_id
    $stmt = $conn->prepare("SELECT to_user_id FROM swap_requests WHERE id = ?");
    $stmt->bind_param("i", $swap_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 0) {
        echo "<p style='color:red;'>Invalid swap ID.</p>";
    } else {
        $to_user = $res->fetch_assoc()['to_user_id'];

        // Insert feedback securely
        $stmt = $conn->prepare("INSERT INTO feedback (swap_id, from_user, to_user, rating, comments) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $swap_id, $from_user, $to_user, $rating, $comments);
        if ($stmt->execute()) {
            echo "<p style='color:green;'>✅ Feedback submitted successfully.</p>";
        } else {
            echo "<p style='color:red;'>❌ Error: {$stmt->error}</p>";
        }
    }
}
?>
