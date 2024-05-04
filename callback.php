<?php
include 'databaseconnection.php';

header("Content-Type: application/json");
$stkCallbackResponse = file_get_contents('php://input');

// Check if input data is empty
if (empty($stkCallbackResponse)) {
    echo json_encode(array("error" => "Empty input data"));
    exit;
}

$logFile = "Mpesastkresponse.json";
$log = fopen($logFile, "a");
fwrite($log, $stkCallbackResponse);
fclose($log);

// Decode JSON data
$data = json_decode($stkCallbackResponse);

// Check if JSON decoding was successful
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(array("error" => "Error decoding JSON data: " . json_last_error_msg()));
    exit;
}

// Debugging output to inspect the structure of the JSON data
var_dump($data);

// Check if the JSON decoding was successful
if ($data === null) {
    echo "Error decoding JSON data";
    // Optionally, log the error or handle it accordingly
} else {
    // Extract the relevant information
    $MerchantRequestID = isset($data->Body->stkCallback->MerchantRequestID) ? $data->Body->stkCallback->MerchantRequestID : null;
    $CheckoutRequestID = isset($data->Body->stkCallback->CheckoutRequestID) ? $data->Body->stkCallback->CheckoutRequestID : null;
    $ResultCode = isset($data->Body->stkCallback->ResultCode) ? $data->Body->stkCallback->ResultCode : null;
    $ResultDesc = isset($data->Body->stkCallback->ResultDesc) ? $data->Body->stkCallback->ResultDesc : null;
    $Amount = isset($data->Body->stkCallback->CallbackMetadata->Item[0]->Value) ? $data->Body->stkCallback->CallbackMetadata->Item[0]->Value : null;
    $TransactionId = isset($data->Body->stkCallback->CallbackMetadata->Item[1]->Value) ? $data->Body->stkCallback->CallbackMetadata->Item[1]->Value : null;
    $UserPhoneNumber = isset($data->Body->stkCallback->CallbackMetadata->Item[4]->Value) ? $data->Body->stkCallback->CallbackMetadata->Item[4]->Value : null;

    // Check if the transaction was successful (ResultCode == 0)
    if ($ResultCode == 0) {
        // Store the transaction details in the database
        $query = "INSERT INTO transactions (MerchantRequestID,CheckoutRequestID,ResultCode,Amount,MpesaReceiptNumber,PhoneNumber) 
                  VALUES ('$MerchantRequestID','$CheckoutRequestID','$ResultCode','$Amount','$TransactionId','$UserPhoneNumber')";

        if (mysqli_query($db, $query)) {
            // Query executed successfully
            echo "Transaction details inserted successfully.";
        } else {
            // Error occurred
            echo "Error: " . mysqli_error($db);
        }
    } else {
        echo "Transaction was not successful. ResultCode: $ResultCode, ResultDesc: $ResultDesc";
    }
}
?>