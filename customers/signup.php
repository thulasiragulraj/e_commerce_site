<?php
header("Content-Type: application/json");

// ---- DB Connection ----
$pdo = require_once __DIR__ . '/../config/db.php'; // Adjust path to your DB connection file

// ---- Get POST data ----
$input = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (empty($input['name']) || empty($input['phone']) || empty($input['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Name, phone, and password are required"
    ]);
    exit;
}

$name = trim($input['name']);
$phone = trim($input['phone']);
$password = trim($input['password']); // Plain password from user
$email = !empty($input['email']) ? trim($input['email']) : null;
$address = !empty($input['address']) ? trim($input['address']) : null;

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if phone or email already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = :phone OR (email IS NOT NULL AND email = :email)");
    $stmt->execute([
        ':phone' => $phone,
        ':email' => $email
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Phone or email already registered"
        ]);
        exit;
    }

    // Insert new customer
    $insert = $pdo->prepare("
        INSERT INTO customers (name, phone, email, address, password)
        VALUES (:name, :phone, :email, :address, :password)
    ");
    $insert->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email,
        ':address' => $address,
        ':password' => $hashedPassword
    ]);

    $customer_id = $pdo->lastInsertId();

    echo json_encode([
        "success" => true,
        "message" => "Customer registered successfully",
        "customer_id" => $customer_id
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
