<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Kolkata');

$key_name = $_GET['key_name'] ?? $_POST['key_name'] ?? '';
$device_id = $_GET['device_id'] ?? $_POST['device_id'] ?? 'android-test';
$nonce = $_GET['nonce'] ?? $_POST['nonce'] ?? 'jitu-app';

// Use absolute path
$store_file = __DIR__ . '/keys.json';

// Load keys
$keys = [];
if (file_exists($store_file)) {
    $keys = json_decode(file_get_contents($store_file), true) ?? [];
}

// DEBUG: Log what's happening
error_log("Looking for key: " . $key_name);
error_log("Store file: " . $store_file);
error_log("Keys count: " . count($keys));

// Check if key exists
if (isset($keys[$key_name])) {
    $key_data = $keys[$key_name];
    
    // Check expiry
    if ($key_data['expiry_timestamp'] < time() * 1000) {
        echo json_encode(["ok" => false, "error" => "Key expired"], JSON_PRETTY_PRINT);
        exit;
    }
    
    // --- DEVICE ARRAY LOGIC ---
    if (!isset($key_data['devices']) || !is_array($key_data['devices'])) {
        $key_data['devices'] = [];
    }
    
    // Add device if not already there
    if (!in_array($device_id, $key_data['devices'])) {
        // max_devices = 0 means unlimited
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
    
    // Response
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
        "expires_at" => date('Y-m-d H:i:s', $expiry),
        "remaining_seconds" => $remaining,
        "remaining" => $remaining_hours . "h " . $remaining_minutes . "m",
        "hmac" => hash('sha256', $key_name . $nonce . 'jitu-secret'),
        "nonce" => $nonce
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} else {
    echo json_encode([
        "ok" => false,
        "error" => "Invalid key",
        "debug" => [
            "looking_for" => $key_name,
            "keys_found" => array_keys($keys),
            "store_file" => $store_file
        ]
    ], JSON_PRETTY_PRINT);
}
?>
