<?php
session_start();
include 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$order_id = mysqli_real_escape_string($conn, $data['order_id']);
$user_id  = $_SESSION['user_id'];

// Check order belongs to user and is still processing
$check = "SELECT * FROM orders WHERE id='$order_id' AND user_id='$user_id' AND status='processing'";
$result = mysqli_query($conn, $check);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or cannot be cancelled']);
    exit;
}

// Start transaction so both cancel + stock restore happen together
mysqli_autocommit($conn, false);

try {
    // Step 1 — Cancel the order
    $cancelSql = "UPDATE orders SET status='cancelled' WHERE id='$order_id'";
    if (!mysqli_query($conn, $cancelSql)) {
        throw new Exception('Failed to cancel order: ' . mysqli_error($conn));
    }

    // Step 2 — Get all items from this order
    $itemsSql = "SELECT product_id, quantity FROM order_items WHERE order_id='$order_id'";
    $itemsResult = mysqli_query($conn, $itemsSql);

    if (!$itemsResult) {
        throw new Exception('Failed to fetch order items: ' . mysqli_error($conn));
    }

    // Step 3 — Restore stock for each item
    while ($item = mysqli_fetch_assoc($itemsResult)) {
        $product_id = intval($item['product_id']);
        $quantity   = intval($item['quantity']);

        $restoreSql = "UPDATE products 
                       SET stock = stock + $quantity,
                           updated_at = NOW()
                       WHERE id = $product_id";

        if (!mysqli_query($conn, $restoreSql)) {
            throw new Exception('Failed to restore stock for product ' . $product_id . ': ' . mysqli_error($conn));
        }
    }

    // All good — commit
    mysqli_commit($conn);
    mysqli_autocommit($conn, true);

    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled and stock restored'
    ]);

} catch (Exception $e) {
    // Something failed — rollback everything
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>