<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// JWT secret key
$jwt_secret = "YOUR_SECRET_KEY";

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

// Input JSON
$input = json_decode(file_get_contents("php://input"), true);

// Action type: fetch / create / update
$action = $input['action'] ?? 'fetch';

try {

    if ($action === 'fetch') {
        // Fetch subscriptions for this customer
        $stmt = $pdo->prepare("
            SELECT s.*, p.name AS plan_name, pm.name AS payment_method_name
            FROM subscriptions s
            LEFT JOIN plans p ON s.plan_id = p.id
            LEFT JOIN payment_methods pm ON s.payment_method_id = pm.id
            WHERE s.customers_id = :cid
            ORDER BY s.start_date DESC
        ");
        $stmt->execute([':cid'=>$customer_id]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "success"=>true,
            "subscriptions"=>$subscriptions
        ]);
        exit;
    }

    if ($action === 'create') {
        // Required fields
        $plan_id = (int)($input['plan_id'] ?? 0);
        $payment_method_id = (int)($input['payment_method_id'] ?? 0);
        $amount = (float)($input['amount'] ?? 0);
        $start_date = $input['start_date'] ?? date('Y-m-d H:i:s');
        $end_date = $input['end_date'] ?? date('Y-m-d H:i:s', strtotime('+1 month'));
        $auto_renew = (int)($input['auto_renew'] ?? 0);
        $transaction_id = $input['transaction_id'] ?? null;

        if (!$plan_id || !$payment_method_id || !$amount) {
            echo json_encode(["success"=>false,"message"=>"plan_id, payment_method_id, amount required"]);
            exit;
        }

        // Insert subscription
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions 
            (customers_id, plan_id, start_date, end_date, status, transaction_id, amount, auto_renew, payment_method_id, created_at, updated_at)
            VALUES
            (:cid, :pid, :sdate, :edate, 'active', :txid, :amt, :ar, :pmid, NOW(), NOW())
        ");

        $stmt->execute([
            ':cid'=>$customer_id,
            ':pid'=>$plan_id,
            ':sdate'=>$start_date,
            ':edate'=>$end_date,
            ':txid'=>$transaction_id,
            ':amt'=>$amount,
            ':ar'=>$auto_renew,
            ':pmid'=>$payment_method_id
        ]);

        $subscription_id = $pdo->lastInsertId();

        echo json_encode([
            "success"=>true,
            "message"=>"Subscription created",
            "subscription_id"=>$subscription_id
        ]);
        exit;
    }

    if ($action === 'update') {
        $subscription_id = (int)($input['subscription_id'] ?? 0);
        $status = $input['status'] ?? null;

        if (!$subscription_id || !$status) {
            echo json_encode(["success"=>false,"message"=>"subscription_id and status required"]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE subscriptions SET status=:status, updated_at=NOW()
            WHERE id=:sid AND customers_id=:cid
        ");
        $stmt->execute([
            ':status'=>$status,
            ':sid'=>$subscription_id,
            ':cid'=>$customer_id
        ]);

        echo json_encode([
            "success"=>true,
            "message"=>"Subscription updated"
        ]);
        exit;
    }

    echo json_encode(["success"=>false,"message"=>"Invalid action"]);

} catch (Exception $e) {
    echo json_encode(["success"=>false,"message"=>"Error: ".$e->getMessage()]);
}
