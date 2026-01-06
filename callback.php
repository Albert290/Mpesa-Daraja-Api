<?php
include 'dbconnection.php';
header("Content-Type: application/json");

$stkCallbackResponse = file_get_contents('php://input');
$logFile = "Mpesastkresponse.json";

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
        $Amount = $TransactionId = $UserPhoneNumber = '';
        
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            if ($item['Name'] == 'Amount') $Amount = $item['Value'];
            if ($item['Name'] == 'MpesaReceiptNumber') $TransactionId = $item['Value'];
            if ($item['Name'] == 'PhoneNumber') $UserPhoneNumber = $item['Value'];
        }
        
        $stmt = mysqli_prepare($db, "INSERT INTO transactions (MerchantRequestID,CheckoutRequestID,ResultCode,Amount,MpesaReceiptNumber,PhoneNumber) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "ssssss", $MerchantRequestID, $CheckoutRequestID, $ResultCode, $Amount, $TransactionId, $UserPhoneNumber);
        mysqli_stmt_execute($stmt);
    }
}

echo json_encode(["ResultCode" => 0, "ResultDesc" => "Success"]);
