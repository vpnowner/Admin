<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

$dbFile = 'keys_db.json';

// Initialize empty database if not exists
if (!file_exists($dbFile)) {
    file_put_contents($dbFile, json_encode(['keys' => [], 'devices' => []], JSON_PRETTY_PRINT));
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;
$action = $input['action'] ?? $_GET['action'] ?? '';

$db = json_decode(file_get_contents($dbFile), true);

switch ($action) {
    case 'get_data':
        echo json_encode($db);
        break;
        
    case 'create_key':
        $key = $input['key'] ?? '';
        $device_limit = intval($input['device_limit'] ?? 1);
        $expiry = $input['expiry'] ?? null;
        $status = $input['status'] ?? 'active';
        $notes = $input['notes'] ?? '';
        
        // Validate input
        if (empty($key)) {
            echo json_encode(['success' => false, 'message' => 'Key is required']);
            exit;
        }
        
        // Check if key already exists
        foreach ($db['keys'] as $k) {
            if ($k['key'] === $key) {
                echo json_encode(['success' => false, 'message' => 'Key already exists']);
                exit;
            }
        }
        
        // Add new key
        $db['keys'][] = [
            'key' => $key,
            'device_limit' => $device_limit,
            'expiry' => $expiry ? date('Y-m-d H:i:s', strtotime($expiry)) : null,
            'status' => $status,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        break;
        
    case 'update_key':
        $key = $input['key'] ?? '';
        $device_limit = intval($input['device_limit'] ?? 1);
        $expiry = $input['expiry'] ?? null;
        $status = $input['status'] ?? 'active';
        $notes = $input['notes'] ?? '';
        
        // Find and update key
        $found = false;
        foreach ($db['keys'] as &$k) {
            if ($k['key'] === $key) {
                $k['device_limit'] = $device_limit;
                $k['expiry'] = $expiry ? date('Y-m-d H:i:s', strtotime($expiry)) : null;
                $k['status'] = $status;
                $k['notes'] = $notes;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'Key not found']);
            exit;
        }
        
        file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        break;
        
    case 'delete_key':
        $key = $input['key'] ?? '';
        
        // Remove key
        $db['keys'] = array_values(array_filter($db['keys'], function($k) use ($key) {
            return $k['key'] !== $key;
        }));
        
        // Remove associated devices
        $db['devices'] = array_values(array_filter($db['devices'], function($d) use ($key) {
            return $d['key'] !== $key;
        }));
        
        file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        break;
        
    case 'delete_all_keys':
        $db = ['keys' => [], 'devices' => []];
        file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>