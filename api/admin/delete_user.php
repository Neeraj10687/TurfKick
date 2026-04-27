<?php
// api/admin/delete_user.php
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('error', 'Invalid method.');
}

$user_id_to_delete = sanitize_input($_POST['user_id'] ?? 0);
$csrf_token = $_POST['csrf_token'] ?? '';

if (!validate_csrf_token($csrf_token)) {
    send_json_response('error', 'CSRF validation failed.');
}

if ($user_id_to_delete == $_SESSION['user_id']) {
    send_json_response('error', 'Cannot delete your own admin account.');
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id_to_delete]);
    send_json_response('success', "User deleted successfully.");
} catch (Exception $e) {
    send_json_response('error', 'Error: ' . $e->getMessage());
}
?>
