<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
if ($_SESSION['user']['role'] !== 'admin') { header('Location: index.php'); exit; }
require_once 'config/db.php';
$user = $_SESSION['user'];
$query = "SELECT * FROM workshops ORDER BY date_atelier ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="page-wrap">
        <div class="container">
            <div class="section-header">
                <h2><i class="bi bi-book"></i> Courses</h2>
                <a href="ajouter_cours.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Add Course</a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                    <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <div class="search-wrap">
                <span class="search-icon"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="search-input" placeholder="Search courses..." onkeyup="filterTable()">
            </div>

            <div class="table-wrap">
                <table id="coursesTable">
                    <thead>
                        <tr><th>#</th><th>Title</th><th>Date</th><th>Duration</th><th>Max</th><th>Reserved</th><th>Available</th><th style="text-align:center;">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($w = mysqli_fetch_assoc($result)):
                                $avail = $w['max_places'] - $w['reserved'];
                            ?>
                            <tr>
                                <td><?php echo $w['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($w['title']); ?></strong></td>
                                <td><?php echo date('M j, Y', strtotime($w['date_atelier'])); ?></td>
                                <td><?php echo htmlspecialchars($w['duration']); ?></td>
                                <td><?php echo $w['max_places']; ?></td>
                                <td><?php echo $w['reserved']; ?></td>
                                <td><span class="tag <?php echo $avail <= 0 ? 'tag-red' : ($avail <= 3 ? 'tag-yellow' : 'tag-green'); ?>"><?php echo $avail; ?></span></td>
                                <td style="text-align:center;">
                                    <a href="modifier_cours.php?id=<?php echo $w['id']; ?>" class="btn btn-sm" style="margin-right:0.25rem;"><i class="bi bi-pencil"></i></a>
                                    <a href="supprimer_cours.php?id=<?php echo $w['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete(event, this)"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">No courses found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <footer><p>&copy; <?php echo date('Y'); ?> AtelierWeb.</p></footer>
    <script src="assets/js/script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>