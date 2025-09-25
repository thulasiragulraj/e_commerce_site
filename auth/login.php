<?php
header("Content-Type: application/json");

$pdo = require __DIR__ . '/../config/db.php'; // DB connection
require __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;

$jwt_secret = "your_super_secret_key";

// ---- Method validation ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success"=>false, "message"=>"Only POST method allowed"]);
    exit;
}

// ---- Input Validation ----
$input = json_decode(file_get_contents("php://input"), true);
$required = ['email', 'password'];

foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(["success"=>false, "message"=>"$field is required"]);
        exit;
    }
}

$email = $input['email'];
$password = $input['password'];

try {
    // ---- Fetch user by email ----
    $stmt = $pdo->prepare("SELECT * FROM users1 WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(["success"=>false, "message"=>"Invalid email or password"]);
        exit;
    }

    // ---- Generate JWT Token ----
    $issuedAt = time();
    $expireAt = $issuedAt + (60 * 60); // 1 hour validity

    $payload = [
        "iss" => "http://localhost",
        "iat" => $issuedAt,
        "exp" => $expireAt,
        "user_id" => $user['id'],
        "email" => $user['email'],
        "role" => $user['role']
    ];

    $jwt = JWT::encode($payload, $jwt_secret, 'HS256');

    // ---- Success Response ----
    echo json_encode([
        "success"=>true,
        "message"=>"Login successful",
        "data"=>[
            "id"=>$user['id'],
            "name"=>$user['name'],
            "email"=>$user['email'],
            "role"=>$user['role'],
            "token"=>$jwt,
            "token_expiry"=>$expireAt
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success"=>false,
        "message"=>"Database error: ".$e->getMessage()
    ]);
}
