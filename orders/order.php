<?php
header("Content-Type: application/json");

// ---- DB Connection ----
$pdo = require_once __DIR__ . '/../config/db.php'; // Adjust path

// ---- Get POST data ----
$input = json_decode(file_get_contents("php://input"), true);

if (empty($input['customer_id']) || empty($input['product_id']) || empty($input['quantity'])) {
    echo json_encode([
        "success" => false,
        "message" => "customer_id, product_id, and quantity are required"
    ]);
    exit;
}

$customer_id = (int)$input['customer_id'];
$product_id = trim($input['product_id']);
$quantity = (int)$input['quantity'];
$payment_method_id=(int)$input['payment_method_id'];

try {
    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = :customer_id");
    $stmt->execute([':customer_id' => $customer_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Customer not found"
        ]);
        exit;
    }

    // Fetch product price and available quantity
    $stmt = $pdo->prepare("SELECT price, product_name, stock_quantity FROM products WHERE product_id = :product_id");
    $stmt->execute([':product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode([
            "success" => false,
            "message" => "Product not found"
        ]);
        exit;
    }

    // Check stock availability
    if ($quantity > $product['stock_quantity']) {
        echo json_encode([
            "success" => false,
            "message" => "Insufficient product quantity. Available: " . $product['stock_quantity']
        ]);
        exit;
    }

    $price = $product['price'];
    $total_price = $price * $quantity;

    // Insert order
    $insert = $pdo->prepare("
        INSERT INTO orders (customer_id, product_id, quantity, price, total_price, payment_method_id)
        VALUES (:customer_id, :product_id, :quantity, :price, :total_price, :payment_method_id)
    ");
    $insert->execute([
        ':customer_id' => $customer_id,
        ':product_id' => $product_id,
        ':quantity' => $quantity,
        ':price' => $price,
        ':total_price' => $total_price,
        ':payment_method_id' => $payment_method_id
    ]);

    $order_id = $pdo->lastInsertId();

    // Update product stock
    $updateProduct = $pdo->prepare("
        UPDATE products
        SET stock_quantity = stock_quantity - :quantity
        WHERE product_id = :product_id
    ");
    $updateProduct->execute([
        ':quantity' => $quantity,
        ':product_id' => $product_id
    ]);

    // Return success response
    echo json_encode([
        "success" => true,
        "message" => "Order placed successfully",
        "order" => [
            "order_id" => $order_id,
            "customer_id" => $customer_id,
            "product_id" => $product_id,
            "product_name" => $product['product_name'],
            "quantity" => $quantity,
            "price" => $price,
            "total_price" => $total_price,
            "payment_method_id" =>  $payment_method_id,
            "status" => "pending",
            "order_date" => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
