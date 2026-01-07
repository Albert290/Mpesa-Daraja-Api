<?php
session_start();
include 'dbconnection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        header("Location: admin_users.php?error=failed");
        exit();
    }
    
    // Check if username or email already exists
    $check_stmt = mysqli_prepare($db, "SELECT id FROM admin_users WHERE username = ? OR email = ?");
    mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        header("Location: admin_users.php?error=exists");
        exit();
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new admin
    $stmt = mysqli_prepare($db, "INSERT INTO admin_users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $password_hash, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: admin_users.php?success=added");
    } else {
        header("Location: admin_users.php?error=failed");
    }
    exit();
}

// If not POST request, redirect back
header("Location: admin_users.php");
exit();
?>
