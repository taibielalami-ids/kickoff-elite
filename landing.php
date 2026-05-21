<?php
session_start();
$user_logged_in = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-page">
    <?php include 'includes/navbar.php'; ?>

    <section class="hero">
        <div class="container">
            <h1>Training Workshops<br>for Web Developers</h1>
            <p>HTML/CSS, JavaScript, PHP, MySQL, Bootstrap and more.</p>
            <div class="flex justify-center gap-3" style="flex-wrap: wrap;">
                <a href="index.php" class="btn btn-primary">Browse Workshops</a>
                <?php if (!$user_logged_in): ?>
                    <a href="signup.php" class="btn">Get Started</a>
                    <a href="login.php" class="btn">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> AtelierWeb</p>
    </footer>
</body>
</html>