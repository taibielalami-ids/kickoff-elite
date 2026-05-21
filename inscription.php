<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once 'config/db.php';
$user = $_SESSION['user'];
$workshops_query = "SELECT * FROM workshops ORDER BY title ASC";
$workshops_result = mysqli_query($conn, $workshops_query);
$selected_id = isset($_GET['workshop_id']) ? (int)$_GET['workshop_id'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="page-wrap">
        <div class="container" style="max-width:600px;">
            <div class="section-header">
                <h2><i class="bi bi-pencil-square"></i> Workshop Registration</h2>
                <a href="index.php" class="btn btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                    <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form id="registrationForm" method="POST" action="traitement_inscription.php" novalidate>
                        <div class="form-group">
                            <label class="form-label">Workshop *</label>
                            <select name="workshop_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                <?php while ($w = mysqli_fetch_assoc($workshops_result)):
                                    $remaining = $w['max_places'] - $w['reserved'];
                                    $disabled = $remaining <= 0 ? 'disabled' : '';
                                    $selected = ($w['id'] == $selected_id) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $w['id']; ?>" <?php echo "$selected $disabled"; ?>>
                                    <?php echo htmlspecialchars($w['title']); ?> (<?php echo $remaining; ?> spots)
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text error" style="display:none;">Please select a workshop.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Level *</label>
                            <select name="level" class="form-control" required>
                                <option value="">-- Select --</option>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                            <div class="form-text error" style="display:none;">Please select your level.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Mode *</label>
                            <div class="flex gap-4">
                                <label style="display:flex;align-items:center;gap:0.35rem;cursor:pointer;font-size:0.9rem;">
                                    <input type="radio" name="mode" value="In-person" checked style="accent-color:#fff;">
                                    <i class="bi bi-building"></i> In-person
                                </label>
                                <label style="display:flex;align-items:center;gap:0.35rem;cursor:pointer;font-size:0.9rem;">
                                    <input type="radio" name="mode" value="Online" style="accent-color:#fff;">
                                    <i class="bi bi-globe"></i> Online
                                </label>
                            </div>
                        </div>

                        <div class="flex gap-3" style="margin-top:0.5rem;">
                            <button type="submit" class="btn btn-primary">Submit Registration</button>
                            <a href="index.php" class="btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer><p>&copy; <?php echo date('Y'); ?> AtelierWeb.</p></footer>
    <script src="assets/js/script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>