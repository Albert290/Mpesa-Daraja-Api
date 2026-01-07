<?php
session_start();
include 'dbconnection.php';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    error_log("Login attempt: " . $username);
    
    $stmt = mysqli_prepare($db, "SELECT id, username, email, password_hash, role FROM admin_users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        error_log("User found: " . $user['username']);
        error_log("Stored hash: " . $user['password_hash']);
        
        if (password_verify($password, $user['password_hash'])) {
            error_log("Password verified successfully");
            // Login successful
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            
            // Update last login
            mysqli_query($db, "UPDATE admin_users SET last_login = NOW() WHERE id = " . $user['id']);
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            error_log("Password verification failed");
        }
    } else {
        error_log("User not found");
    }
    
    // Login failed
    header("Location: admin_login.php?error=1");
    exit();
}

// If not POST request, redirect to login
header("Location: admin_login.php");
exit();
?>
