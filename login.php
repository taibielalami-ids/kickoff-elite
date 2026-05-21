<?php
session_start();
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') { header('Location: admin.php'); exit; }
    else { header('Location: index.php'); exit; }
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    if (empty($email) || empty($password)) {
        $error = 'Please fill in both fields.';
    } else {
        $query  = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'], 'email' => $user['email'],
                    'first_name' => $user['first_name'], 'last_name' => $user['last_name'],
                    'role' => $user['role'],
                ];
                if ($user['role'] === 'admin') { header('Location: admin.php'); exit; }
                else { header('Location: index.php'); exit; }
            } else { $error = 'Invalid email or password.'; }
        } else { $error = 'Invalid email or password.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
    <canvas id="bgCanvas"></canvas>
    <div class="container" style="max-width:400px;position:relative;z-index:1;">
        <div class="card">
            <div class="card-header" style="text-align:center;">
                <a href="landing.php" style="text-decoration:none;color:var(--text);font-weight:700;font-size:1.1rem;">
                    <i class="bi bi-laptop" style="color:var(--primary);"></i> AtelierWeb
                </a>
                <p style="margin:0.25rem 0 0;font-size:0.85rem;color:var(--text-secondary);">Sign in to your account</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                        <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
                </form>
                <div style="text-align:center;margin-top:1rem;font-size:0.85rem;color:var(--text-secondary);">
                    Don't have an account? <a href="signup.php" style="color:var(--primary);text-decoration:none;">Sign Up</a>
                </div>
                <div style="text-align:center;margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border);font-size:0.8rem;color:var(--text-muted);">
                    Demo: admin@atelier.com / admin123 &bull; user@atelier.com / user123
                </div>
            </div>
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <a href="landing.php" style="color:var(--text-secondary);text-decoration:none;font-size:0.85rem;">
                <i class="bi bi-arrow-left"></i> Back to home
            </a>
        </div>
    </div>
    <script src="assets/js/dot-matrix-reveal.js"></script>
</body>
</html>
