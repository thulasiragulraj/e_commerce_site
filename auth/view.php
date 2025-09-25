<?php
// ---- DB Connection ----
$pdo = require_once __DIR__ . '/../config/db.php';

// ---- Get slug from URL ----
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    die("Invalid QR");
}

// ---- Fetch user details ----
$stmt = $pdo->prepare("SELECT name, phone, email FROM users1 WHERE qr_code LIKE ?");
$stmt->execute(["%$slug%"]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

// ---- Display user details ----
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Details</title>
</head>
<body>
    <h2>User Details</h2>
    <ul>
        <li>Name: <?= htmlspecialchars($user['name']) ?></li>
        <li>Phone: <?= htmlspecialchars($user['phone']) ?></li>
        <li>Email: <?= htmlspecialchars($user['email']) ?></li>
    </ul>
</body>
</html>
