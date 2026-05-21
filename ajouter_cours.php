<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
if ($_SESSION['user']['role'] !== 'admin') { header('Location: index.php'); exit; }
require_once 'config/db.php';
$user = $_SESSION['user'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
    $date_atelier = mysqli_real_escape_string($conn, $_POST['date_atelier'] ?? '');
    $duration = mysqli_real_escape_string($conn, trim($_POST['duration'] ?? ''));
    $max_places = isset($_POST['max_places']) ? (int)$_POST['max_places'] : 0;
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($date_atelier)) $errors[] = 'Date is required.';
    if (empty($duration)) $errors[] = 'Duration is required.';
    if ($max_places <= 0) $errors[] = 'Max places must be greater than 0.';
    if (empty($errors)) {
        $q = "INSERT INTO workshops (title, date_atelier, duration, max_places, reserved, description) VALUES ('$title', '$date_atelier', '$duration', $max_places, 0, '$description')";
        if (mysqli_query($conn, $q)) {
            $_SESSION['message'] = 'Course added successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: gestion_cours.php'); exit;
        } else { $errors[] = 'Database error.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course - AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="page-wrap">
        <div class="container" style="max-width:640px;">
            <div class="section-header">
                <h2><i class="bi bi-plus-circle"></i> Add Course</h2>
                <a href="gestion_cours.php" class="btn btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin:0;padding-left:1.25rem;">
                        <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="ajouter_cours.php">
                        <div class="form-group">
                            <label class="form-label">Course Title *</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" placeholder="e.g. React.js Fundamentals" required>
                        </div>
                        <div class="flex gap-3" style="flex-wrap:wrap;">
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label class="form-label">Date *</label>
                                <input type="date" name="date_atelier" class="form-control" value="<?php echo htmlspecialchars($_POST['date_atelier'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label class="form-label">Duration *</label>
                                <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>" placeholder="e.g. 3 hours" required>
                            </div>
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label class="form-label">Max Places *</label>
                                <input type="number" name="max_places" class="form-control" value="<?php echo htmlspecialchars($_POST['max_places'] ?? ''); ?>" min="1" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe what this workshop covers..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="btn btn-primary">Add Course</button>
                            <a href="gestion_cours.php" class="btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer><p>&copy; <?php echo date('Y'); ?> AtelierWeb.</p></footer>
</body>
</html>
<?php mysqli_close($conn); ?>