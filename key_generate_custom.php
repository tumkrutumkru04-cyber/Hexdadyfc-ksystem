<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

$store_file = 'keys.json';

$custom_key = $_GET['key'] ?? $_POST['key'] ?? '';
$days = intval($_GET['days'] ?? $_POST['days'] ?? 0);
$hours = intval($_GET['hours'] ?? $_POST['hours'] ?? 0);
$devices = intval($_GET['devices'] ?? $_POST['devices'] ?? 0);  // 0 = unlimited

$keys = [];
if (file_exists($store_file)) {
    $keys = json_decode(file_get_contents($store_file), true) ?? [];
}

if (empty($custom_key)) {
    echo json_encode(["ok" => false, "error" => "Key required"], JSON_PRETTY_PRINT);
    exit;
}

if ($days <= 0 && $hours <= 0) {
    echo json_encode(["ok" => false, "error" => "Days or Hours required"], JSON_PRETTY_PRINT);
    exit;
}

if (isset($keys[$custom_key])) {
    echo json_encode(["ok" => false, "error" => "Key already exists"], JSON_PRETTY_PRINT);
    exit;
}

$total_seconds = ($days * 24 * 3600) + ($hours * 3600);
$expiry = time() + $total_seconds;

$validity_text = ($days > 0 ? "$days Days " : "") . ($hours > 0 ? "$hours Hours" : "");
$validity_text = trim($validity_text);

$keys[$custom_key] = [
    "key" => $custom_key,
    "device_id" => null,
    "devices" => [],
    "devices_used" => 0,
    "max_devices" => $devices,  // 0 = unlimited
    "created_at" => date('Y-m-d H:i:s'),
    "expires_at" => date('Y-m-d H:i:s', $expiry),
    "expiry_timestamp" => $expiry * 1000,
    "validity" => $validity_text,
    "status" => "active"
];

file_put_contents($store_file, json_encode($keys, JSON_PRETTY_PRINT));

echo json_encode([
    "ok" => true,
    "key" => $custom_key,
    "validity" => $validity_text,
    "expires_at" => date('Y-m-d H:i:s', $expiry),
    "max_devices" => $devices,
    "devices_used" => 0
], JSON_PRETTY_PRINT);
?>