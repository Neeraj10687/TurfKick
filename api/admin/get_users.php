<?php
// api/admin/get_users.php
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

require_admin();

try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    send_json_response('success', 'Users fetched.', $users);
} catch (Exception $e) {
    send_json_response('error', 'Error: ' . $e->getMessage());
}
?>
