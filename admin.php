<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
if ($_SESSION['user']['role'] !== 'admin') { header('Location: index.php'); exit; }
require_once 'config/db.php';
$user = $_SESSION['user'];
$query = "SELECT r.*, w.title AS workshop_title FROM registrations r JOIN workshops w ON r.workshop_id = w.id ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrations - AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="page-wrap">
        <div class="container">
            <div class="section-header">
                <h2><i class="bi bi-people"></i> Registrations</h2>
                <a href="index.php" class="btn btn-sm"><i class="bi bi-eye"></i> Workshops</a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                    <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <div class="search-wrap">
                <span class="search-icon"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name, email, workshop, level, mode..." onkeyup="filterTable()">
            </div>

            <div class="table-wrap">
                <table id="registrationsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Workshop</th>
                            <th>Level</th>
                            <th>Mode</th>
                            <th>Date</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($reg = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $reg['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                <td><?php echo htmlspecialchars($reg['workshop_title']); ?></td>
                                <td>
                                    <span class="tag <?php
                                        echo $reg['level'] === 'Beginner' ? 'tag-green' : ($reg['level'] === 'Intermediate' ? 'tag-yellow' : 'tag-red');
                                    ?>"><?php echo $reg['level']; ?></span>
                                </td>
                                <td><?php echo $reg['mode'] === 'In-person' ? '<i class="bi bi-building" style="color:var(--success);"></i> In-person' : '<i class="bi bi-globe" style="color:var(--primary);"></i> Online'; ?></td>
                                <td style="font-size:0.85rem;"><?php echo date('M j, Y', strtotime($reg['created_at'])); ?></td>
                                <td style="text-align:center;">
                                    <a href="modifier.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm" title="Edit" style="margin-right:0.25rem;"><i class="bi bi-pencil"></i></a>
                                    <a href="supprimer.php?id=<?php echo $reg['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirmDelete(event, this)"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">No registrations found.</td></tr>
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
