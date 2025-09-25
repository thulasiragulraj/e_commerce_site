<?php
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

function sendSuccess($data = [], $message = "Success") {
    echo json_encode([
        "success" => true,
        "message" => $message,
        "data"    => $data
    ]);
    exit;
}
