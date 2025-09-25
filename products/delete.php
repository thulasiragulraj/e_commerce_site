<?php
header("Content-Type: application/json");

$pdo = require __DIR__ . '/../config/db.php'; // DB connection
require __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt_secret = "your_super_secret_key";

// ---- Method validation ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success"=>false, "message"=>"Only POST method allowed"]);
    exit;
}

// ---- JWT Auth ----
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["success"=>false, "message"=>"Authorization token missing"]);
    exit;
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);

try {
    $decoded = JWT::decode($token, new Key($jwt_secret, 'HS256'));
    $user_id = $decoded->user_id;

    // ---- Fetch user role from DB ----
    $stmt = $pdo->prepare("SELECT role FROM users1 WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["success"=>false, "message"=>"Only admin can access this API"]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success"=>false, "message"=>"Invalid token: ".$e->getMessage()]);
    exit;
}

// ---- Input Validation ----
$input = json_decode(file_get_contents("php://input"), true);

if (empty($input['product_id'])) {
    echo json_encode(["success"=>false, "message"=>"product_id is required"]);
    exit;
}

$product_id = $input['product_id'];

try {
    // ---- Check if product exists ----
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(["success"=>false, "message"=>"Product not found"]);
        exit;
    }

    // ---- Delete Product ----
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);

    echo json_encode([
        "success"=>true,
        "message"=>"Product deleted successfully",
        "data"=>[
            "product_id"=>$product_id,
            "product_name"=>$product['product_name']
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success"=>false,
        "message"=>"Database error: ".$e->getMessage()
    ]);
}
