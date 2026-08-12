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

// Validate inputs
if ($turf_id <= 0 || $slot_id <= 0) {
    send_json_response('error', 'Invalid turf or slot selection.');
}

if (empty($date)) {
    send_json_response('error', 'Booking date is required.');
}

// Validate date format
$date_obj = DateTime::createFromFormat('Y-m-d', $date);
if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
    send_json_response('error', 'Invalid date format.');
}

// Prevent booking in the past
$today = new DateTime();
$today->setTime(0, 0, 0);
$booking_date = new DateTime($date);
if ($booking_date < $today) {
    send_json_response('error', 'Cannot book for a past date.');
}

// Validate price
if ($total_price <= 0) {
    send_json_response('error', 'Invalid booking price.');
}

try {
    // 1. Verify turf exists and is active
    $check_turf = $pdo->prepare("SELECT id, price_per_hour FROM turfs WHERE id = ? AND status = 'active'");
    $check_turf->execute([$turf_id]);
    $turf = $check_turf->fetch();
    
    if (!$turf) {
        send_json_response('error', 'Turf not found or inactive.');
    }
    
    // 2. Verify slot exists
    $check_slot = $pdo->prepare("SELECT id FROM time_slots WHERE id = ? AND turf_id = ?");
    $check_slot->execute([$slot_id, $turf_id]);
    if (!$check_slot->fetch()) {
        send_json_response('error', 'Invalid time slot.');
    }

    // 3. Double check availability (prevent race conditions)
    $check = $pdo->prepare("SELECT id FROM bookings WHERE turf_id = ? AND slot_id = ? AND booking_date = ? AND status != 'cancelled'");
    $check->execute([$turf_id, $slot_id, $date]);
    if ($check->fetch()) {
        send_json_response('error', 'This slot was just booked by someone else. Please select another slot.');
    }

    // 4. Create booking with explicit column names
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, turf_id, slot_id, booking_date, total_price, status, equipment_ids) VALUES (?, ?, ?, ?, ?, 'upcoming', ?)");
    $stmt->execute([$user_id, $turf_id, $slot_id, $date, $total_price, $equipment_ids]);
    
    $booking_id = $pdo->lastInsertId();

    // 5. Create placeholder payment record
    $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status) VALUES (?, ?, 'Online', 'completed')");
    $stmt->execute([$booking_id, $total_price]);

    send_json_response('success', 'Booking confirmed! See you at the turf.', ['booking_id' => $booking_id]);
} catch (Exception $e) {
    send_json_response('error', 'Booking failed: ' . $e->getMessage());
}
?>