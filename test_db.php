<?php
/**
 * Test database connection script
 */

require_once 'config/db.php';

echo "<h2>Database Connection Test</h2>";

if ($conn) {
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test query
    $query = "SELECT COUNT(*) as user_count FROM users";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Users in database: " . $row['user_count'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Query failed: " . mysqli_error($conn) . "</p>";
    }
    
    // Test workshops query
    $query = "SELECT COUNT(*) as workshop_count FROM workshops";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p>Workshops in database: " . $row['workshop_count'] . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Query failed: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_close($conn);
} else {
    echo "<p style='color: red;'>✗ Database connection failed!</p>";
    echo "<p>Error: " . mysqli_connect_error() . "</p>";
}