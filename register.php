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
<!-- Simple Registration Form -->
<form method="POST">
    <input type="text" name="name" placeholder="Your Name" required><br><br>
    <input type="email" name="email" placeholder="Email Address" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Register</button>
</form>
