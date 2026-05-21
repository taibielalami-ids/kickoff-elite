<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
if ($_SESSION['user']['role'] !== 'admin') { header('Location: index.php'); exit; }
require_once 'config/db.php';
$user = $_SESSION['user'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($course_id <= 0) { $_SESSION['message']='Invalid course ID.'; $_SESSION['message_type']='danger'; header('Location: gestion_cours.php'); exit; }
$query = "SELECT * FROM workshops WHERE id = $course_id";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) === 0) { $_SESSION['message']='Course not found.'; $_SESSION['message_type']='danger'; header('Location: gestion_cours.php'); exit; }
$course = mysqli_fetch_assoc($result);
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
    if ($max_places < $course['reserved']) $errors[] = 'Max places cannot be less than reserved ('.$course['reserved'].').';
    if (empty($errors)) {
        $uq = "UPDATE workshops SET title='$title', date_atelier='$date_atelier', duration='$duration', max_places=$max_places, description='$description' WHERE id=$course_id";
        if (mysqli_query($conn, $uq)) { $_SESSION['message']='Course updated!'; $_SESSION['message_type']='success'; header('Location: gestion_cours.php'); exit; }
        else { $errors[] = 'Database error.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="page-wrap">
        <div class="container" style="max-width:640px;">
            <div class="section-header">
                <h2><i class="bi bi-pencil"></i> Edit Course</h2>
                <a href="gestion_cours.php" class="btn btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul style="margin:0;padding-left:1.25rem;"><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="modifier_cours.php?id=<?php echo $course_id; ?>">
                        <div class="form-group">
                            <label class="form-label">Course Title *</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($_POST['title'] ?? $course['title']); ?>" required>
                        </div>
                        <div class="flex gap-3" style="flex-wrap:wrap;">
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label class="form-label">Date *</label>
                                <input type="date" name="date_atelier" class="form-control" value="<?php echo htmlspecialchars($_POST['date_atelier'] ?? $course['date_atelier']); ?>" required>
                            </div>
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label class="form-label">Duration *</label>
                                <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($_POST['duration'] ?? $course['duration']); ?>" required>
                            </div>
                            <div class="form-group" style="flex:1;min-width:180px;">
                                <label class="form-label">Max Places *</label>
                                <input type="number" name="max_places" class="form-control" value="<?php echo htmlspecialchars($_POST['max_places'] ?? $course['max_places']); ?>" min="<?php echo $course['reserved']; ?>" required>
                                <div class="form-text">Minimum <?php echo $course['reserved']; ?> (reserved)</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $course['description']); ?></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="btn btn-primary">Update Course</button>
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