<?php
// api/admin/reject_turf.php
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('error', 'Invalid method.');
}

$turf_id = sanitize_input($_POST['turf_id'] ?? 0);
$csrf_token = $_POST['csrf_token'] ?? '';

if (!validate_csrf_token($csrf_token)) {
    send_json_response('error', 'CSRF validation failed.');
}

try {
    $stmt = $pdo->prepare("UPDATE turfs SET status = 'inactive' WHERE id = ?");
    $stmt->execute([$turf_id]);
    send_json_response('success', "Turf rejected successfully.");
} catch (Exception $e) {
    send_json_response('error', 'Error: ' . $e->getMessage());
}
?>
