<?php
include 'dbconnection.php';
include 'accessToken.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

// Log the input for debugging
error_log("Registration input: " . print_r($input, true));

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Validate required fields
$required = ['fullName', 'email', 'phone', 'gender', 'raceCategory', 'firstTime', 'emergencyName', 'emergencyPhone', 'relationship', 'transport'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit;
    }
}

// Validate phone number
if (!preg_match('/^254\d{9}$/', $input['phone'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

// Calculate total amount (server-side validation)
$total = 1000; // Base registration fee
if (isset($input['finisherMedal']) && $input['finisherMedal'] === 'Yes') {
    $total += 1500;
}
if ($input['transport'] === 'Nairobi group transport') {
    $total += 5000;
}

// Validate client-sent total
if (isset($input['totalAmount']) && $input['totalAmount'] != $total) {
    echo json_encode(['success' => false, 'message' => 'Amount mismatch']);
    exit;
}

// Generate unique order ID
$orderID = 'REG' . time() . rand(1000, 9999);

// Store registration in database
$stmt = mysqli_prepare($db, "INSERT INTO registrations (order_id, full_name, email, phone, gender, race_category, first_time, emergency_name, emergency_phone, relationship, medical_conditions, allergies, finisher_medal, transport, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Prepare variables for binding
$medicalConditions = $input['medicalConditions'] ?? '';
$allergies = $input['allergies'] ?? '';
$finisherMedal = $input['finisherMedal'] ?? 'No';

mysqli_stmt_bind_param($stmt, "ssssssssssssssd", 
    $orderID,
    $input['fullName'],
    $input['email'],
    $input['phone'],
    $input['gender'],
    $input['raceCategory'],
    $input['firstTime'],
    $input['emergencyName'],
    $input['emergencyPhone'],
    $input['relationship'],
    $medicalConditions,
    $allergies,
    $finisherMedal,
    $input['transport'],
    $total
);

if (!mysqli_stmt_execute($stmt)) {
    error_log("Database error: " . mysqli_error($db));
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . mysqli_error($db)]);
    exit;
}

// Initiate M-Pesa STK Push
date_default_timezone_set('Africa/Nairobi');
$processrequestUrl = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$callbackurl = 'https://pamella-unweeping-amina.ngrok-free.dev/Mpesa-Daraja-Api/registration_callback.php';
$passkey = "edce4ac089090904e2d080385482de18d53c287385a86bd23dd791d7992af3db";
$BusinessShortCode = '4161411';
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $passkey . $Timestamp);

$stkpushheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $access_token];

$curl_post_data = array(
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $total,
    'PartyA' => $input['phone'],
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $input['phone'],
    'CallBackURL' => $callbackurl,
    'AccountReference' => $orderID,
    'TransactionDesc' => 'Race Registration - ' . $input['fullName']
);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $processrequestUrl);
curl_setopt($curl, CURLOPT_HTTPHEADER, $stkpushheader);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));

$curl_response = curl_exec($curl);
$data = json_decode($curl_response, true);

if (isset($data['ResponseCode']) && $data['ResponseCode'] == "0") {
    // Update registration with checkout request ID
    $checkoutRequestID = $data['CheckoutRequestID'];
    mysqli_query($db, "UPDATE registrations SET checkout_request_id = '$checkoutRequestID' WHERE order_id = '$orderID'");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful! Payment request sent to your phone.',
        'order_id' => $orderID,
        'amount' => $total
    ]);
} else {
    error_log("M-Pesa error: " . $curl_response);
    echo json_encode(['success' => false, 'message' => 'Payment request failed: ' . ($data['errorMessage'] ?? 'Unknown error')]);
}

curl_close($curl);
