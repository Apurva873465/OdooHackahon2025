<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | SkillSwap</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, #eaf6ff 0%, #4fc3f7 100%); min-height: 100vh;">
    <nav class="navbar">
        <div class="container">
            <a href="#" class="navbar-brand">
                <i class="fas fa-exchange-alt"></i> <span style="color:#2196f3;">SkillSwap</span>
            </a>
            <ul class="navbar-nav">
                <li><a href="login.php" class="nav-link">Login</a></li>
                <li><a href="register.php" class="nav-link">Sign Up</a></li>
            </ul>
        </div>
    </nav>
    <div class="container" style="min-height: 80vh; display: flex; align-items: center; justify-content: center;">
        <div class="card fade-in" style="max-width: 500px; margin: 3rem auto; background: rgba(255,255,255,0.85); box-shadow: 0 8px 32px 0 #b3e5fc55; border-radius: 24px;">
            <div class="card-header" style="border-bottom: 1px solid #eaf6ff; text-align:center;">
                <div style="font-size:2.5rem; color:#2196f3; margin-bottom:0.5rem;"><i class="fas fa-hands-helping"></i></div>
                <h1 class="card-title" style="color:#1976d2;">Welcome to SkillSwap!</h1>
                <div style="font-size:1.1rem; color:#2196f3; font-weight:500; margin-top:0.2rem;">Exchange. Learn. Grow.</div>
            </div>
            <div class="card-body" style="color:#1976d2; text-align:center;">
                <p style="font-size:1.08rem; color:#1976d2;">SkillSwap helps you connect, learn, and grow by exchanging skills with others.<br>Join our community and start swapping today!</p>
                <div class="d-flex gap-2 mt-3" style="justify-content:center;">
                    <a href="register.php" class="btn btn-primary" style="min-width:130px;">Get Started</a>
                    <a href="login.php" class="btn btn-outline" style="min-width:100px;">Login</a>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>