<?php
include 'dbconnection.php';

// Test database connection
if ($db) {
    echo "Database connected successfully\n";
    
    // Test if registrations table exists
    $result = mysqli_query($db, "SHOW TABLES LIKE 'registrations'");
    if (mysqli_num_rows($result) > 0) {
        echo "Registrations table exists\n";
    } else {
        echo "Registrations table NOT found\n";
    }
} else {
    echo "Database connection failed\n";
}
?>
