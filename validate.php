<?php
header('Content-Type: application/json');

$dbFile = 'keys_db.json';

// Create empty database if not exists
if (!file_exists($dbFile)) {
    file_put_contents($dbFile, json_encode(['keys' => [], 'devices' => []], JSON_PRETTY_PRINT));
}

$db = json_decode(file_get_contents($dbFile), true);

$key = $_POST['key'] ?? '';
$deviceId = $_POST['device_id'] ?? '';

if (empty($key) || empty($deviceId)) {
    echo json_encode(['success' => false, 'message' => 'Missing credentials']);
    exit;
}

// Find key
$keyData = null;
foreach ($db['keys'] as $k) {
    if ($k['key'] === $key) {
        $keyData = $k;
        break;
    }
}

if (!$keyData) {
    echo json_encode(['success' => false, 'message' => 'Invalid key']);
    exit;
}

// Check key status
if ($keyData['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Key is '.$keyData['status']]);
    exit;
}

// Check expiration
if (isset($keyData['expiry']) && strtotime($keyData['expiry']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Key has expired']);
    exit;
}

// Check devices
$devices = array_filter($db['devices'], function($d) use ($key) {
    return $d['key'] === $key;
});

// Check if device already registered
foreach ($devices as $device) {
    if ($device['device_id'] === $deviceId) {
        echo json_encode(['success' => true]);
        exit;
    }
}

// Check device limit
if (count($devices) >= $keyData['device_limit']) {
    echo json_encode(['success' => false, 'message' => 'Device limit reached (Max '.$keyData['device_limit'].')']);
    exit;
}

// Register new device
$db['devices'][] = [
    'key' => $key,
    'device_id' => $deviceId,
    'registered_at' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
];

file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT));

echo json_encode(['success' => true]);
?>