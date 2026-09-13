<?php
header('Content-Type: application/json');

// Set timezone to IST (India)
date_default_timezone_set('Asia/Kolkata');

$store_file = 'keys.json';

function generateKey() {
    $prefix = "PIYUSH-HACKS-";
    $random = strtoupper(bin2hex(random_bytes(4))); // 8 chars
    return $prefix . $random;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'generate';

$keys = [];
if (file_exists($store_file)) {
    $keys = json_decode(file_get_contents($store_file), true) ?? [];
}

if ($action === 'generate') {
    $new_key = generateKey();
    $expiry = time() + (5 * 3600); // 5 hours from now in IST
    
    $keys[$new_key] = [
        "key" => $new_key,
        "device_id" => null,
        "devices_used" => 0,
        "max_devices" => 1,
        "created_at" => date('Y-m-d H:i:s'),
        "expires_at" => date('Y-m-d H:i:s', $expiry),
        "expiry_timestamp" => $expiry * 1000,
        "validity" => "5 Hours",
        "status" => "active"
    ];
    
    file_put_contents($store_file, json_encode($keys, JSON_PRETTY_PRINT));
    
    echo json_encode([
        "ok" => true,
        "key" => $new_key,
        "validity" => "5 Hours",
        "expires_at" => date('Y-m-d H:i:s', $expiry),
        "max_devices" => 1
    ], JSON_PRETTY_PRINT);
    
} elseif ($action === 'list') {
    $list = [];
    foreach ($keys as $k => $data) {
        $list[] = [
            "key" => $k,
            "status" => $data['status'] ?? 'active',
            "devices_used" => $data['devices_used'] ?? 0,
            "expires_at" => $data['expires_at'] ?? 'N/A'
        ];
    }
    echo json_encode(["ok" => true, "keys" => $list], JSON_PRETTY_PRINT);
    
} else {
    echo json_encode(["ok" => false, "error" => "Invalid action"]);
}
?>
