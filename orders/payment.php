<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Razorpay\Api\Api;

$jwt_secret = "YOUR_SECRET_KEY";
$razorpay_key_id = "YOUR_RAZORPAY_KEY_ID";
$razorpay_key_secret = "YOUR_RAZORPAY_KEY_SECRET";

// JWT auth
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["success"=>false,"message"=>"Authorization token missing"]);
    exit;
}
$token = str_replace('Bearer ', '', $headers['Authorization']);
try {
    $decoded = JWT::decode($token, new Key($jwt_secret, 'HS256'));
    $customer_id = $decoded->sub;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success"=>false,"message"=>"Invalid token"]);
    exit;
}

// Input
$input = json_decode(file_get_contents("php://input"), true);
$order_id = (int)($input['order_id'] ?? 0);
$payment_method_id = (int)($input['payment_method_id'] ?? 0);

if (!$order_id || !$payment_method_id) {
    echo json_encode(["success"=>false,"message"=>"order_id and payment_method_id required"]);
    exit;
}

try {
    // Validate order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND customer_id = :cid");
    $stmt->execute([':id'=>$order_id, ':cid'=>$customer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        echo json_encode(["success"=>false,"message"=>"Order not found"]);
        exit;
    }

    // Validate payment method
    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE id = :id AND status='active'");
    $stmt->execute([':id'=>$payment_method_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$payment) {
        echo json_encode(["success"=>false,"message"=>"Invalid payment method"]);
        exit;
    }

    // Insert into payments table
    $stmt = $pdo->prepare("INSERT INTO payments (order_id, payment_method_id, amount, currency, payment_status) 
                           VALUES (:oid, :pmid, :amt, :curr, :status)");
    $stmt->execute([
        ':oid'=>$order_id,
        ':pmid'=>$payment_method_id,
        ':amt'=>$order['total_price'],
        ':curr'=>'INR',
        ':status'=>'pending'
    ]);

    $payment_id = $pdo->lastInsertId();

    $payment_response = null;

    if ($payment['type'] === 'online') {
        // Razorpay order creation
        $api = new Api($razorpay_key_id, $razorpay_key_secret);
        $razorpay_order = $api->order->create([
            'receipt' => 'order_'.$order_id,
            'amount' => $order['total_price']*100, // in paise
            'currency' => 'INR',
            'payment_capture' => 1
        ]);

        // Update payment with Razorpay order id
        $stmt = $pdo->prepare("UPDATE payments SET payment_gateway_order_id = :rid WHERE id = :pid");
        $stmt->execute([':rid'=>$razorpay_order['id'], ':pid'=>$payment_id]);

        $payment_response = [
            'razorpay_order_id' => $razorpay_order['id'],
            'amount' => $razorpay_order['amount'],
            'currency' => $razorpay_order['currency']
        ];
    } else {
        // Offline payment, mark completed immediately
        $stmt = $pdo->prepare("UPDATE payments SET payment_status='completed' WHERE id=:pid");
        $stmt->execute([':pid'=>$payment_id]);
    }

    echo json_encode([
        "success"=>true,
        "message"=>"Payment processed",
        "payment_id"=>$payment_id,
        "payment_gateway"=>$payment_response
    ]);

} catch (Exception $e) {
    echo json_encode(["success"=>false,"message"=>"Error: ".$e->getMessage()]);
}
