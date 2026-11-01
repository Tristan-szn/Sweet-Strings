<?php
session_name("CUSTOMER_SESSION"); // must match order.php
session_start();
// TEMPORARY - add after session block, remove later
error_log("SESSION: " . print_r($_SESSION, true));
error_log("POST DATA: " . file_get_contents('php://input'));
include 'database.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['items'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid order data'
    ]);
    exit;
}

try {
    $user_id = intval($_SESSION['user_id']);

    $total = floatval($data['total'] ?? 0);
    if ($total <= 0) {
        throw new Exception('Invalid total');
    }

    $payment_method = in_array($data['paymentMethod'] ?? '', ['cash', 'gcash', 'card'])
        ? $data['paymentMethod']
        : 'cash';

    $order_id = 'ORD-' . time();
    $order_date = date('Y-m-d H:i:s');
    $delivery_date = date('Y-m-d H:i:s', strtotime('+3 days'));

    mysqli_autocommit($conn, false);

    // INSERT ORDER
    $orderStmt = mysqli_prepare($conn, "
        INSERT INTO orders (id, user_id, total, payment_method, status, order_date, delivery_date)
        VALUES (?, ?, ?, ?, 'pending', ?, ?)
    ");

    mysqli_stmt_bind_param(
        $orderStmt,
        "sidsss",
        $order_id,
        $user_id,
        $total,
        $payment_method,
        $order_date,
        $delivery_date
    );

    if (!mysqli_stmt_execute($orderStmt)) {
        throw new Exception('Order save failed: ' . mysqli_stmt_error($orderStmt));
    }

    // INSERT ITEMS
    $itemStmt = mysqli_prepare($conn, "
        INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // UPDATE STOCK
    $stockStmt = mysqli_prepare($conn, "
        UPDATE products
        SET stock = GREATEST(0, stock - ?),
            updated_at = NOW()
        WHERE id = ? AND stock >= ?
    ");

    foreach ($data['items'] as $item) {
        $product_id = intval($item['productId']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);
        $subtotal = floatval($item['subtotal']);
        $product_name = substr($item['name'] ?? 'Unknown', 0, 255);

        if ($quantity <= 0 || $price <= 0 || $product_id <= 0) {
            throw new Exception('Invalid item data');
        }

        // CHECK STOCK
        $stockCheck = mysqli_prepare($conn, "
            SELECT stock FROM products WHERE id = ?
        ");

        mysqli_stmt_bind_param($stockCheck, "i", $product_id);
        mysqli_stmt_execute($stockCheck);

        $result = mysqli_stmt_get_result($stockCheck);
        $row = mysqli_fetch_assoc($result);
        $current_stock = $row['stock'] ?? 0;

        mysqli_stmt_close($stockCheck);

        if ($current_stock < $quantity) {
            throw new Exception("Insufficient stock for {$product_name}. Available: {$current_stock}");
        }

        // SAVE ORDER ITEM
        mysqli_stmt_bind_param(
            $itemStmt,
            "sisdid",
            $order_id,
            $product_id,
            $product_name,
            $price,
            $quantity,
            $subtotal
        );

        if (!mysqli_stmt_execute($itemStmt)) {
            throw new Exception('Item save failed: ' . mysqli_stmt_error($itemStmt));
        }

        // REDUCE STOCK
        mysqli_stmt_bind_param(
            $stockStmt,
            "iii",
            $quantity,
            $product_id,
            $quantity
        );

        if (!mysqli_stmt_execute($stockStmt) || mysqli_stmt_affected_rows($stockStmt) === 0) {
            throw new Exception("Stock update failed for product ID: {$product_id}");
        }
    }

    mysqli_commit($conn);
    mysqli_autocommit($conn, true);

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'delivery_date' => $delivery_date,
        'message' => 'Order placed successfully'
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($orderStmt)) mysqli_stmt_close($orderStmt);
    if (isset($itemStmt)) mysqli_stmt_close($itemStmt);
    if (isset($stockStmt)) mysqli_stmt_close($stockStmt);
}
?>