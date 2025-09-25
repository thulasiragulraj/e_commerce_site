<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../helpers/response.php';
require_once __DIR__.'/../../helpers/phone.php';

$input = json_decode(file_get_contents("php://input"), true);
if (!$input || empty($input['phone']) || !isset($input['otp'])) sendError("phone and otp required");

$phone = normalizeIndianPhone($input['phone']);
$otp = trim($input['otp']);
if (!preg_match('/^[6-9]\d{9}$/', $phone)) sendError("Invalid phone");
if (!preg_match('/^\d{4,6}$/', $otp)) sendError("Invalid OTP format");

try {
    // fetch latest unverified OTP for phone
    $stmt = $pdo->prepare("SELECT * FROM otp_verifications WHERE phone=? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$phone]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) sendError("No OTP requested for this phone", 400);

    // already verified?
    if ($row['verified']) {
        sendSuccess([], "Phone already verified");
    }

    // check expiry
    if (strtotime($row['expires_at']) < time()) {
        sendError("OTP expired. Request a new one.", 400);
    }

    // attempts limit
    if ($row['attempts'] >= 5) {
        sendError("Too many wrong attempts. Request a new OTP.", 429);
    }

    // verify hashed OTP
    if (password_verify($otp, $row['otp_hash'])) {
        // mark verified
        $pdo->prepare("UPDATE otp_verifications SET verified=1 WHERE id=?")->execute([$row['id']]);
        // optionally set phone_verified_at in users when registering
        sendSuccess([], "OTP verified successfully");
    } else {
        // increment attempts
        $pdo->prepare("UPDATE otp_verifications SET attempts = attempts + 1 WHERE id=?")->execute([$row['id']]);
        sendError("Invalid OTP", 400);
    }

} catch (Exception $e) {
    sendError("Server error: " . $e->getMessage(), 500);
}
