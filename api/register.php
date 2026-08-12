<?php
// api/register.php
require_once '../config/db.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('error', 'Invalid request method.');
}

$name = sanitize_input($_POST['name'] ?? '');
$email = sanitize_input($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = sanitize_input($_POST['role'] ?? 'user');
$turf_name = sanitize_input($_POST['turf_name'] ?? '');
$csrf_token = $_POST['csrf_token'] ?? '';

if (!validate_csrf_token($csrf_token)) {
    send_json_response('error', 'CSRF token validation failed.');
}

if (empty($name) || empty($email) || empty($password)) {
    send_json_response('error', 'Please fill all required fields.');
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_response('error', 'Invalid email format.');
}

// Validate password strength
if (strlen($password) < 8) {
    send_json_response('error', 'Password must be at least 8 characters long.');
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Allowed file types and max size
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
$max_file_size = 5 * 1024 * 1024; // 5MB

try {
    $pdo->beginTransaction();

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        send_json_response('error', 'Email already registered.');
    }

    // Handle file uploads for owners
    $aadhaar_path = '';
    $license_path = '';
    $primary_image_path = '';
    if ($role === 'owner') {
        // Validate and upload aadhaar
        if (isset($_FILES['aadhaar']) && $_FILES['aadhaar']['error'] === UPLOAD_ERR_OK) {
            $file_type = $_FILES['aadhaar']['type'];
            $file_size = $_FILES['aadhaar']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type for Aadhaar. Only JPG, PNG, and PDF allowed.');
            }
            
            if ($file_size > $max_file_size) {
                throw new Exception('Aadhaar file size exceeds 5MB limit.');
            }
            
            $extension = pathinfo($_FILES['aadhaar']['name'], PATHINFO_EXTENSION);
            $aadhaar_path = 'uploads/' . time() . '_aadhaar_' . uniqid() . '.' . $extension;
            if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
            move_uploaded_file($_FILES['aadhaar']['tmp_name'], '../' . $aadhaar_path);
        } else {
            throw new Exception('Aadhaar card is required for turf owners.');
        }
        
        // Validate and upload license
        if (isset($_FILES['license']) && $_FILES['license']['error'] === UPLOAD_ERR_OK) {
            $file_type = $_FILES['license']['type'];
            $file_size = $_FILES['license']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type for License. Only JPG, PNG, and PDF allowed.');
            }
            
            if ($file_size > $max_file_size) {
                throw new Exception('License file size exceeds 5MB limit.');
            }
            
            $extension = pathinfo($_FILES['license']['name'], PATHINFO_EXTENSION);
            $license_path = 'uploads/' . time() . '_license_' . uniqid() . '.' . $extension;
            if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
            move_uploaded_file($_FILES['license']['tmp_name'], '../' . $license_path);
        } else {
            throw new Exception('Business license is required for turf owners.');
        }
        
        // Validate and upload turf images
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $image_count = 0;
            foreach ($_FILES['images']['name'] as $key => $image_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['images']['type'][$key];
                    $file_size = $_FILES['images']['size'][$key];
                    
                    // Only allow images for turf photos
                    if (!in_array($file_type, ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
                        throw new Exception('Invalid image type. Only JPG, PNG, and WEBP allowed.');
                    }
                    
                    if ($file_size > $max_file_size) {
                        throw new Exception('Image file size exceeds 5MB limit.');
                    }
                    
                    $extension = pathinfo($image_name, PATHINFO_EXTENSION);
                    $img_path = 'uploads/' . time() . '_turf_' . uniqid() . '.' . $extension;
                    if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$key], '../' . $img_path)) {
                        if (empty($primary_image_path)) {
                            $primary_image_path = $img_path; // Store first image as primary
                        }
                        $image_count++;
                    }
                }
            }
            
            if ($image_count === 0) {
                throw new Exception('At least one turf image is required.');
            }
        } else {
            throw new Exception('At least one turf image is required.');
        }
    }

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, aadhaar_file, license_file) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hashed_password, $role, $aadhaar_path, $license_path]);
    $user_id = $pdo->lastInsertId();

    // If owner, create initial turf entry
    if ($role === 'owner' && !empty($turf_name)) {
        $stmt = $pdo->prepare("INSERT INTO turfs (owner_id, name, location, sport_category, price_per_hour, image_path) VALUES (?, ?, 'Not Specified', 'Multi-Sport', 0, ?)");
        $stmt->execute([$user_id, $turf_name, $primary_image_path]);
    }

    $pdo->commit();
    send_json_response('success', 'Registration successful! You can now login.');
} catch (Exception $e) {
    $pdo->rollBack();
    send_json_response('error', 'Registration failed: ' . $e->getMessage());
}
?>
