<?php
session_start();

// Require login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';

$errors = [];
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'add';

// Helper to sanitize input
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize fields
    $workshop_id = isset($_POST['workshop_id']) ? (int)$_POST['workshop_id'] : 0;
    $level       = sanitize($conn, $_POST['level'] ?? '');
    $mode_radio  = sanitize($conn, $_POST['mode'] ?? '');

    // --- Validation ---

    if ($workshop_id <= 0) {
        $errors[] = 'Please select a workshop.';
    } else {
        // Verify workshop exists and has available places (only for new registrations)
        $ws_query  = "SELECT * FROM workshops WHERE id = $workshop_id";
        $ws_result = mysqli_query($conn, $ws_query);
        if (mysqli_num_rows($ws_result) === 0) {
            $errors[] = 'Selected workshop does not exist.';
        } else {
            $workshop = mysqli_fetch_assoc($ws_result);
            $remaining = $workshop['max_places'] - $workshop['reserved'];
            if ($mode === 'add' && $remaining <= 0) {
                $errors[] = 'Sorry, this workshop is full.';
            }
        }
    }
    if (empty($level)) {
        $errors[] = 'Please select your level.';
    } elseif (!in_array($level, ['Beginner', 'Intermediate', 'Advanced'])) {
        $errors[] = 'Invalid level selected.';
    }
    if (empty($mode_radio)) {
        $errors[] = 'Please select a mode (In-person or Online).';
    } elseif (!in_array($mode_radio, ['In-person', 'Online'])) {
        $errors[] = 'Invalid mode selected.';
    }

    // --- Process ---
    if (empty($errors)) {
        if ($mode === 'edit') {
            // UPDATE existing registration
            $reg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            if ($reg_id <= 0) {
                $_SESSION['message']      = 'Invalid registration ID.';
                $_SESSION['message_type'] = 'danger';
                header('Location: admin.php');
                exit;
            }

            $update_query = "UPDATE registrations SET
                                workshop_id = $workshop_id,
                                level       = '$level',
                                mode        = '$mode_radio'
                             WHERE id = $reg_id";

            if (mysqli_query($conn, $update_query)) {
                $_SESSION['message']      = 'Registration updated successfully!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message']      = 'Database error: ' . mysqli_error($conn);
                $_SESSION['message_type'] = 'danger';
            }

            header('Location: admin.php');
            exit;
        } else {
            // INSERT new registration (use logged-in user's info)
            $user_id    = $_SESSION['user']['id'];
            $first_name = $_SESSION['user']['first_name'];
            $last_name  = $_SESSION['user']['last_name'];
            $email      = $_SESSION['user']['email'];

            $insert_query = "INSERT INTO registrations (first_name, last_name, email, workshop_id, level, mode)
                             VALUES ('$first_name', '$last_name', '$email', $workshop_id, '$level', '$mode_radio')";

            if (mysqli_query($conn, $insert_query)) {
                // Increment reserved count
                mysqli_query($conn, "UPDATE workshops SET reserved = reserved + 1 WHERE id = $workshop_id");
                $_SESSION['message']      = 'Registration successful! Thank you.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message']      = 'Database error: ' . mysqli_error($conn);
                $_SESSION['message_type'] = 'danger';
            }

            header('Location: inscription.php');
            exit;
        }
    } else {
        // Validation failed — store errors in session and redirect back
        $error_html = '<ul class="mb-0">';
        foreach ($errors as $err) {
            $error_html .= '<li>' . htmlspecialchars($err) . '</li>';
        }
        $error_html .= '</ul>';

        $_SESSION['message']      = $error_html;
        $_SESSION['message_type'] = 'danger';

        if ($mode === 'edit') {
            $reg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            header("Location: modifier.php?id=$reg_id");
        } else {
            header('Location: inscription.php');
        }
        exit;
    }
} else {
    // Not a POST request
    header('Location: index.php');
    exit;
}