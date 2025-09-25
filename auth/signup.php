<?php
header("Content-Type: application/json");

// ---- DB Connection ----
$pdo = require_once __DIR__ . '/../config/db.php'; // ✅ DB connection include
require __DIR__ . "/../libs/phpqrcode/qrlib.php";   // ✅ phpqrcode library
require __DIR__ . "/../vendor/autoload.php";        // ✅ composer autoload for JWT

use Firebase\JWT\JWT;

// ---- JWT Secret Key ----
$jwt_secret = "your_super_secret_key"; 

// ---- Method Validation ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Only POST method allowed"]);
    exit;
}

// ---- Input Validation ----
$input = json_decode(file_get_contents("php://input"), true);
$required = ['name', 'email','phone', 'password'];

foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(["success" => false, "message" => "$field is required"]);
        exit;
    }
}

$name = $input['name'];
$phone = $input['phone'];
$email = $input['email'];
$password = password_hash($input['password'], PASSWORD_BCRYPT);
$role = 'admin'; // ✅ role added

try {
    // ---- Insert User ----
$stmt = $pdo->prepare("INSERT INTO users1 (name, phone, email, password, role) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$name, $phone, $email, $password, $role]);
    $user_id = $pdo->lastInsertId();

    // ---- Create Slug ----
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . $user_id;

    // ---- Generate QR Code ----
    $qrDir = __DIR__ . "/../qr_img";
    if (!file_exists($qrDir)) {
        mkdir($qrDir, 0755, true);
    }

    $qrFile = $qrDir . "/" . $slug . ".png";

    // QR code data contains user info + slug
    $qrData = "UserID: $user_id\nName: $name\nPhone: $phone\nEmail: $email\nSlug: $slug";

    // Generate QR code using phpqrcode
    QRcode::png($qrData, $qrFile, QR_ECLEVEL_L, 5);

    // ---- Update user with QR path ----
    $stmt = $pdo->prepare("UPDATE users1 SET qr_code = ? WHERE id = ?");
    $stmt->execute([basename($qrFile), $user_id]);

    // ---- Generate JWT Token ----
    $issuedAt = time();
    $expireAt = $issuedAt + (60 * 60); // 1 hour validity
    $payload = [
        "iss" => "http://localhost",
        "iat" => $issuedAt,
        "exp" => $expireAt,
        "user_id" => $user_id,
        "email" => $email
    ];
    $jwt = JWT::encode($payload, $jwt_secret, 'HS256');

    // ---- Success Response ----
    echo json_encode([
        "success" => true,
        "message" => "User registered successfully",
        "data" => [
            "id" => $user_id,
            "name" => $name,
            "phone" => $phone,
            "email" => $email,
            "slug" => $slug,
            "qr_code" => "qr_img/" . basename($qrFile),
            "role" => $role, // ✅ role added in response
            "token" => $jwt,
            "token_expiry" => $expireAt
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
