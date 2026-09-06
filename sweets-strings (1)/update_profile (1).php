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
$data    = json_decode(file_get_contents('php://input'), true);

$name    = mysqli_real_escape_string($conn, trim($data['name']    ?? ''));
$contact = mysqli_real_escape_string($conn, trim($data['contact'] ?? ''));
$address = mysqli_real_escape_string($conn, trim($data['address'] ?? ''));

if (!$name || !$contact || !$address) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}

// Fetch OLD values before update
$old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, contact, address FROM users WHERE id=$user_id"));

// Do the update
if (mysqli_query($conn, "UPDATE users SET fullname='$name', contact='$contact', address='$address' WHERE id=$user_id")) {

    // Find what changed
    $changed = [];
    if ($old['fullname'] !== $name)    $changed[] = "name ('{$old['fullname']}' → '$name')";
    if ($old['contact']  !== $contact) $changed[] = "contact ('{$old['contact']}' → '$contact')";
    if ($old['address']  !== $address) $changed[] = "address updated";

    if (!empty($changed)) {
        $actor   = mysqli_real_escape_string($conn, $name);
        $details = mysqli_real_escape_string($conn, "Updated: " . implode(', ', $changed));
        mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('$actor', 'Profile Update', '$details')");
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
?>