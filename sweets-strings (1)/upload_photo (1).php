<?php
session_name("CUSTOMER_SESSION");
session_start();
include 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

if (empty($_FILES['photo'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file     = $_FILES['photo'];
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large']);
    exit;
}

$filename = 'uploads/photos/' . $user_id . '_' . time() . '.' . $ext;

if (!is_dir('uploads/photos')) {
    mkdir('uploads/photos', 0755, true);
}

if (move_uploaded_file($file['tmp_name'], $filename)) {
    mysqli_query($conn, "UPDATE users SET photo='$filename' WHERE id=$user_id");

    // Fetch user name for log
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname FROM users WHERE id=$user_id"));
    $actor = mysqli_real_escape_string($conn, $u['fullname']);
    mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('$actor', 'Profile Update', 'Updated profile photo')");

    echo json_encode(['success' => true, 'photo' => $filename]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save photo']);
}
?>