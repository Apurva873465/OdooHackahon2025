<?php
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // 1️⃣ Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        echo "<p style='color:red;'>❌ Email already registered. Please try logging in.</p>";
    } else {
        // 2️⃣ Proceed with insert
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        if ($stmt->execute()) {
            echo "<p style='color:green;'>✅ Registration successful. <a href='login.php'>Login here</a>.</p>";
        } else {
            echo "<p style='color:red;'>Something went wrong. Please try again.</p>";
        }
    }
}
?>
<link rel="stylesheet" href="assets/css/style.css">
<div class="form-container">
    <form method="POST">
        <h2 class="mb-3" style="text-align:center;">Register</h2>
        <div class="form-group">
            <label for="name" class="form-label">Your Name</label>
            <input type="text" name="name" class="form-control" placeholder="Your Name" required>
        </div>
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
        </div>
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <div class="form-group" style="text-align:center;">
            <button type="submit" class="btn btn-primary">Register</button>
        </div>
    </form>
</div>
