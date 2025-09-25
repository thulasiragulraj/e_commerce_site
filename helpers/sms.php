<?php
function sendSMS($phone, $otp) {
    $authKey = "YOUR_MSG91_AUTH_KEY"; // MSG91 dashboard-ல generate பண்ணு
    $flowId  = "YOUR_FLOW_ID";        // OTP transactional flow create பண்ணி எடுத்த ID
    $senderId = "SENDERID";           // Example: SNDOTP

    $url = "https://api.msg91.com/api/v5/flow/";

    $data = [
        "flow_id" => $flowId,
        "sender"  => $senderId,
        "mobiles" => "91$phone",
        "otp"     => $otp
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            "authkey: $authKey",
            "Content-Type: application/json"
        ],
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("SMS API error: $err");
        return false;
    }

    // Optionally: check MSG91 response success
    $resp = json_decode($response, true);
    if (isset($resp['type']) && $resp['type'] === 'success') {
        return true;
    } else {
        error_log("SMS send failed: " . $response);
        return false;
    }
}
