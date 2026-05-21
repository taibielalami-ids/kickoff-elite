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

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    $_SESSION['message'] = 'Invalid course ID.';
    $_SESSION['message_type'] = 'danger';
    header('Location: gestion_cours.php');
    exit;
}

// Verify course exists
$query = "SELECT id, title FROM workshops WHERE id = $course_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['message'] = 'Course not found.';
    $_SESSION['message_type'] = 'danger';
    header('Location: gestion_cours.php');
    exit;
}

$course = mysqli_fetch_assoc($result);

// Delete the course (registrations cascade via foreign key)
$delete_query = "DELETE FROM workshops WHERE id = $course_id";

if (mysqli_query($conn, $delete_query)) {
    $_SESSION['message'] = 'Course "' . htmlspecialchars($course['title']) . '" deleted successfully!';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Database error: ' . mysqli_error($conn);
    $_SESSION['message_type'] = 'danger';
}

header('Location: gestion_cours.php');
exit;