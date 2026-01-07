<?php
// Generate proper password hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password hash: " . $hash . "\n";

// Test verification
if (password_verify($password, $hash)) {
    echo "Password verification works!\n";
} else {
    echo "Password verification failed!\n";
}
?>
