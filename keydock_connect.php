<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Kolkata');

// JSON body read
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

$key_name = $json_data['key_name'] ?? $_GET['key_name'] ?? $_POST['key_name'] ?? '';
$device_id = $json_data['device_id'] ?? $_GET['device_id'] ?? $_POST['device_id'] ?? 'android-test';
$nonce = $json_data['nonce'] ?? $_GET['nonce'] ?? $_POST['nonce'] ?? 'jitu-app';

$store_file = __DIR__ . '/keys.json';

// HMAC Secret (from APK)
$hmac_secret = "Jx9#kR2\$mP5@nL8!vQ3&yB6*zA4%wC1^eT7";

// Load keys
$keys = [];
if (file_exists($store_file)) {
    $keys = json_decode(file_get_contents($store_file), true) ?? [];
}

if (isset($keys[$key_name])) {
    $key_data = $keys[$key_name];
    
    // Check expiry
    if ($key_data['expiry_timestamp'] < time() * 1000) {
        echo json_encode(["ok" => false, "error" => "Key expired"], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Device array logic
    if (!isset($key_data['devices']) || !is_array($key_data['devices'])) {
        $key_data['devices'] = [];
    }
    
    if (!in_array($device_id, $key_data['devices'])) {
        if ($key_data['max_devices'] > 0 && count($key_data['devices']) >= $key_data['max_devices']) {
            echo json_encode(["ok" => false, "error" => "Device limit reached"], JSON_PRETTY_PRINT);
            exit;
        }
        $key_data['devices'][] = $device_id;
    }
    
    $key_data['device_id'] = $device_id;
    $key_data['devices_used'] = count($key_data['devices']);
    
    $keys[$key_name] = $key_data;
    file_put_contents($store_file, json_encode($keys, JSON_PRETTY_PRINT));
    
    // Calculate remaining
    $now = time();
    $expiry = $key_data['expiry_timestamp'] / 1000;
    $remaining = max(0, $expiry - $now);
    $remaining_hours = floor($remaining / 3600);
    $remaining_minutes = floor(($remaining % 3600) / 60);
    
    $expires_at = date('Y-m-d H:i:s', $expiry);
    $remaining_seconds = $remaining;
    
    // --- BUILD HMAC MESSAGE (EXACT APK FORMAT) ---
    $message = "ok=true&nonce=" . $nonce;
    $message .= "&expires_at=" . $expires_at;
    $message .= "&remaining_seconds=" . $remaining_seconds;
    
    // Generate HMAC
    $hmac = hash_hmac('sha256', $message, $hmac_secret);
    
    // Build response
    $response = [
        "ok" => true,
        "status" => true,
        "Status" => "active",
        "DeviceLimit" => $key_data['max_devices'],
        "Devices" => implode(",", $key_data['devices']),
        "device_id" => $device_id,
        "devices_used" => $key_data['devices_used'],
        "max_devices" => $key_data['max_devices'],
        "Expiry" => $key_data['expiry_timestamp'],
        "Vaildity" => $key_data['expiry_timestamp'],
        "Validity" => $key_data['expiry_timestamp'],
        "state" => "active",
        "cheat" => null,
        "seller" => "",
        "validity" => $key_data['validity'],
        "expires_at" => $expires_at,
        "remaining_seconds" => $remaining_seconds,
        "remaining" => $remaining_hours . "h " . $remaining_minutes . "m",
        "nonce" => $nonce,
        "hmac" => $hmac
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} else {
    echo json_encode([
        "ok" => false,
        "error" => "Invalid key",
        "debug" => [
            "looking_for" => $key_name,
            "keys_found" => array_keys($keys)
        ]
    ], JSON_PRETTY_PRINT);
}
?>
