<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

$store_file = __DIR__ . '/keys.json';

$custom_key = $_GET['key'] ?? $_POST['key'] ?? '';
$days = intval($_GET['days'] ?? $_POST['days'] ?? 0);
$hours = intval($_GET['hours'] ?? $_POST['hours'] ?? 0);
$devices = intval($_GET['devices'] ?? $_POST['devices'] ?? 0);

$keys = [];
if (file_exists($store_file)) {
    $keys = json_decode(file_get_contents($store_file), true) ?? [];
}

if (empty($custom_key)) {
    echo json_encode([
        "ok" => false,
        "error" => "Key required (e.g., ?key=MY-KEY&days=5&devices=3)"
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($days <= 0 && $hours <= 0) {
    echo json_encode([
        "ok" => false,
        "error" => "Days or Hours required"
    ], JSON_PRETTY_PRINT);
    exit;
}

if (isset($keys[$custom_key])) {
    echo json_encode([
        "ok" => false,
        "error" => "Key already exists"
    ], JSON_PRETTY_PRINT);
    exit;
}

// Calculate expiry (days + hours)
$total_seconds = ($days * 24 * 3600) + ($hours * 3600);
$expiry = time() + $total_seconds;

// Build validity text
$validity_parts = [];
if ($days > 0) $validity_parts[] = $days . " Days";
if ($hours > 0) $validity_parts[] = $hours . " Hours";
$validity_text = implode(" ", $validity_parts);

// 0 = unlimited devices
$keys[$custom_key] = [
    "key" => $custom_key,
    "device_id" => null,
    "devices" => [],
    "devices_used" => 0,
    "max_devices" => $devices,
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
    "devices_used" => 0,
    "days" => $days,
    "hours" => $hours
], JSON_PRETTY_PRINT);
?>