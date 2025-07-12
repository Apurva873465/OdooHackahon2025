<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
// session_start(); // Already started in includes/auth.php

// Handle login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    if ($auth->login($email, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $login_error = "Invalid login credentials.";
    }
}

// Handle signup
$signup_error = '';
$signup_success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    if ($password !== $confirm) {
        $signup_error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $signup_error = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hash);
            if ($stmt->execute()) {
                $signup_success = "Account created! You can now log in.";
            } else {
                $signup_error = "Signup failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SkillSwap</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            background: #fff;
            box-shadow: 0 8px 32px 0 #b3e5fc55;
            border-radius: 24px;
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 400px;
            width: 100%;
            margin: 2rem auto;
            color: #1976d2;
            position: relative;
            animation: floatIn 0.8s cubic-bezier(.39,.575,.56,1.000);
        }
        .login-card h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: #1976d2;
        }
        .login-card input[type="text"],
        .login-card input[type="email"],
        .login-card input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1.5px solid #b3e5fc;
            background: #f8faff;
            color: #1976d2;
            font-size: 1rem;
            margin-bottom: 1.2rem;
            outline: none;
            transition: box-shadow 0.2s, border 0.2s;
        }
        .login-card input:focus {
            box-shadow: 0 0 0 2px #b3e5fc;
            border: 1.5px solid #2196f3;
            background: #eaf6ff;
        }
        .login-card button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            background: linear-gradient(90deg, #2196f3 0%, #4fc3f7 100%);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 0.5rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
            box-shadow: 0 2px 8px #2196f322;
        }
        .login-card button:hover {
            background: linear-gradient(90deg, #1565c0 0%, #2196f3 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .login-card .form-link {
            color: #1976d2;
            text-align: center;
            display: block;
            margin-top: 1.2rem;
            text-decoration: none;
            font-weight: 600;
        }
        .login-card .form-link:hover {
            text-decoration: underline;
            color: #1565c0;
        }
        @keyframes floatIn {
            from { opacity: 0; transform: translateY(30px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>
    <div class="login-card fade-in">
        <h2>Login to SkillSwap</h2>
        <?php if ($login_error): ?><div class="auth-error"><?php echo $login_error; ?></div><?php endif; ?>
        <?php if ($signup_success): ?><div class="auth-success"><?php echo $signup_success; ?></div><?php endif; ?>
        <form method="post">
            <input type="email" name="email" placeholder="Email" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <a href="register.php" class="form-link">Don't have an account? Sign up</a>
    </div>
</body>
</html>