<?php
header("Content-Type: application/json");

$pdo = require __DIR__ . '/../config/db.php'; // DB connection
require __DIR__ . '/../vendor/autoload.php';  // Composer for JWT
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
$required = ['item_id', 'product_name', 'price','stock_quantity'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(["success"=>false, "message"=>"$field is required"]);
        exit;
    }
}

$item_id = $input['item_id'];
$product_name = $input['product_name'];
$price = $input['price'];
$stock_quantity = $input['stock_quantity'];

try {
    // ---- Insert Product ----
    $stmt = $pdo->prepare("INSERT INTO products (item_id, product_name, price,stock_quantity) VALUES (?, ?, ?, ?)");
    $stmt->execute([$item_id, $product_name, $price, $stock_quantity]);
    $product_id = $pdo->lastInsertId();

    // ---- Fetch newly inserted product ----
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"=>true,
        "message"=>"Product created successfully",
        "data"=>$product
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success"=>false,
        "message"=>"Database error: ".$e->getMessage()
    ]);
}
