<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<canvas id="bgCanvas"></canvas>
<nav class="navbar">
    <div class="navbar-container">
        <a class="navbar-brand" href="landing.php">
            <i class="bi bi-laptop"></i> AtelierWeb
        </a>
        <ul class="navbar-nav">
            <li>
                <a class="nav-link <?php echo $current_page === 'landing.php' ? 'active' : ''; ?>" href="landing.php">
                    <i class="bi bi-house"></i> Home
                </a>
            </li>
            <?php if (!isset($_SESSION['user']) || (isset($_SESSION['user']) && $_SESSION['user']['role'] !== 'admin')): ?>
                <li>
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-book"></i> Workshops
                    </a>
                </li>
            <?php endif; ?>
            <?php if (isset($_SESSION['user'])): ?>
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                    <li>
                        <a class="nav-link <?php echo $current_page === 'admin.php' ? 'active' : ''; ?>" href="admin.php">
                            <i class="bi bi-people"></i> Registrations
                        </a>
                    </li>
                    <li>
                        <a class="nav-link <?php echo $current_page === 'gestion_cours.php' ? 'active' : ''; ?>" href="gestion_cours.php">
                            <i class="bi bi-gear"></i> Courses
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['user'])): ?>
                <li>
                    <span class="nav-link" style="color: var(--text-muted); cursor: default;">
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['user']['first_name']); ?>
                    </span>
                </li>
                <li>
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" href="login.php">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </a>
                </li>
                <li>
                    <a class="nav-link <?php echo $current_page === 'signup.php' ? 'active' : ''; ?>" href="signup.php">
                        <i class="bi bi-person-plus"></i> Sign Up
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<script src="assets/js/dot-matrix-reveal.js"></script>
