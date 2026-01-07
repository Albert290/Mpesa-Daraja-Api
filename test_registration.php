<?php
// Test the registration processor with sample data
$testData = [
    'fullName' => 'Test User',
    'email' => 'test@example.com',
    'phone' => '254712345678',
    'gender' => 'Male',
    'raceCategory' => '10 km',
    'firstTime' => 'Yes',
    'emergencyName' => 'Emergency Contact',
    'emergencyPhone' => '254700000000',
    'relationship' => 'Friend',
    'transport' => 'Self-arranged transport',
    'totalAmount' => 1000
];

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
file_put_contents('php://input', json_encode($testData));

// Capture output
ob_start();
include 'process_registration.php';
$output = ob_get_clean();

echo "Output: " . $output . "\n";
?>
