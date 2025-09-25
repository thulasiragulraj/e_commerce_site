<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/sms.php';
require_once __DIR__ . '/../helpers/phone.php';

$input = json_decode(file_get_contents("php://input"), true);
if (!$input || empty($input['phone'])) sendError("phone is required");

$rawPhone = trim($input['phone']);
$phone = normalizeIndianPhone($rawPhone);
if (!preg_match('/^[6-9]\d{9}$/', $phone)) sendError("Invalid Indian phone number");

try {
    // Last OTP check for rate limiting
    $stmt = $pdo->prepare("SELECT * FROM otp_verifications WHERE phone=? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$phone]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['sent_count'] >= 3 && strtotime($row['created_at']) > strtotime('-24 hours')) {
        sendError("OTP send limit reached. Try after some time.", 429);
    }

    // Generate OTP
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
    $expires_at = (new DateTime())->modify('+5 minutes')->format('Y-m-d H:i:s');
    $sent_count = ($row && strtotime($row['expires_at']) > time()) ? $row['sent_count'] + 1 : 1;

    // Save to DB
    $stmt = $pdo->prepare("INSERT INTO otp_verifications (phone, otp_hash, expires_at, sent_count) VALUES (?,?,?,?)");
    $stmt->execute([$phone, $otp_hash, $expires_at, $sent_count]);

    // Send OTP via MSG91
    $smsOk = sendSMS($phone, $otp);
    if (!$smsOk) {
        sendError("Failed to send OTP. Try again later.", 500);
    }

    sendSuccess([], "OTP sent to +91" . $phone);

} catch (Exception $e) {
    sendError("Server error: " . $e->getMessage(), 500);
}
