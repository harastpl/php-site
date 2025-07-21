<?php
// This file handles fetching and caching the PhonePe OAuth access token.

$accessToken = '';

// 1. Check the database for an existing, non-expired token
$stmt = $pdo->prepare("SELECT access_token, expires_at FROM api_tokens WHERE service_name = 'phonepe' LIMIT 1");
$stmt->execute();
$token_data = $stmt->fetch();

if ($token_data && time() < $token_data['expires_at']) {
    // Token exists and is valid, use it
    $accessToken = $token_data['access_token'];
} else {
    // No valid token found, so we must fetch a new one
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => PHONEPE_BASE_URL . '/v1/oauth/token',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => http_build_query([
        'client_id' => PHONEPE_CLIENT_ID,
        'client_secret' => PHONEPE_CLIENT_SECRET,
        'grant_type' => 'client_credentials',
        'client_version' => 1 // THIS IS THE MISSING FIELD THAT HAS BEEN ADDED
      ]),
      CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      die("cURL Error getting token: " . $err);
    }

    $getToken = json_decode($response, true);

    if (isset($getToken['access_token'])) {
        $accessToken = $getToken['access_token'];
        $expires_at = time() + ($getToken['expires_in'] ?? 86400); 

        // 2. Save the new token to the database
        $stmt_save = $pdo->prepare(
            "INSERT INTO api_tokens (service_name, access_token, expires_at) 
             VALUES ('phonepe', ?, ?) 
             ON DUPLICATE KEY UPDATE access_token = ?, expires_at = ?"
        );
        $stmt_save->execute([$accessToken, $expires_at, $accessToken, $expires_at]);

    } else {
        die("Could not get access token from PhonePe. API Response: " . $response);
    }
}

if (empty($accessToken)) {
    die("Failed to obtain a valid access token for payment.");
}
?>