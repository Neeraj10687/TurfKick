<?php
// api/admin/get_bookings.php
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

require_admin();

try {
    $stmt = $pdo->query("
        SELECT b.*, u.name as user_name, t.name as turf_name, ts.slot_label 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN turfs t ON b.turf_id = t.id 
        JOIN time_slots ts ON b.slot_id = ts.id 
        ORDER BY b.booking_date DESC
    ");
    $bookings = $stmt->fetchAll();
    send_json_response('success', 'Bookings fetched.', $bookings);
} catch (Exception $e) {
    send_json_response('error', 'Error: ' . $e->getMessage());
}
?>
