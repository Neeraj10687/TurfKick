<?php
// api/manage_equipment.php
require_once '../config/db.php';
require_once '../includes/helpers.php';

if (!is_logged_in() || !is_owner()) {
    send_json_response('error', 'Unauthorized access.');
}

$owner_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT e.*, t.name as turf_name FROM equipment e LEFT JOIN turfs t ON e.turf_id = t.id WHERE e.owner_id = ?");
        $stmt->execute([$owner_id]);
        $items = $stmt->fetchAll();
        send_json_response('success', 'Equipment fetched successfully.', $items);
    } catch (Exception $e) {
        send_json_response('error', 'Error fetching equipment: ' . $e->getMessage());
    }
} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($csrf_token)) {
        send_json_response('error', 'CSRF token validation failed.');
    }

    if ($action === 'add') {
        $name = sanitize_input($_POST['name'] ?? '');
        $price = sanitize_float($_POST['price'] ?? 0, 0);
        $turf_id = sanitize_int($_POST['turf_id'] ?? 0, 1);

        if (empty($name)) {
            send_json_response('error', 'Equipment name is required.');
        }

        if ($price <= 0) {
            send_json_response('error', 'Price must be greater than 0.');
        }

        try {
            // Verify turf ownership
            $check = $pdo->prepare("SELECT id FROM turfs WHERE id = ? AND owner_id = ?");
            $check->execute([$turf_id, $owner_id]);
            if (!$check->fetch()) {
                send_json_response('error', 'Access denied. You do not own this turf.');
            }

            $stmt = $pdo->prepare("INSERT INTO equipment (owner_id, turf_id, name, price_per_session) VALUES (?, ?, ?, ?)");
            $stmt->execute([$owner_id, $turf_id, $name, $price]);
            send_json_response('success', 'Equipment added successfully.');
        } catch (Exception $e) {
            send_json_response('error', 'Add failed: ' . $e->getMessage());
        }
    } elseif ($action === 'delete') {
        $item_id = sanitize_int($_POST['item_id'] ?? 0, 1);
        
        if ($item_id <= 0) {
            send_json_response('error', 'Invalid item ID.');
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ? AND owner_id = ?");
            $stmt->execute([$item_id, $owner_id]);
            send_json_response('success', 'Equipment deleted successfully.');
        } catch (Exception $e) {
            send_json_response('error', 'Delete failed: ' . $e->getMessage());
        }
    } else {
        send_json_response('error', 'Invalid action.');
    }
} else {
    send_json_response('error', 'Invalid request method.');
}
?>
