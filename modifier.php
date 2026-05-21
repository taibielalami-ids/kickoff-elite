<?php
session_start();

// Require login + admin role
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';

$user = $_SESSION['user'];
$reg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reg_id <= 0) {
    $_SESSION['message']      = 'Invalid registration ID.';
    $_SESSION['message_type'] = 'danger';
    header('Location: admin.php');
    exit;
}

// Fetch the registration
$query  = "SELECT * FROM registrations WHERE id = $reg_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['message']      = 'Registration not found.';
    $_SESSION['message_type'] = 'danger';
    header('Location: admin.php');
    exit;
}

$registration = mysqli_fetch_assoc($result);

// Fetch all workshops for dropdown
$workshops_query  = "SELECT * FROM workshops ORDER BY title ASC";
$workshops_result = mysqli_query($conn, $workshops_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Registration - AtelierWeb</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <main style="padding-top: 80px;">
        <div class="container" style="padding: 2rem 1rem;">
            <div style="max-width: 800px; margin: 0 auto;">
                <div class="flex justify-between items-center mb-4">
                    <h2><i class="bi bi-pencil"></i> Edit Registration #<?php echo $reg_id; ?></h2>
                    <a href="admin.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Admin
                    </a>
                </div>

                <!-- Session Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                        <?php
                            echo $_SESSION['message'];
                            unset($_SESSION['message'], $_SESSION['message_type']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body" style="padding: 2rem;">
                        <form id="registrationForm" method="POST" action="traitement_inscription.php?mode=edit&id=<?php echo $reg_id; ?>" novalidate>
                            <div class="flex" style="gap: 1rem; flex-wrap: wrap;">
                                <!-- Workshop -->
                                <div class="form-group" style="flex: 1 1 calc(50% - 0.5rem); min-width: 250px;">
                                    <label for="workshop_id" class="form-label">Workshop <span style="color: #ef4444;">*</span></label>
                                    <select class="form-control" id="workshop_id" name="workshop_id" required style="height: 48px;">
                                        <option value="">-- Select a workshop --</option>
                                        <?php while ($w = mysqli_fetch_assoc($workshops_result)): ?>
                                            <option value="<?php echo $w['id']; ?>"
                                                <?php echo ($w['id'] == $registration['workshop_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($w['title']); ?>
                                                (<?php echo ($w['max_places'] - $w['reserved']); ?>/<?php echo $w['max_places']; ?> places)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text" style="color: #ef4444; display: none;">Please select a workshop.</div>
                                </div>

                                <!-- Level -->
                                <div class="form-group" style="flex: 1 1 calc(50% - 0.5rem); min-width: 250px;">
                                    <label for="level" class="form-label">Level <span style="color: #ef4444;">*</span></label>
                                    <select class="form-control" id="level" name="level" required style="height: 48px;">
                                        <option value="">-- Select --</option>
                                        <option value="Beginner" <?php echo $registration['level'] === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                        <option value="Intermediate" <?php echo $registration['level'] === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="Advanced" <?php echo $registration['level'] === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                                    </select>
                                    <div class="form-text" style="color: #ef4444; display: none;">Please select your level.</div>
                                </div>

                                <!-- Mode -->
                                <div class="form-group" style="width: 100%;">
                                    <label class="form-label">Mode <span style="color: #ef4444;">*</span></label>
                                    <div class="flex" style="gap: 2rem;">
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                            <input type="radio" name="mode" value="In-person"
                                                   <?php echo $registration['mode'] === 'In-person' ? 'checked' : ''; ?>
                                                   style="width: 18px; height: 18px; accent-color: #fff;">
                                            <span><i class="bi bi-building"></i> In-person</span>
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                            <input type="radio" name="mode" value="Online"
                                                   <?php echo $registration['mode'] === 'Online' ? 'checked' : ''; ?>
                                                   style="width: 18px; height: 18px; accent-color: #fff;">
                                            <span><i class="bi bi-globe"></i> Online</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="flex" style="gap: 1rem; margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-weight: 600;">
                                    <i class="bi bi-save"></i> Update Registration
                                </button>
                                <a href="admin.php" class="btn btn-secondary" style="padding: 0.75rem 2rem;">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer style="background: #1e293b; color: white; text-align: center; padding: 1.5rem 0; margin-top: 3rem;">
        <div class="container">
            <p style="margin: 0;">&copy; <?php echo date('Y'); ?> AtelierWeb. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
