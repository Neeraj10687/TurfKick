<?php
// api/get_equipment.php
require_once '../config/db.php';
require_once '../includes/helpers.php';

$turf_id = sanitize_int($_GET['turf_id'] ?? 0, 1);

if ($turf_id <= 0) {
    send_json_response('error', 'Invalid turf ID.');
}

try {
    // First, verify turf exists and is active
    $stmt = $pdo->prepare("SELECT id, owner_id FROM turfs WHERE id = ? AND status = 'active'");
    $stmt->execute([$turf_id]);
    $turf = $stmt->fetch();

    if (!$turf) {
        send_json_response('error', 'Turf not found or inactive.');
    }

    // Fetch equipment for this turf
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE turf_id = ? AND status = 'active'");
    $stmt->execute([$turf_id]);
    $items = $stmt->fetchAll();

    send_json_response('success', 'Equipment fetched successfully.', $items);
} catch (Exception $e) {
    send_json_response('error', 'Error fetching equipment.');
}
?>
