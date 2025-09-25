<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$JWT_SECRET = getenv('JWT_SECRET') ?: 'replace_this_with_a_very_secret_random_string';
$JWT_ALGO   = 'HS256';

// ✅ Token validity = 1 hour (3600 seconds)
$JWT_EXP_SECONDS = 60 * 60; // 1 hour

function create_jwt(array $payloadCustom = [], $expSeconds = null) {
    global $JWT_SECRET, $JWT_ALGO, $JWT_EXP_SECONDS;
    $now = time();
    $exp = $now + ($expSeconds ?? $JWT_EXP_SECONDS);

    $payload = array_merge([
        'iat' => $now,
        'nbf' => $now,
        'exp' => $exp
    ], $payloadCustom);

    return JWT::encode($payload, $JWT_SECRET, $JWT_ALGO);
}

function decode_jwt($token) {
    global $JWT_SECRET, $JWT_ALGO;
    try {
        $decoded = JWT::decode($token, new Key($JWT_SECRET, $JWT_ALGO));
        return json_decode(json_encode($decoded), true);
    } catch (\Firebase\JWT\ExpiredException $e) {
        throw new Exception('Token expired', 401);
    } catch (\Exception $e) {
        throw new Exception('Invalid token: ' . $e->getMessage(), 401);
    }
}
