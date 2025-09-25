<?php
$host = "localhost";
$dbname = "e_commerce";   
$username = "root";       
$password = "";           

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo; // ✅ PDO object return
} catch (PDOException $e) {
    die(json_encode([
        "success" => false,
        "message" => "DB Connection failed: " . $e->getMessage()
    ]));
}
