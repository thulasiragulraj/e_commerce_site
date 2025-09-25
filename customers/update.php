<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';  // DB connection
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload for JWT

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ---- JWT secret key ----
$jwt_secret = "YOUR_SECRET_KEY";

// ---- Method validation ----
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["success"=>false, "message"=>"Only PUT method allowed"]);
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
    $customer_id = $decoded->sub; // Assuming JWT sub = customer_id

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success"=>false, "message"=>"Invalid token: ".$e->getMessage()]);
    exit;
}

// ---- Input ----
$input = json_decode(file_get_contents("php://input"), true);

// Allowed fields to update
$fields = ['name', 'email', 'phone', 'address'];
$updateData = [];
$params = [];

foreach ($fields as $field) {
    if (isset($input[$field]) && !empty($input[$field])) {
        $updateData[] = "$field = :$field";
        $params[":$field"] = trim($input[$field]);
    }
}

if (empty($updateData)) {
    echo json_encode(["success"=>false, "message"=>"No valid fields provided to update"]);
    exit;
}

$params[":id"] = $customer_id;

try {
    $sql = "UPDATE customers SET ".implode(", ", $updateData)." WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Fetch updated customer
    $stmt = $pdo->prepare("SELECT id, name, phone, email, address, status, created_at FROM customers WHERE id = :id");
    $stmt->execute([':id' => $customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "message" => "Customer updated successfully",
        "customer" => $customer
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: ".$e->getMessage()
    ]);
}
