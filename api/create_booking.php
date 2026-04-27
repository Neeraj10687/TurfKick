<?php
// api/create_booking.php
require_once '../config/db.php';
require_once '../includes/helpers.php';

if (!is_logged_in()) {
    send_json_response('error', 'Login required.');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('error', 'Invalid method.');
}

$turf_id = sanitize_input($_POST['turf_id'] ?? 0);
$slot_id = sanitize_input($_POST['slot_id'] ?? 0);
$date = sanitize_input($_POST['date'] ?? '');
$total_price = sanitize_input($_POST['price'] ?? 0);
$csrf_token = $_POST['csrf_token'] ?? '';
$equipment_ids = $_POST['equipment_ids'] ?? '[]'; // JSON string of IDs

if (!validate_csrf_token($csrf_token)) {
    send_json_response('error', 'CSRF validation failed.');
}

try {
    // 1. Double check availability
    $check = $pdo->prepare("SELECT id FROM bookings WHERE turf_id = ? AND slot_id = ? AND booking_date = ? AND status != 'cancelled'");
    $check->execute([$turf_id, $slot_id, $date]);
    if ($check->fetch()) {
        send_json_response('error', 'This slot was just booked by someone else.');
    }

    // 2. Create booking
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, turf_id, slot_id, equipment_ids, booking_date, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'upcoming')");
    $stmt->execute([$user_id, $turf_id, $slot_id, $equipment_ids, $date, $total_price]);
    
    $booking_id = $pdo->lastInsertId();

    // 3. Create placeholder payment record
    $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) VALUES (?, ?, 'Online', 'completed')");
    $stmt->execute([$booking_id, $total_price]);

    send_json_response('success', 'Booking confirmed! See you at the turf.');
} catch (Exception $e) {
    send_json_response('error', 'Booking failed: ' . $e->getMessage());
}
?>