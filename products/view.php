<?php
header("Content-Type: application/json");

$pdo = require __DIR__ . '/../config/db.php'; // DB connection
require __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt_secret = "your_super_secret_key";

// ---- Method validation ----
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success"=>false, "message"=>"Only GET method allowed"]);
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

// ---- Fetch Products with Item Info ----
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.product_id,
            p.product_name,
            p.price,
            p.item_id,
            i.item_name AS category
        FROM products p
        JOIN items i ON p.item_id = i.id
        ORDER BY p.id ASC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"=>true,
        "message"=>"Products fetched successfully",
        "data"=>$products
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success"=>false,
        "message"=>"Database error: ".$e->getMessage()
    ]);
}
