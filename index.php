<?php
session_start();
require_once 'config/db.php';
$user = $_SESSION['user'] ?? [];
$query = "SELECT * FROM workshops ORDER BY category, date_atelier ASC";
$result = mysqli_query($conn, $query);

// Group workshops by category
$categories = [];
while ($w = mysqli_fetch_assoc($result)) {
    $cat = !empty($w['category']) ? $w['category'] : 'General';
    $categories[$cat][] = $w;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshops - AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="page-wrap">
        <div class="container">
            <div class="section-header">
                <h2><i class="bi bi-mortarboard"></i> Workshops</h2>
                <?php if (!empty($user) && isset($user['role']) && $user['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-sm"><i class="bi bi-people"></i> Registrations</a>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                    <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category => $workshops): ?>
                <div style="margin-bottom: 0.5rem; margin-top: 1.5rem;">
                    <h3 style="display:inline-block; padding:0.25rem 1rem; border:1px solid var(--border); border-radius:var(--radius); font-size:0.9rem; letter-spacing:0.05em;">
                        <?php echo htmlspecialchars($category); ?>
                    </h3>
                </div>
                <div class="grid-4">
                    <?php foreach ($workshops as $w):
                        $remaining = $w['max_places'] - $w['reserved'];
                        $is_full = $remaining <= 0;
                    ?>
                    <div class="workshop-card">
                        <div class="workshop-card-header">
                            <?php echo htmlspecialchars($w['title']); ?>
                        </div>
                        <div class="workshop-card-body">
                            <div class="workshop-meta">
                                <span><i class="bi bi-calendar"></i> <?php echo date('M j, Y', strtotime($w['date_atelier'])); ?></span>
                                <span><i class="bi bi-clock"></i> <?php echo htmlspecialchars($w['duration']); ?></span>
                            </div>
                            <div class="workshop-meta" style="border-top: 1px solid var(--border); padding-top: 0.5rem;">
                                <span>Places:</span>
                                <span class="workshop-places <?php echo $remaining <= 0 ? 'tag-red' : ($remaining <= 3 ? 'tag-yellow' : 'tag-green'); ?>">
                                    <?php echo $remaining; ?> left
                                </span>
                            </div>
                            <?php if (!empty($w['description'])): ?>
                                <p style="font-size: 0.85rem; margin-top: 0.5rem;"><?php echo htmlspecialchars($w['description']); ?></p>
                            <?php endif; ?>
                            <a href="inscription.php?workshop_id=<?php echo $w['id']; ?>"
                               class="btn <?php echo $is_full ? 'btn' : 'btn-primary'; ?>" style="width:100%; margin-top:0.75rem; <?php echo $is_full ? 'opacity:0.5;pointer-events:none;' : ''; ?>">
                                <?php echo $is_full ? '<i class="bi bi-x-circle"></i> Full' : '<i class="bi bi-pencil-square"></i> Register'; ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No workshops available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
    <footer><p>&copy; <?php echo date('Y'); ?> AtelierWeb.</p></footer>
</body>
</html>
<?php mysqli_close($conn); ?>
