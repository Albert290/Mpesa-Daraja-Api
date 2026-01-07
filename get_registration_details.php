<?php
session_start();
include 'dbconnection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$registration_id = $_GET['id'] ?? null;

if (!$registration_id) {
    echo json_encode(['success' => false, 'message' => 'Registration ID required']);
    exit();
}

$stmt = mysqli_prepare($db, "SELECT * FROM registrations WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $registration_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($registration = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'registration' => $registration
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration not found']);
}
?>
