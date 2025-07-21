<?php
session_start();
require 'includes/config.php';
require 'includes/db.php';
require 'includes/functions.php';

// Check if the merchant order ID ('moid') is present in the URL
if (!isset($_GET['moid'])) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Error: Invalid callback. Merchant Order ID not found.'
    ];
    header('Location: orders.php');
    exit();
}

$merchantOrderId = $_GET['moid']; // This is the 'payment_id' in your orders table

// --- Fetch the latest access token from the database ---
try {
    $stmt_token = $pdo->query("SELECT access_token FROM api_tokens ORDER BY id DESC LIMIT 1");
    $token_row = $stmt_token->fetch();
    if ($token_row && !empty($token_row['access_token'])) {
        $accessToken = $token_row['access_token'];
    } else {
        throw new Exception("No valid access token found in database.");
    }
} catch (Exception $e) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Error: Could not retrieve payment gateway credentials. ' . $e->getMessage()
    ];
    header('Location: orders.php');
    exit();
}

// The API endpoint from the documentation
$statusUrl = PHONEPE_BASE_URL . '/checkout/v2/order/' . $merchantOrderId . '/status';

$curl = curl_init();

// Set cURL options according to the documentation
curl_setopt_array($curl, array(
  CURLOPT_URL => $statusUrl,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    // --- FIX: Changed back to 'O-Bearer' as per the documentation ---
    'Authorization: O-Bearer ' . $accessToken
    // --- FIX: Removed the X-VERIFY header as it's not in the documentation ---
  ),
));

$response = curl_exec($curl);
$error = curl_error($curl);

curl_close($curl);

if ($error) {
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Error communicating with payment gateway: ' . $error
    ];
    header('Location: orders.php');
    exit();
}

$responseData = json_decode($response, true);

// Check for the 'state' key in the response, which indicates a successful query
if (isset($responseData['state'])) {
    // --- API call was successful, now check the payment state ---
    $paymentState = $responseData['state'];
    // The transactionId is now inside the 'paymentDetails' array
    $transactionId = $responseData['paymentDetails'][0]['transactionId'] ?? null;

    if ($paymentState === 'COMPLETED') {
        try {
            $stmt = $pdo->prepare(
                "UPDATE orders SET payment_status = 'Paid', transaction_id = ? WHERE payment_id = ?"
            );
            $stmt->execute([$transactionId, $merchantOrderId]);

            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Payment successful! Your order has been updated.'
            ];
            unset($_SESSION['cart']);

        } catch (PDOException $e) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Payment was successful, but we failed to update your order. Please contact support. DB Error: ' . $e->getMessage()
            ];
        }
    } else {
        // --- Payment was not completed (e.g., FAILED, PENDING) ---
        try {
            $stmt = $pdo->prepare(
                "UPDATE orders SET payment_status = ?, transaction_id = ? WHERE payment_id = ?"
            );
            $stmt->execute([ucfirst(strtolower($paymentState)), $transactionId, $merchantOrderId]);
        } catch (PDOException $e) {
            // Log this error
        }

        $_SESSION['flash_message'] = [
            'type' => 'warning',
            'message' => 'Payment was not completed. Status: ' . $paymentState
        ];
    }
} else {
    // --- API call failed or returned an error ---
    $errorMessage = $responseData['message'] ?? 'Unknown error from payment gateway.';
    $debugInfo = " [Debug Info: Raw Response: " . htmlspecialchars($response) . "]";
    
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Could not verify payment status. Reason: ' . $errorMessage . $debugInfo
    ];
}

header('Location: orders.php');
exit();
