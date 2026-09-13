<?php
header('Content-Type: application/json');

// Set timezone to IST (India)
date_default_timezone_set('Asia/Kolkata');

$key_name = $_GET['key_name'] ?? $_POST['key_name'] ?? '';
$device_id = $_GET['device_id'] ?? $_POST['device_id'] ?? 'android-test';
$nonce = $_GET['nonce'] ?? $_POST['nonce'] ?? 'jitu-app';

$store_file = 'keys.json';

// Load keys
$keys = [];
if (file_exists($store_file)) {
    $keys = json_decode(file_get_contents($store_file), true) ?? [];
}

// Check if key exists
if (isset($keys[$key_name])) {
    $key_data = $keys[$key_name];
    
    // Check expiry
    if ($key_data['expiry_timestamp'] < time() * 1000) {
        echo json_encode(["ok" => false, "error" => "Key expired"], JSON_PRETTY_PRINT);
        exit;
    }
    
    // --- DEVICE ARRAY LOGIC (NO LIMIT BY DEFAULT) ---
    if (!isset($key_data['devices']) || !is_array($key_data['devices'])) {
        $key_data['devices'] = [];
    }
    
    // Add device if not already there
    if (!in_array($device_id, $key_data['devices'])) {
        // Check max_devices (0 or -1 = unlimited)
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
    
    // Calculate remaining time
    $now = time();
    $expiry = $key_data['expiry_timestamp'] / 1000;
    $remaining = max(0, $expiry - $now);
    $remaining_hours = floor($remaining / 3600);
    $remaining_minutes = floor(($remaining % 3600) / 60);
    
    // Build response (SAME FORMAT AS YOUR ORIGINAL)
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
    echo json_encode(["ok" => false, "error" => "Invalid key"], JSON_PRETTY_PRINT);
}
?>