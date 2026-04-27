<?php
// api/admin/get_turfs.php
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

require_admin();

try {
    $stmt = $pdo->query("
        SELECT t.*, u.name as owner_name, 
        (SELECT COUNT(*) FROM maintenance_requests WHERE turf_id = t.id AND status = 'pending') as pending_requests
        FROM turfs t 
        JOIN users u ON t.owner_id = u.id 
        ORDER BY pending_requests DESC, t.status DESC
    ");
    $turfs = $stmt->fetchAll();
    send_json_response('success', 'Turfs fetched.', $turfs);
} catch (Exception $e) {
    send_json_response('error', 'Error: ' . $e->getMessage());
}
?>