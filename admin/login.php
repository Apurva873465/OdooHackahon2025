<?php
session_start();
// Simple hardcoded admin credentials (replace with DB check for production)
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'admin123';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username === $ADMIN_USER && $password === $ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | SkillSwap</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: linear-gradient(135deg, #eaf6ff 0%, #b3e5fc 100%); min-height: 100vh; }
        .admin-login-container { max-width: 400px; margin: 7vh auto; background: #fff; border-radius: 18px; box-shadow: 0 8px 32px #b3e5fc55; padding: 2.5rem 2rem; }
        .admin-login-title { color: #1976d2; font-size: 1.5rem; font-weight: 700; text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.3rem; }
        .form-label { font-weight: 600; color: #1976d2; margin-bottom: 0.5rem; display: block; }
        .form-control { width: 100%; padding: 0.8rem 1rem; border-radius: 10px; border: 1.5px solid #b3e5fc; font-size: 1.07em; background: #f8faff; color: #1976d2; }
        .form-control:focus { border: 1.5px solid #2196f3; outline: none; }
        .btn-primary { width: 100%; padding: 0.8rem; font-size: 1.1rem; }
        .error-msg { color: #ff6b6b; background: #fff0f0; border-radius: 8px; padding: 0.7rem 1rem; margin-bottom: 1rem; text-align: center; font-weight: 600; }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-title">Admin Login</div>
        <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" class="form-control" name="username" id="username" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control" name="password" id="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</body>
</html>