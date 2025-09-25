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
$item_id = $input['item_id'] ?? null;
$product_name = $input['product_name'] ?? null;
$price = $input['price'] ?? null;

// Build dynamic SET query
$fields = [];
$params = [];

if ($item_id !== null) {
    $fields[] = "item_id = ?";
    $params[] = $item_id;
}
if ($product_name !== null) {
    $fields[] = "product_name = ?";
    $params[] = $product_name;
}
if ($price !== null) {
    $fields[] = "price = ?";
    $params[] = $price;
}

if (empty($fields)) {
    echo json_encode(["success"=>false, "message"=>"No fields to update"]);
    exit;
}

$params[] = $product_id; // WHERE product_id = ?

$sql = "UPDATE products SET ".implode(", ", $fields)." WHERE product_id = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // ---- Fetch updated product ----
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"=>true,
        "message"=>"Product updated successfully",
        "data"=>$product
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success"=>false,
        "message"=>"Database error: ".$e->getMessage()
    ]);
}
