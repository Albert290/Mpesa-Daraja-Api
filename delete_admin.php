<?php
session_start();
include 'dbconnection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_GET['id'] ?? null;

if ($admin_id && $admin_id != $_SESSION['admin_id']) {
    // Prevent deleting yourself
    $stmt = mysqli_prepare($db, "DELETE FROM admin_users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: admin_users.php?success=deleted");
    } else {
        header("Location: admin_users.php?error=delete_failed");
    }
} else {
    header("Location: admin_users.php?error=delete_failed");
}

exit();
?>
