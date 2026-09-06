    <?php
    session_name("CUSTOMER_SESSION");
    session_start();
    include 'database.php';

    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode([]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $role    = $_SESSION['role'];

    // Admin sees all orders, customer sees only their own
    if ($role === 'admin') {
        $sql = "SELECT o.*, u.fullname as customer_name, u.email as customer_email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.order_date DESC";
        $result = mysqli_query($conn, $sql);
    } else {
        $sql = "SELECT o.*, u.fullname as customer_name, u.email as customer_email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.user_id = '$user_id' 
                ORDER BY o.order_date DESC";
        $result = mysqli_query($conn, $sql);
    }

    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Get items for each order
        $order_id   = $row['id'];
        $items_sql  = "SELECT * FROM order_items WHERE order_id = '$order_id'";
        $items_result = mysqli_query($conn, $items_sql);
        $items = [];
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        $row['items'] = $items;
        $orders[] = $row;
    }

    echo json_encode($orders);
    ?>