<?php
require_once 'includes/db.php';
session_start();

// Handle login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute(); $stmt->store_result();
    $stmt->bind_result($id, $hash); $stmt->fetch();
    if ($stmt->num_rows > 0 && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%), url('https://www.transparenttextures.com/patterns/cubes.png');
            background-blend-mode: overlay;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }
        .auth-container {
            background: rgba(30, 30, 40, 0.85);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-radius: 24px;
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 370px;
            width: 100%;
            margin: 2rem auto;
            color: #fff;
            position: relative;
            animation: fadeIn 0.8s cubic-bezier(.39,.575,.56,1.000);
        }
        .auth-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            color: #fff;
        }
        .auth-container .form-group {
            margin-bottom: 1.2rem;
        }
        .auth-container label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: #cfcfff;
        }
        .auth-container input[type="text"],
        .auth-container input[type="email"],
        .auth-container input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: none;
            background: rgba(255,255,255,0.08);
            color: #fff;
            font-size: 1rem;
            margin-bottom: 0.2rem;
            outline: none;
            transition: box-shadow 0.2s;
        }
        .auth-container input:focus {
            box-shadow: 0 0 0 2px #764ba2;
            background: rgba(255,255,255,0.13);
        }
        .auth-container button {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 0.5rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }
        .auth-container button:hover {
            background: linear-gradient(90deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px) scale(1.03);
        }
        .auth-toggle {
            text-align: center;
            margin-top: 1.2rem;
            color: #cfcfff;
        }
        .auth-toggle a {
            color: #f093fb;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 600;
        }
        .auth-error {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 600;
        }
        .auth-success {
            background: rgba(79, 172, 254, 0.15);
            color: #4facfe;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 600;
        }
        @media (max-width: 500px) {
            .auth-container { padding: 1.2rem 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="auth-container" id="authBox">
        <div id="loginBox" style="display: block;">
            <h2>Login</h2>
            <?php if ($login_error): ?><div class="auth-error"><?php echo $login_error; ?></div><?php endif; ?>
            <?php if ($signup_success): ?><div class="auth-success"><?php echo $signup_success; ?></div><?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <div class="auth-toggle">
                Don't have an account? <a onclick="toggleAuth('signup')">Sign up</a>
            </div>
        </div>
        <div id="signupBox" style="display: none;">
            <h2>Sign Up</h2>
            <?php if ($signup_error): ?><div class="auth-error"><?php echo $signup_error; ?></div><?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="signup">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email_signup">Email</label>
                    <input type="email" id="email_signup" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password_signup">Password</label>
                    <input type="password" id="password_signup" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit">Sign Up</button>
            </form>
            <div class="auth-toggle">
                Already have an account? <a onclick="toggleAuth('login')">Login</a>
            </div>
        </div>
    </div>
    <script>
        function toggleAuth(mode) {
            document.getElementById('loginBox').style.display = (mode === 'login') ? 'block' : 'none';
            document.getElementById('signupBox').style.display = (mode === 'signup') ? 'block' : 'none';
        }
    </script>
</body>
</html>