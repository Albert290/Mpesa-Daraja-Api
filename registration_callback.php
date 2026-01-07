<?php
include 'dbconnection.php';
header("Content-Type: application/json");

$stkCallbackResponse = file_get_contents('php://input');
$logFile = "registration_payments.json";

if ($log = fopen($logFile, "a")) {
    fwrite($log, $stkCallbackResponse . "\n");
    fclose($log);
}

$data = json_decode($stkCallbackResponse, true);

if ($data && isset($data['Body']['stkCallback'])) {
    $callback = $data['Body']['stkCallback'];
    $MerchantRequestID = $callback['MerchantRequestID'];
    $CheckoutRequestID = $callback['CheckoutRequestID'];
    $ResultCode = $callback['ResultCode'];
    
    if ($ResultCode == 0 && isset($callback['CallbackMetadata']['Item'])) {
        // Payment successful - extract details
        $Amount = $MpesaReceiptNumber = $UserPhoneNumber = $TransactionDate = '';
        
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            if ($item['Name'] == 'Amount') $Amount = $item['Value'];
            if ($item['Name'] == 'MpesaReceiptNumber') $MpesaReceiptNumber = $item['Value'];
            if ($item['Name'] == 'PhoneNumber') $UserPhoneNumber = $item['Value'];
            if ($item['Name'] == 'TransactionDate') $TransactionDate = $item['Value'];
        }
        
        // Update registration status to paid
        $stmt = mysqli_prepare($db, "UPDATE registrations SET payment_status = 'paid' WHERE checkout_request_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $CheckoutRequestID);
        mysqli_stmt_execute($stmt);
        
        // Store transaction details
        $stmt2 = mysqli_prepare($db, "INSERT INTO transactions (MerchantRequestID,CheckoutRequestID,ResultCode,Amount,MpesaReceiptNumber,PhoneNumber,TransactionDate) VALUES (?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt2, "ssissss", $MerchantRequestID, $CheckoutRequestID, $ResultCode, $Amount, $MpesaReceiptNumber, $UserPhoneNumber, $TransactionDate);
        mysqli_stmt_execute($stmt2);
        
    } else {
        // Payment failed - update status
        $stmt = mysqli_prepare($db, "UPDATE registrations SET payment_status = 'failed' WHERE checkout_request_id = ?");
        mysqli_stmt_bind_param($stmt, "s", $CheckoutRequestID);
        mysqli_stmt_execute($stmt);
    }
}

echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
