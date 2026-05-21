<?php
/**
 * Database configuration file
 * MAMP default settings
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'atelier_web_db';
$db_port = 8889;

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, 'utf8mb4');