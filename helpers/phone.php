<?php
function normalizeIndianPhone($phone) {
    // Remove spaces, +91, dashes
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 12 && substr($phone, 0, 2) == "91") {
        $phone = substr($phone, 2);
    }
    return $phone;
}
