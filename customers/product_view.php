<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt_secret = "YOUR_SECRET_KEY";

// ---- JWT Auth ----
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["success"=>false,"message"=>"Authorization token missing"]);
    exit;
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);

try {
    $decoded = JWT::decode($token, new Key($jwt_secret, 'HS256'));
    $customer_id = $decoded->sub; // logged-in customer

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success"=>false,"message"=>"Invalid token"]);
    exit;
}

// ---- Get search term if exists ----
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// ---- Fetch products ----
try {
    if ($search) {
        $stmt = $pdo->prepare("
            SELECT product_id, product_name, price, stock_quantity
            FROM products
            WHERE product_name LIKE :search AND stock_quantity > 0
            ORDER BY product_name ASC
        ");
        $stmt->execute([':search' => "%$search%"]);
    } else {
        $stmt = $pdo->query("
            SELECT product_id, product_name, price, stock_quantity
            FROM products
            WHERE stock_quantity > 0
            ORDER BY product_name ASC
        ");
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "products" => $products
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: ".$e->getMessage()
    ]);
}
