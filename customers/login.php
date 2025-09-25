<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php'; // DB connection
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ---- JWT secret key ----
$jwt_secret = "YOUR_SECRET_KEY"; // Change to a strong secret key
$jwt_expiry = 3600; // 1 hour validity

$input = json_decode(file_get_contents("php://input"), true);

if (empty($input['phone']) || empty($input['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Phone and password are required"
    ]);
    exit;
}

$phone = trim($input['phone']);
$password = trim($input['password']);

try {
    // Fetch customer by phone
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE phone = :phone");
    $stmt->execute([':phone' => $phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode([
            "success" => false,
            "message" => "Customer not found"
        ]);
        exit;
    }

    // Verify hashed password
    if (!password_verify($password, $customer['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid password"
        ]);
        exit;
    }

    // Generate JWT token
    $payload = [
        'iss' => 'yourdomain.com',       // Issuer
        'iat' => time(),                 // Issued at
        'exp' => time() + $jwt_expiry,   // Expiry time
        'sub' => $customer['id'],        // Subject (customer ID)
        'phone' => $customer['phone']    // Customer phone
    ];

    $token = JWT::encode($payload, $jwt_secret, 'HS256');

    // Return customer details + token
    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "token" => $token,
        "customer" => [
            "id" => $customer['id'],
            "name" => $customer['name'],
            "phone" => $customer['phone'],
            "email" => $customer['email'],
            "address" => $customer['address'],
            "status" => $customer['status'],
            "created_at" => $customer['created_at']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
