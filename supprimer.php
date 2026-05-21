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

$reg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reg_id <= 0) {
    $_SESSION['message']      = 'Invalid registration ID.';
    $_SESSION['message_type'] = 'danger';
    header('Location: admin.php');
    exit;
}

// Get the workshop_id before deleting (to decrement reserved count)
$query  = "SELECT workshop_id FROM registrations WHERE id = $reg_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 1) {
    $registration = mysqli_fetch_assoc($result);
    $workshop_id  = $registration['workshop_id'];

    // Delete the registration
    $delete_query = "DELETE FROM registrations WHERE id = $reg_id";
    if (mysqli_query($conn, $delete_query)) {
        // Decrement reserved count for the workshop (if not already 0)
        mysqli_query($conn, "UPDATE workshops SET reserved = GREATEST(reserved - 1, 0) WHERE id = $workshop_id");
        $_SESSION['message']      = 'Registration deleted successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message']      = 'Database error: ' . mysqli_error($conn);
        $_SESSION['message_type'] = 'danger';
    }
} else {
    $_SESSION['message']      = 'Registration not found.';
    $_SESSION['message_type'] = 'danger';
}

header('Location: admin.php');
exit;
