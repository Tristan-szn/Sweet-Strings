    <?php
    // ADMIN SESSION
    session_name("ADMIN_SESSION");
    session_start();

    include 'database.php';

    // AUTH CHECK - ADMIN ONLY
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {

        header('Location: login.php');
        exit;

    }

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT * FROM users WHERE id='$user_id'";
    $result = mysqli_query($conn, $sql);

    $admin = mysqli_fetch_assoc($result);

    // ─── HANDLE AJAX REQUESTS ─────────────────────────────────────────────────────
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'get_dashboard_stats') {
        header('Content-Type: application/json');
        $stats = [];

        // Total customers
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='customer'");
        $stats['customers'] = mysqli_fetch_assoc($r)['c'];

        // Total income (completed/delivered orders)
        $r = mysqli_query($conn, "SELECT SUM(total) as t FROM orders WHERE status IN ('order-received','completed','delivered')");
        $row = mysqli_fetch_assoc($r);
        $stats['income'] = $row['t'] ?? 0;

        // Total items sold
        $r = mysqli_query($conn, "SELECT SUM(oi.quantity) as q FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE o.status IN ('order-received','completed','delivered')");
        $row = mysqli_fetch_assoc($r);
        $stats['sold'] = $row['q'] ?? 0;

        // Order counts by status
        $r = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM orders GROUP BY status");
        $statusCounts = [];
        while ($row = mysqli_fetch_assoc($r)) {
            $statusCounts[$row['status']] = $row['c'];
        }
        $stats['status_counts'] = $statusCounts;

        // Monthly income for chart (last 6 months)
        $r = mysqli_query($conn, "SELECT DATE_FORMAT(order_date,'%Y-%m') as month, SUM(total) as revenue 
            FROM orders WHERE status IN ('order-received','completed','delivered') 
            AND order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
            GROUP BY month ORDER BY month ASC");
        $chart = [];
        while ($row = mysqli_fetch_assoc($r)) {
            $chart[] = $row;
        }
        $stats['chart'] = $chart;

    // Recent orders - one row per order with item count
    $r = mysqli_query($conn, "
        SELECT o.id, o.total, o.status, o.order_date, u.fullname,
            COUNT(oi.id) as item_count,
            GROUP_CONCAT(oi.product_name SEPARATOR ', ') as product_names
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        GROUP BY o.id, o.total, o.status, o.order_date, u.fullname
        ORDER BY o.order_date DESC 
        LIMIT 8
    ");
    $recent = [];
    while ($row = mysqli_fetch_assoc($r)) {
        $recent[] = $row;
    }
    $stats['recent_orders'] = $recent;

        // Most ordered items (by category or product)
    $r = mysqli_query($conn, "
        SELECT product_name, SUM(quantity) as total_qty 
        FROM order_items 
        GROUP BY product_name 
        ORDER BY total_qty DESC 
        LIMIT 5
    ");

    $topItems = [];

    while ($row = mysqli_fetch_assoc($r)) {
        $topItems[] = $row;
    }

    /*
    |--------------------------------------------------------------------------
    | Reset top items if there are no orders
    |--------------------------------------------------------------------------
    */
    $checkOrders = mysqli_query($conn, "SELECT COUNT(*) as total_orders FROM orders");
    $orderData = mysqli_fetch_assoc($checkOrders);

    if ($orderData['total_orders'] == 0) {
        $stats['top_items'] = []; // reset empty
    } else {
        $stats['top_items'] = $topItems;
    }

    echo json_encode($stats);
    exit;
    }

    if ($action === 'get_orders') {
        header('Content-Type: application/json');
        $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $filter = mysqli_real_escape_string($conn, $_GET['filter'] ?? 'all');

        $where = "1=1";
        if ($filter !== 'all') {
            $where .= " AND o.status='$filter'";
        }
        if ($search) {
            $where .= " AND (o.id LIKE '%$search%' OR u.fullname LIKE '%$search%' OR u.email LIKE '%$search%')";
        }

        $sql = "SELECT o.*, u.fullname as customer_name, u.email as customer_email, u.contact as customer_contact
                FROM orders o LEFT JOIN users u ON o.user_id=u.id
                WHERE $where ORDER BY o.order_date DESC";
        $result = mysqli_query($conn, $sql);
        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $oid = $row['id'];
            $items_r = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id='$oid'");
            $items = [];
            while ($item = mysqli_fetch_assoc($items_r)) {
                $items[] = $item;
            }
            $row['items'] = $items;
            $orders[] = $row;
        }
        echo json_encode($orders);
        exit;
    }

    if ($action === 'update_order_status') {
        header('Content-Type: application/json');
        $order_id = mysqli_real_escape_string($conn, $_POST['order_id'] ?? '');
        $status   = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
    // Fetch current status from DB
    $current_row    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM orders WHERE id='$order_id'"));
    $current_status = $current_row['status'] ?? '';

    // Forward-only transitions
$allowed_transitions = [
    'processing'       => ['preparing'],
    'preparing'        => ['to-ship'],
    'to-ship'          => ['out-for-delivery'],
    'out-for-delivery' => ['order-received'],
    'order-received'   => ['completed'],
];

    if (!isset($allowed_transitions[$current_status]) || !in_array($status, $allowed_transitions[$current_status])) {
        echo json_encode(['success' => false, 'message' => "Cannot change status from '$current_status' to '$status'."]);
        exit;
    }

    $sql = "UPDATE orders SET status='$status' WHERE id='$order_id'";
                    
        if (mysqli_query($conn, $sql)) {
        
            // Log activity
            $actor = 'Admin';
            $detail = "Order #$order_id status changed to $status";
            if (!mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('Admin', 'Order Status Change', '$detail')")) {
        error_log("Activity log failed: " . mysqli_error($conn));
    }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }
    if ($action === 'log_activity') {
        header('Content-Type: application/json');
        $actor   = mysqli_real_escape_string($conn, $_POST['actor'] ?? 'Unknown');
        $type    = mysqli_real_escape_string($conn, $_POST['action_type'] ?? 'Profile Update');
        $details = mysqli_real_escape_string($conn, $_POST['details'] ?? '');
        
        if (mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('$actor', '$type', '$details')")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }

    if ($action === 'get_inventory') {
        header('Content-Type: application/json');
        $search   = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $category = mysqli_real_escape_string($conn, $_GET['category'] ?? 'all');

        $where = "1=1";
        if ($category !== 'all') {
            $where .= " AND category='$category'";
        }
        if ($search) {
            $where .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
        }

        $result = mysqli_query($conn, "SELECT * FROM products WHERE $where ORDER BY name ASC");
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }

        // Aggregates
        $agg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, SUM(stock) as total_stock, SUM(price*stock) as total_value FROM products"));
        $low = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE stock <= 10"));

        echo json_encode([
            'products'    => $products,
            'total'       => $agg['total'],
            'total_stock' => $agg['total_stock'] ?? 0,
            'total_value' => $agg['total_value'] ?? 0,
            'low_stock'   => $low['c'],
        ]);
        exit;
    }

    if ($action === 'update_stock') {
        header('Content-Type: application/json');
        $id    = intval($_POST['id'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);

        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, stock FROM products WHERE id=$id"));

        if (mysqli_query($conn, "UPDATE products SET stock=$stock, updated_at=NOW() WHERE id=$id")) {
            $detail = "Stock for '{$old['name']}' updated from {$old['stock']} to $stock";
            mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('Admin', 'Stock Update', '$detail')");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }
    if ($action === 'save_product') {
        header('Content-Type: application/json');
        $id          = intval($_POST['id'] ?? 0);
        $name        = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $stock       = intval($_POST['stock'] ?? 0);
        $category    = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
        $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
        $image       = mysqli_real_escape_string($conn, $_POST['image'] ?? '');

        // Fetch OLD values BEFORE update
        $old = null;
        if ($id > 0) {
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, price, stock, category FROM products WHERE id=$id"));
        }

        if ($id > 0) {
            $sql = "UPDATE products SET name='$name', price=$price, stock=$stock, category='$category', description='$description', image='$image', updated_at=NOW() WHERE id=$id";
        } else {
            $sql = "INSERT INTO products (name, price, stock, category, description, image, created_at, updated_at) VALUES ('$name',$price,$stock,'$category','$description','$image',NOW(),NOW())";
        }

        if (mysqli_query($conn, $sql)) {
            // Build detail OUTSIDE the if blocks so it's always defined
            if ($id > 0 && $old) {
                $changed = [];
                if ($old['name']            !== $name)            $changed[] = "name → '$name'";
                if (floatval($old['price']) !== floatval($price)) $changed[] = "price → ₱$price";
                if (intval($old['stock'])   !== intval($stock))   $changed[] = "stock → $stock";
                if ($old['category']        !== $category)        $changed[] = "category → '$category'";

                $detail = !empty($changed)
                    ? "Product '$name' updated: " . implode(', ', $changed)
                    : "Product '$name' updated (no changes)";

                $action_type = 'Stock Update';
            } else {
                $detail      = "Product '$name' added — Price: ₱$price, Stock: $stock, Category: $category";
                $action_type = 'Stock Update';
            }

            $detail      = mysqli_real_escape_string($conn, $detail);
            $action_type = mysqli_real_escape_string($conn, $action_type);

            mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('Admin', '$action_type', '$detail')");

            $new_id = $id > 0 ? $id : mysqli_insert_id($conn);
            echo json_encode(['success' => true, 'id' => $new_id]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }

    if ($action === 'delete_product') {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        $nameR = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM products WHERE id=$id"));
        if (mysqli_query($conn, "DELETE FROM products WHERE id=$id")) {
            $detail = "Product '{$nameR['name']}' deleted";
            mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('Admin','Product Delete','$detail')");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }

    if ($action === 'get_pending_orders') {
        header('Content-Type: application/json');

        $result = mysqli_query($conn, "
            SELECT o.*, u.fullname AS customer_name, u.address AS customer_address
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.status = 'pending'
            ORDER BY o.order_date DESC
        ");

        $orders = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $oid       = $row['id'];
            $items_res = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id='$oid'");
            $items     = [];
            while ($item = mysqli_fetch_assoc($items_res)) {
                $items[] = $item;
            }
            $row['items'] = $items;
            $orders[]     = $row;
        }

        echo json_encode($orders);
        exit;
    }

    if ($action === 'approve_order') {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);

        mysqli_query($conn, "UPDATE orders SET status='processing' WHERE id=$id AND status='pending'");
        $affected = mysqli_affected_rows($conn);

        if ($affected > 0) {
            $detail = "Order #$id approved and moved to Processing";
            mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('Admin', 'Order Approval', '$detail')");
            echo json_encode(['success' => true, 'message' => $detail]);
        } else {
            $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM orders WHERE id=$id"));
            if (!$check) {
                echo json_encode(['success' => false, 'message' => "Order #$id not found."]);
            } else {
                echo json_encode(['success' => false, 'message' => "Order is already '{$check['status']}', cannot approve."]);
            }
        }
        exit;
    }

    if ($action === 'reject_order') {
        header('Content-Type: application/json');
        $id     = intval($_POST['id'] ?? 0);
        $reason = mysqli_real_escape_string($conn, trim($_POST['reason'] ?? 'No reason provided.'));

        mysqli_query($conn, "UPDATE orders SET status='cancelled' WHERE id=$id AND status='pending'");
        $affected = mysqli_affected_rows($conn);

        if ($affected > 0) {
            $detail = "Order #$id rejected. Reason: $reason";
            mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('Admin', 'Order Rejection', '$detail')");
            echo json_encode(['success' => true, 'message' => $detail]);
        } else {
            $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM orders WHERE id=$id"));
            if (!$check) {
                echo json_encode(['success' => false, 'message' => "Order #$id not found."]);
            } else {
                echo json_encode(['success' => false, 'message' => "Order is already '{$check['status']}', cannot reject."]);
            }
        }
        exit;
    }
    if ($action === 'clear_logs') {
        header('Content-Type: application/json');
        if (mysqli_query($conn, "TRUNCATE TABLE activity_logs")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }

    if ($action === 'get_activity_logs') {
        header('Content-Type: application/json');
        $search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
        $where  = "1=1";
        if ($search) {
            $where .= " AND (actor LIKE '%$search%' OR action_type LIKE '%$search%' OR details LIKE '%$search%')";
        }
        $result = mysqli_query($conn, "SELECT * FROM activity_logs WHERE $where ORDER BY created_at DESC LIMIT 100");
        $logs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = $row;
        }
        echo json_encode($logs);
        exit;
    }

    // Log this admin login view
    if (empty($_SESSION['admin_logged_once'])) {
    $_SESSION['admin_logged_once'] = true;
    mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('{$admin['fullname']}','Login','Successfully login using Admin account')");
}
    
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Panel</title>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            /* Add to <style> block */
            .badge-activity-profile { 
        
            background: rgba(20,184,166,.15); 
            color: var(--teal); 
            border: 1px solid rgba(20,184,166,.3); 
    }
            :root {
                --bg:        #fd0c70ff;
                --surface:   #fffafcff;
                --surface2:  #ece7eaff;
                --border:    #2a2f42;
                --accent:    #4f8dff;
                --accent2:   #a78bfa;
                --green:     #22c55e;
                --yellow:    #f59e0b;
                --red:       #ef4444;
                --teal:      #14b8a6;
                --text:      #000000ff;
                --muted:     #64748b;
                --sidebar-w: 220px;
            }

            body {
                font-family: 'DM Sans', sans-serif;
                background: var(--bg);
                color: var(--text);
                display: flex;
                min-height: 100vh;
                font-size: 14px;
            }

            /* ─── SIDEBAR ──────────────────────────────────────── */
            .sidebar {
                width: var(--sidebar-w);
                background: var(--surface);
                border-right: 1px solid var(--border);
                display: flex;
                flex-direction: column;
                position: fixed;
                top: 0; left: 0;
                height: 100vh;
                z-index: 100;
            }

            .sidebar-logo {
                padding: 24px 20px 20px;
                border-bottom: 1px solid var(--border);
            }
            .sidebar-logo .brand {
                font-size: 18px;
                font-weight: 700;
                color: var(--text);
                letter-spacing: -0.5px;
            }
            .sidebar-logo .brand span { color: var(--accent); }
            .sidebar-logo .badge {
                display: inline-block;
                margin-top: 4px;
                font-size: 10px;
                font-family: 'DM Mono', monospace;
                background: rgba(206, 206, 206, 0.15);
                color: var(--accent);
                border: 1px solid rgba(79,141,255,.3);
                padding: 1px 8px;
                border-radius: 20px;
            }

            .sidebar-nav {
                flex: 1;
                padding: 16px 12px;
                display: flex;
                flex-direction: column;
                gap: 2px;
                overflow-y: auto;
            }

            .nav-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 12px;
                border-radius: 8px;
                cursor: pointer;
                color: var(--muted);
                font-weight: 500;
                transition: all .15s ease;
                border: 1px solid transparent;
                text-decoration: none;
                font-size: 13.5px;
            }
            .nav-item:hover {
                background: var(--surface2);
                color: var(--text);
            }
            .nav-item.active {
                background: rgba(79,141,255,.12);
                color: var(--accent);
                border-color: rgba(79,141,255,.2);
            }
            .nav-item svg { flex-shrink: 0; }

            .nav-section {
                font-size: 10px;
                font-family: 'DM Mono', monospace;
                color: var(--muted);
                letter-spacing: 1px;
                text-transform: uppercase;
                padding: 12px 12px 4px;
            }

            .sidebar-footer {
                padding: 16px 12px;
                border-top: 1px solid var(--border);
            }
            .admin-card {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px;
                background: var(--surface2);
                border-radius: 8px;
            }
            .admin-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--accent), var(--accent2));
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 13px;
                font-weight: 700;
                color: #fff;
                flex-shrink: 0;
            }
            .admin-info { flex: 1; min-width: 0; }
            .admin-name { font-size: 12.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .admin-role { font-size: 11px; color: var(--accent); }
            .logout-btn {
                background: none;
                border: none;
                color: var(--muted);
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                transition: color .15s;
            }
            .logout-btn:hover { color: var(--red); }

            /* ─── MAIN ─────────────────────────────────────────── */
            .main {
                margin-left: var(--sidebar-w);
                flex: 1;
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }

            .topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 28px;
                background: var(--surface);
                border-bottom: 1px solid var(--border);
                position: sticky;
                top: 0;
                z-index: 50;
            }
            .page-title { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
            .page-subtitle { font-size: 12px; color: var(--muted); margin-top: 2px; }
            .topbar-right { display: flex; align-items: center; gap: 12px; }
            .topbar-time {
                font-family: 'DM Mono', monospace;
                font-size: 12px;
                color: var(--muted);
            }

            .content {
                padding: 24px 28px;
                flex: 1;
            }

            /* ─── TABS ─────────────────────────────────────────── */
            .tab-panel { display: none; }
            .tab-panel.active { display: block; }

            /* ─── CARDS ────────────────────────────────────────── */
            .card {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }
            .card-title { font-size: 15px; font-weight: 600; }
            .card-subtitle { font-size: 12px; color: var(--muted); margin-top: 2px; }

            /* ─── STAT CARDS ───────────────────────────────────── */
            .stats-row {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
                margin-bottom: 20px;
            }
            .stat-card {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 20px;
                display: flex;
                align-items: center;
                gap: 16px;
            }
            .stat-icon {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .stat-icon.blue  { background: rgba(79,141,255,.15); color: var(--accent); }
            .stat-icon.green { background: rgba(34,197,94,.15);  color: var(--green); }
            .stat-icon.amber { background: rgba(245,158,11,.15); color: var(--yellow); }
            .stat-icon.purple{ background: rgba(167,139,250,.15);color: var(--accent2); }
            .stat-info {}
            .stat-label { font-size: 12px; color: var(--muted); font-weight: 500; }
            .stat-value { font-size: 26px; font-weight: 700; font-family: 'DM Mono', monospace; letter-spacing: -1px; margin-top: 2px; }

            /* ─── TABLES ───────────────────────────────────────── */
            .table-wrap { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; }
            th {
                text-align: left;
                padding: 10px 14px;
                font-size: 11px;
                font-family: 'DM Mono', monospace;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: .5px;
                border-bottom: 1px solid var(--border);
            }
            td {
                padding: 12px 14px;
                border-bottom: 1px solid rgba(42,47,66,.5);
                font-size: 13.5px;
                vertical-align: middle;
            }
            tr:last-child td { border-bottom: none; }
            tr:hover td { background: rgba(255,255,255,.02); }

            /* ─── STATUS BADGES ────────────────────────────────── */
            .badge {
                display: inline-flex;
                align-items: center;
                padding: 3px 10px;
                border-radius: 20px;
                font-size: 11.5px;
                font-weight: 600;
                white-space: nowrap;
            }
            .badge-pending       { background: rgba(245,158,11,.15); color: var(--yellow); border: 1px solid rgba(245,158,11,.3); }
            .badge-processing    { background: rgba(79,141,255,.15);  color: var(--accent);  border: 1px solid rgba(79,141,255,.3); }
            .badge-preparing     { background: rgba(167,139,250,.15); color: var(--accent2); border: 1px solid rgba(167,139,250,.3); }
            .badge-shipping      { background: rgba(20,184,166,.15);  color: var(--teal);    border: 1px solid rgba(20,184,166,.3); }
            .badge-received      { background: rgba(34,197,94,.15);   color: var(--green);   border: 1px solid rgba(34,197,94,.3); }
            .badge-cancelled     { background: rgba(239,68,68,.15);   color: var(--red);     border: 1px solid rgba(239,68,68,.3); }
            .badge-completed     { background: rgba(34,197,94,.15);   color: var(--green);   border: 1px solid rgba(34,197,94,.3); }
            .badge-active        { background: rgba(34,197,94,.15);   color: var(--green);   border: 1px solid rgba(34,197,94,.3); }
            .badge-low           { background: rgba(239,68,68,.15);   color: var(--red);     border: 1px solid rgba(239,68,68,.3); }
            .badge-activity-order{ background: rgba(245,158,11,.15);  color: var(--yellow);  border: 1px solid rgba(245,158,11,.3); }
            .badge-activity-stock{ background: rgba(20,184,166,.15);  color: var(--teal);    border: 1px solid rgba(20,184,166,.3); }
            .badge-activity-login{ background: rgba(79,141,255,.15);  color: var(--accent);  border: 1px solid rgba(79,141,255,.3); }
            .badge-activity-product{ background: rgba(167,139,250,.15);color: var(--accent2);border: 1px solid rgba(167,139,250,.3);}
            .badge-admin         { background: rgba(239,68,68,.12);   color: var(--red);     border: 1px solid rgba(239,68,68,.25);}
            .badge-user          { background: rgba(79,141,255,.12);  color: var(--accent);  border: 1px solid rgba(79,141,255,.25);}

            /* ─── BUTTONS ──────────────────────────────────────── */
            .btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 14px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                border: 1px solid transparent;
                transition: all .15s ease;
                font-family: 'DM Sans', sans-serif;
            }
            .btn-primary { background: var(--accent); color: #fff; }
            .btn-primary:hover { background: #3a7af0; }
            .btn-ghost { background: transparent; color: var(--muted); border-color: var(--border); }
            .btn-ghost:hover { background: var(--surface2); color: var(--text); }
            .btn-danger { background: rgba(239,68,68,.15); color: var(--red); border-color: rgba(239,68,68,.3); }
            .btn-danger:hover { background: rgba(239,68,68,.25); }
            .btn-success { background: rgba(34,197,94,.15); color: var(--green); border-color: rgba(34,197,94,.3); }
            .btn-success:hover { background: rgba(34,197,94,.25); }
            .btn-sm { padding: 5px 10px; font-size: 12px; }
            .btn-icon { padding: 6px; border-radius: 6px; }

            /* ─── FORMS ────────────────────────────────────────── */
            .search-bar {
                background: var(--color-background-primary, #ffffff);
                display: flex;
                gap: 10px;
                margin-bottom: 16px;
                flex-wrap: wrap;
            }
            .input-wrap {
                position: relative;
                flex: 1;
                min-width: 200px;
            }
            .input-wrap svg {
                position: absolute;
                left: 10px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--muted);
            }
            input[type=text], input[type=number], input[type=email], select, textarea {
                background: #fff
                border: 1px solid var(--border);
                color: var(--text);
                border-radius: 8px;
                font-family: 'DM Sans', sans-serif;
                font-size: 13.5px;
                transition: border-color .15s;
            }
            input[type=text]:focus, input[type=number]:focus, input[type=email]:focus, select:focus, textarea:focus {
                outline: none;
                border-color: var(--accent);
            }
            .search-input {
                width: 100%;
                padding: 9px 12px 9px 36px;
            }
            .filter-select {
                padding: 9px 14px;
                min-width: 140px;
            }
            .form-group { margin-bottom: 14px; }
            .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .4px; }
            .form-group input, .form-group select, .form-group textarea {
                width: 100%;
                padding: 9px 12px;
            }
            .form-group textarea { resize: vertical; min-height: 80px; }
            .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

            /* ─── MODAL ────────────────────────────────────────── */
            .modal-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.65);
                z-index: 999;
                align-items: center;
                justify-content: center;
            }
            .modal-overlay.open { display: flex; }
            .modal {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 28px;
                width: 90%;
                max-width: 560px;
                max-height: 90vh;
                overflow-y: auto;
                animation: slideUp .2s ease;
            }
            .modal-lg { max-width: 720px; }
            @keyframes slideUp {
                from { transform: translateY(20px); opacity: 0; }
                to   { transform: translateY(0);    opacity: 1; }
            }
            .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
            .modal-title { font-size: 17px; font-weight: 700; }
            .modal-close { background: none; border: none; color: var(--muted); cursor: pointer; padding: 4px; border-radius: 4px; }
            .modal-close:hover { color: var(--text); }
            .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }

            /* ─── DASHBOARD ────────────────────────────────────── */
            .dashboard-grid {
                display: grid;
                grid-template-columns: 1fr 340px;
                gap: 20px;
            }
            .chart-area { height: 240px; position: relative; }
            .recent-order-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid rgba(42,47,66,.5);
                font-size: 13px;
            }
            .recent-order-row:last-child { border-bottom: none; }
            .ro-id { font-family: 'DM Mono', monospace; font-size: 12px; color: var(--muted); }
            .ro-name { font-weight: 500; }
            .ro-total { font-family: 'DM Mono', monospace; color: var(--green); font-size: 13px; }

            /* ─── STATUS SELECT ────────────────────────────────── */
            .status-select {
                padding: 5px 10px;
                border-radius: 6px;
                font-size: 12px;
                min-width: 130px;
            }

            /* ─── PRODUCT IMAGE ────────────────────────────────── */
            .prod-img {
                width: 40px;
                height: 40px;
                border-radius: 8px;
                object-fit: cover;
                background: var(--surface2);
            }

            /* ─── STOCK BAR ────────────────────────────────────── */
            .stock-bar {
                height: 4px;
                background: var(--border);
                border-radius: 2px;
                margin-top: 4px;
                width: 80px;
            }
            .stock-fill {
                height: 100%;
                border-radius: 2px;
                background: var(--green);
                transition: width .3s;
            }
            .stock-fill.low { background: var(--red); }
            .stock-fill.med { background: var(--yellow); }

            /* ─── EMPTY STATE ──────────────────────────────────── */
            .empty-state {
                text-align: center;
                padding: 48px 24px;
                color: var(--muted);
            }
            .empty-state svg { margin-bottom: 12px; opacity: .4; }
            .empty-state p { font-size: 14px; }

            /* ─── ACTIVITY LOG ─────────────────────────────────── */
            .log-row td { padding: 10px 14px; }
            .log-details { color: var(--muted); font-size: 13px; }
            .log-time { font-family: 'DM Mono', monospace; font-size: 11.5px; color: var(--muted); white-space: nowrap; }

            /* ─── ORDER STATUS COUNTS ──────────────────────────── */
            .status-counts {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }
            .sc-card {
                background: var(--surface);
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: 16px;
                text-align: center;
            }
            .sc-num { font-size: 28px; font-weight: 700; font-family: 'DM Mono', monospace; }
            .sc-label { font-size: 12px; color: var(--muted); margin-top: 4px; }
            .sc-card.pending  { border-color: rgba(245,158,11,.4); }
            .sc-card.shipping { border-color: rgba(20,184,166,.4); }
            .sc-card.received { border-color: rgba(34,197,94,.4);  }
            .sc-card.cancelled{ border-color: rgba(239,68,68,.4);  }

            /* ─── MISC ─────────────────────────────────────────── */
            .text-muted { color: var(--muted); font-size: 12px; }
            .font-mono { font-family: 'DM Mono', monospace; }
            .divider { border: none; border-top: 1px solid var(--border); margin: 16px 0; }
            .spinner {
                width: 20px; height: 20px;
                border: 2px solid var(--border);
                border-top-color: var(--accent);
                border-radius: 50%;
                animation: spin .7s linear infinite;
                display: inline-block;
            }
            @keyframes spin { to { transform: rotate(360deg); } }

            /* ─── TOAST ────────────────────────────────────────── */
            #toast {
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: var(--surface2);
                border: 1px solid var(--border);
                color: var(--text);
                padding: 12px 18px;
                border-radius: 10px;
                font-size: 13.5px;
                font-weight: 500;
                z-index: 9999;
                transform: translateY(80px);
                opacity: 0;
                transition: all .25s ease;
                display: flex;
                align-items: center;
                gap: 10px;
                max-width: 340px;
                box-shadow: 0 8px 30px rgba(0,0,0,.4);
            }
            #toast.show { transform: translateY(0); opacity: 1; }
            #toast.success { border-color: rgba(34,197,94,.4); }
            #toast.error   { border-color: rgba(239,68,68,.4); }

            /* ─── APPROVAL CARDS ───────────────────────────────── */
            
            .approval-order-items {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin: 10px 0;
        padding: 10px;
        background: var(--bg-secondary, #f9f9f9);
        border-radius: 8px;
        font-size: 13px;
    }

    .approval-order-item {
        display: flex;
        justify-content: space-between;
        padding: 3px 0;
        border-bottom: 1px solid var(--border, #eee);
    }

    .approval-order-item:last-child {
        border-bottom: none;
    }

    .approval-order-total {
        font-size: 13px;
        text-align: right;
        padding-top: 6px;
        color: var(--muted);
    }

    .approval-order-total strong {
        color: var(--text);
        font-size: 15px;
    }
            .approval-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 16px;
            }
            .approval-card {
                background: var(--color-background-primary, #ffffff);
                border: 1px solid var(--border);
                border-radius: 12px;
                overflow: hidden;
            }
            .approval-card-img {
                height: 160px;
                background: var(--bg);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .approval-card-img img { width: 100%; height: 100%; object-fit: cover; }
            .approval-card-body { padding: 14px; }
            .approval-card-name { font-weight: 700; font-size: 15px; margin-bottom: 4px; }
            .approval-card-meta { font-size: 12px; color: var(--muted); margin-bottom: 10px; }
            .approval-card-actions { display: flex; gap: 8px; }

            /* Scrollbar styling */
            ::-webkit-scrollbar { width: 6px; height: 6px; }
            ::-webkit-scrollbar-d{ background: transparent; }
            ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
            ::-webkit-scrollbar-thumb:hover { background: var(--muted); }
        </style>
    </head>
    <body>

    <!-- ═══════════════════ SIDEBAR ═══════════════════ -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="brand">Sweet-String</div>
            <div class="badge">ADMIN PANEL</div>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section">Main</span>
            <a class="nav-item active" onclick="switchTab('dashboard')" href="#">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a class="nav-item" onclick="switchTab('orders')" href="#">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6M9 16h4"/></svg>
                Orders
            </a>

            <span class="nav-section">Inventory</span>
            <a class="nav-item" onclick="switchTab('inventory')" href="#">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                Inventory
            </a>
            <span class="nav-section">System</span>
            <a class="nav-item" onclick="switchTab('approval')" href="#">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Order Approval
            </a>
            <a class="nav-item" onclick="switchTab('activity')" href="#">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Activity Logs
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-card">
                <div class="admin-avatar"><?php echo strtoupper(substr($admin['fullname'], 0, 1)); ?></div>
                <div class="admin-info">
                    <div class="admin-name"><?php echo htmlspecialchars($admin['fullname']); ?></div>
                    <div class="admin-role">Administrator</div>
                </div>
                <button class="logout-btn" onclick="handleLogout()" title="Logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </div>
        </div>
    </aside>

    <!-- ═══════════════════ MAIN ═══════════════════ -->
    <main class="main">
        <div class="topbar">
            <div>
                <div class="page-title" id="pageTitle">Dashboard</div>
                <div class="page-subtitle" id="pageSubtitle">Overview of your store</div>
            </div>
            <div class="topbar-right">
                <div class="topbar-time" id="topbarTime"></div>
            </div>
        </div>

        <div class="content">

            <!-- ══════════════ DASHBOARD TAB ══════════════ -->
            <div class="tab-panel active" id="tab-dashboard">
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Customers</div>
                            <div class="stat-value" id="statCustomers">—</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Income</div>
                            <div class="stat-value" id="statIncome">—</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Sold</div>
                            <div class="stat-value" id="statSold">—</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <div class="card-title">Income Statistics</div>
                                    <div class="card-subtitle">Revenue from completed orders</div>
                                </div>
                            </div>
                            <div class="chart-area">
                                <canvas id="incomeChart"></canvas>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Recent Orders</div>
                                <a class="btn btn-ghost btn-sm" onclick="switchTab('orders')" href="#">See Full Details</a>
                            </div>
                            <div id="recentOrdersTable">
                                <div style="text-align:center;padding:24px;color:var(--muted);">Loading...</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Most Ordered Items</div>
                            </div>
                            <div class="chart-area" style="height:220px;">
                                <canvas id="donutChart"></canvas>
                            </div>
                            <div id="donutLegend" style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;font-size:12px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════ ORDERS TAB ══════════════ -->
            <div class="tab-panel" id="tab-orders">
                <div class="status-counts">
                    <div class="sc-card pending">
                        <div class="sc-num" id="scPending" style="color:var(--red)">—</div>
                        <div class="sc-label">Pending</div>
                        <div class="text-muted" style="font-size:11px;margin-top:2px;">New orders awaiting</div>
                    </div>
                    <div class="sc-card shipping">
                        <div class="sc-num" id="scShipping" style="color:var(--teal)">—</div>
                        <div class="sc-label">To Ship</div>
                        <div class="text-muted" style="font-size:11px;margin-top:2px;">Orders awaiting shipment</div>
                    </div>
                    <div class="sc-card to-recieve">
                        <div class="sc-num" id="scToreceive" style="color:var(--yellow)">—</div>
                        <div class="sc-label">To Recieve</div>
                        <div class="text-muted" style="font-size:11px;margin-top:2px;">Orders Out for Delivery</div>
                    </div>
                    <div class="sc-card completed">
                        <div class="sc-num" id="scCompleted" style="color:var(--green)">—</div>
                        <div class="sc-label">Completed</div>
                        <div class="text-muted" style="font-size:11px;margin-top:2px;">Successfully Fulfilled</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"></div>
                            <div class="card-subtitle">Track and manage order statuses efficiently</div>
                        </div>
                    </div>
                    <div class="search-bar">
                        <div class="input-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" class="search-input" id="ordersSearch" placeholder="Search orders..." oninput="loadOrders()">
                        </div>
<select class="filter-select" id="ordersFilter" onchange="loadOrders()">
    <option value="all">All Orders</option>
    <option value="pending">Pending</option>
    <option value="processing">Processing</option>
    <option value="preparing">Preparing</option>
    <option value="to-ship">To Ship</option>
    <option value="out-for-delivery">Out for Delivery</option>
    <option value="order-received">Order Received</option>
    <option value="completed">Completed</option>
    <option value="cancelled">Cancelled</option>
</select>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Item(s)</th>
                                    <th>Profit</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody">
                                <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted);">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══════════════ INVENTORY TAB ══════════════ -->
            <div class="tab-panel" id="tab-inventory">
                <div class="stats-row" style="grid-template-columns: repeat(4,1fr);">
                    <div class="stat-card">
                        <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></div>
                        <div class="stat-info"><div class="stat-label">Products</div><div class="stat-value" style="font-size:22px;" id="invTotal">—</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg></div>
                        <div class="stat-info"><div class="stat-label">Total Value</div><div class="stat-value" style="font-size:18px;" id="invValue">—</div></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                        <div class="stat-info"><div class="stat-label">Total Stock</div><div class="stat-value" style="font-size:22px;" id="invStock">—</div></div>
                    </div>
                    <div class="stat-card" style="border-color:rgba(239,68,68,.3);">
                        <div class="stat-icon" style="background:rgba(239,68,68,.15);color:var(--red);"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
                        <div class="stat-info"><div class="stat-label">Low Stock</div><div class="stat-value" style="font-size:22px;color:var(--red);" id="invLow">—</div></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"></div>
                            <div class="card-subtitle">Manage and track all your inventory</div>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="openProductModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Product
                        </button>
                    </div>
                    <div class="search-bar">
                        <div class="input-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" class="search-input" id="invSearch" placeholder="Search products..." oninput="loadInventory()">
                        </div>
                        <select class="filter-select" id="invCategory" onchange="loadInventory()">
                            <option value="all">All Categories</option>
                            <option value="Cookies">Cookies</option>
                            <option value="Pastries">Pastries</option>
                            <option value="Cheescakes">Cheescakes</option>
                            <option value="Cupcakes">Cupcakes</option>
                        </select>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="invTableBody">
                                <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted);">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

    <!-- ══════════════ ORDER APPROVAL TAB ══════════════ -->
    <div class="tab-panel" id="tab-approval">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title"></div>
                    <div class="card-subtitle">Review and approve orders placed by customers</div>
                </div>
                <button class="btn btn-ghost btn-sm" onclick="loadApproval()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
                    </svg>
                    Refresh
                </button>
            </div>
            <div id="approvalContent">
                <div style="text-align:center;padding:32px;color:var(--muted);">Loading...</div>
            </div>
        </div>
    </div>

            <!-- ══════════════ ACTIVITY TAB ══════════════ -->
            <div class="tab-panel" id="tab-activity">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"></div>
                            <div class="card-subtitle">Track all admin and employee actions</div>
                        </div>
                        <div style="display:flex;gap:10px;">
                            <div class="input-wrap" style="min-width:220px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" class="search-input" id="activitySearch" placeholder="Find logs activity" oninput="loadActivity()">
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="loadActivity()">Find</button>
                        </div>
                    </div>
                    
    <button class="btn btn-danger btn-sm" onclick="clearLogs()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
        </svg>
        Clear Logs
    </button>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Actor</th>
                                    <th>Action Type</th>
                                    <th>Details</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="activityTableBody">
                                <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--muted);">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /content -->
    </main>

    <!-- ═══════════════════ PRODUCT MODAL ═══════════════════ -->
    <div class="modal-overlay" id="productModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div class="modal-title" id="productModalTitle">Add Product</div>
                <button class="modal-close" onclick="closeProductModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <input type="hidden" id="productEditId">
            <div class="form-row">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" id="pName" placeholder="">
                </div>
                <div class="form-group">
                    <label>Price (₱)</label>
                    <input type="number" id="pPrice" step="0.01" placeholder="0.00">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" id="pStock" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select id="pCategory">
                        <option value="Cookies">Cookies</option>
                        <option value="Pastries">Pastries</option>
                        <option value="Cheescakes">Cheescakes</option>
                        <option value="Cupcakes">Cupcakes</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="text" id="pImage" placeholder="images/products/product.jpg">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="pDescription" rows="3" placeholder="Product description..."></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeProductModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveProduct()">Save Product</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ ORDER DETAIL MODAL ═══════════════════ -->
    <div class="modal-overlay" id="orderDetailModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <div class="modal-title">Order Details</div>
                <button class="modal-close" onclick="closeOrderModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div id="orderDetailContent"></div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeOrderModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ TOAST ═══════════════════ -->
    <div id="toast"></div>
    <script>
    // ─── STATE ───────────────────────────────────────────────────────────────────
const IMAGE_SLOTS = {
    1:  'images/products/rasberry.jpg',
    2:  'images/products/pista.jpg',
    3:  'images/products/sc.jpg',
    4:  'images/products/rv.jpg',
    5:  'images/products/cf.jpg',
    6:  'images/products/lbc.jpg',
    7:  'images/products/cook.jpg',
    8:  'images/products/matcook.jpg',
    9:  'images/products/orco.jpg',
    10: 'images/products/ac.jpg',
    11: 'images/products/pac.jpg',
    12: 'images/products/sd.jpg',
    13: 'images/products/oc.jpg',
};



    let incomeChartInst = null;
    let donutChartInst  = null;

    // ─── TAB SWITCHING ───────────────────────────────────────────────────────────
    const tabMeta = {
        dashboard: { title: 'Dashboard',         subtitle: 'Overview of your store' },
        orders:    { title: 'Order Management',   subtitle: 'Track and manage order statuses efficiently' },
        inventory: { title: 'Inventory',          subtitle: 'Manage and track all your inventory' },
        approval:  { title: 'Order Approval',     subtitle: 'Review orders placed by customers' },
        activity:  { title: 'Activity Logs',      subtitle: 'Track all admin and employee actions' },
    };

    function switchTab(name) {
        sessionStorage.setItem('adminTab', name);
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(a => a.classList.remove('active'));
        document.getElementById('tab-' + name).classList.add('active');
        document.querySelectorAll('.nav-item').forEach(a => {
            if (a.getAttribute('onclick') && a.getAttribute('onclick').includes("'" + name + "'")) {
                a.classList.add('active');
            }
        });
        const m = tabMeta[name];
        if (m) {
            document.getElementById('pageTitle').textContent = m.title;
            document.getElementById('pageSubtitle').textContent = m.subtitle;
        }
        if (name === 'dashboard') loadDashboard();
        if (name === 'orders')    loadOrders();
        if (name === 'inventory') loadInventory();
        if (name === 'approval')  loadApproval();
        if (name === 'activity')  loadActivity();
    }

    // ─── TOAST ───────────────────────────────────────────────────────────────────
    function toast(msg, type = '') {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.className = 'show ' + type;
        clearTimeout(el._t);
        el._t = setTimeout(() => el.className = '', 3000);
    }

    // ─── CLOCK ───────────────────────────────────────────────────────────────────
    function updateClock() {
        const now = new Date();
        document.getElementById('topbarTime').textContent =
            now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
            '  ' + now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ─── STATUS HELPERS ───────────────────────────────────────────────────────────
function statusBadge(status) {
    const map = {
        'pending':          ['Pending',           'badge-pending'],
        'processing':       ['Processing',         'badge-processing'],
        'preparing':        ['Preparing',          'badge-preparing'],
        'to-ship':          ['To Ship',            'badge-shipping'],
        'out-for-delivery': ['Out for Delivery',   'badge-shipping'],
        'order-received':   ['Order Received',     'badge-received'],
        'completed':        ['Completed',          'badge-completed'],
        'cancelled':        ['Cancelled',          'badge-cancelled'],
    };
    const [label, cls] = map[status] || [status, 'badge-processing'];
    return `<span class="badge ${cls}">${label}</span>`;
}

const STATUS_OPTIONS = [
    { value: 'processing',       label: 'Processing' },
    { value: 'preparing',        label: 'Preparing' },
    { value: 'to-ship',          label: 'To Ship' },
    { value: 'out-for-delivery', label: 'Out for Delivery' },
    { value: 'order-received',   label: 'Order Received' },
    { value: 'completed',        label: 'Completed' },
];

function statusSelect(orderId, currentStatus) {
    // Pending orders can only be changed via Order Approval tab, not here
    if (currentStatus === 'pending') {
        return `<span style="font-size:12px;color:var(--muted);font-style:italic;">Awaiting approval</span>`;
    }

    // Cancelled and completed are final states
    if (currentStatus === 'cancelled' || currentStatus === 'completed') {
        return statusBadge(currentStatus);
    }

    const currentIndex = STATUS_OPTIONS.findIndex(s => s.value === currentStatus);

    // Only allow the very next status, nothing else
    const opts = STATUS_OPTIONS.map((s, index) => {
        const isDisabled = index <= currentIndex; // disable current and all previous
        const isSelected = s.value === currentStatus;
        return `<option value="${s.value}" ${isSelected ? 'selected' : ''} ${isDisabled ? 'disabled' : ''}>${s.label}</option>`;
    }).join('');

    return `<select class="status-select filter-select" onchange="if(this.value !== '${currentStatus}') updateStatus('${orderId}', this.value)">${opts}</select>`;
}

    // ─── DASHBOARD ────────────────────────────────────────────────────────────────
    async function loadDashboard() {
        try {
            const res  = await fetch('admin.php?action=get_dashboard_stats');
            const data = await res.json();

            document.getElementById('statCustomers').textContent = data.customers ?? 0;
            document.getElementById('statIncome').textContent    = '₱' + parseFloat(data.income || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
            document.getElementById('statSold').textContent      = data.sold ?? 0;

            // Status counts
            const sc = data.status_counts || {};
            const toShip = (sc['to-ship'] || 0);
            const receive = sc['out-for-delivery'] || 0;
            document.getElementById('scPending').textContent   = sc['pending'] || 0;
            document.getElementById('scShipping').textContent  = toShip;
            document.getElementById('scToreceive').textContent  = receive;
            document.getElementById('scCompleted').textContent = sc['completed']||0;

            // Chart
            const chartData = data.chart || [];
            const labels  = chartData.map(d => {
                const [y, m] = d.month.split('-');
                return new Date(y, m-1).toLocaleString('en', { month: 'short', year: '2-digit' });
            });
            const values  = chartData.map(d => parseFloat(d.revenue || 0));

            const ctx = document.getElementById('incomeChart').getContext('2d');
            if (incomeChartInst) incomeChartInst.destroy();
            incomeChartInst = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Revenue',
                        data: values,
                        backgroundColor: 'rgba(79,141,255,.6)',
                        borderColor: 'rgba(79,141,255,1)',
                        borderWidth: 1,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => '₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits:2})
                            }
                        }
                    },
                    scales: {
                        x: { grid: { color: '#2a2f42' }, ticks: { color: '#64748b', font: { family: 'DM Mono', size: 11 } } },
                        y: { grid: { color: '#2a2f42' }, ticks: { color: '#64748b', font: { family: 'DM Mono', size: 11 }, callback: v => '₱' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v) } }
                    }
                }
            });

            // Donut chart - most ordered items
            const topItems = data.top_items || [];
            const COLORS = ['#4f8dff', '#a78bfa', '#f59e0b', '#22c55e', '#14b8a6'];
            const dctx = document.getElementById('donutChart').getContext('2d');
            if (donutChartInst) donutChartInst.destroy();
            donutChartInst = new Chart(dctx, {
                type: 'doughnut',
                data: {
                    labels: topItems.map(i => i.product_name),
                    datasets: [{
                        data: topItems.map(i => i.total_qty),
                        backgroundColor: COLORS,
                        borderColor: '#181c27',
                        borderWidth: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    cutout: '65%',
                }
            });

            // Legend
            document.getElementById('donutLegend').innerHTML = topItems.map((item, i) =>
                `<div style="display:flex;align-items:center;gap:4px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:${COLORS[i]};flex-shrink:0;"></div>
                    <span style="color:var(--muted)">${item.product_name} (${item.total_qty})</span>
                </div>`
            ).join('');

            // Recent orders table
            const recentOrders = data.recent_orders || [];
            if (recentOrders.length === 0) {
                document.getElementById('recentOrdersTable').innerHTML = '<div class="empty-state"><p>No orders yet.</p></div>';
            } else {
                document.getElementById('recentOrdersTable').innerHTML = recentOrders.map(o => `
        <div class="recent-order-row">
            <div>
                <div class="ro-name">${o.fullname || 'Unknown'}</div>
                <div class="ro-id">${o.id} &middot; ${new Date(o.order_date).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'})}</div>
                <div class="ro-id" style="margin-top:2px;max-width:340px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    ${o.product_names || '—'} &middot; ${o.item_count} item(s)
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                ${statusBadge(o.status)}
                <span class="ro-total">₱${parseFloat(o.total||0).toFixed(2)}</span>
            </div>
        </div>
    `).join('');
            }

        } catch (e) {
            console.error(e);
            toast('Failed to load dashboard', 'error');
        }
    }

    // ─── ORDERS ───────────────────────────────────────────────────────────────────
    async function loadOrders() {
        const search = document.getElementById('ordersSearch')?.value || '';
        const filter = document.getElementById('ordersFilter')?.value || 'all';
        const tbody  = document.getElementById('ordersTableBody');

        // Update status counts
        try {
            const res  = await fetch('admin.php?action=get_dashboard_stats');
            const data = await res.json();
            const sc = data.status_counts || {};
            const toShip = (sc['to-ship'] || 0);
            const receive = sc['out-for-delivery'] || 0;
            document.getElementById('scPending').textContent   = sc['pending']||0;
            document.getElementById('scShipping').textContent  = toShip;
            document.getElementById('scToreceive').textContent  = receive;
            document.getElementById('scCompleted').textContent = sc['completed']||0;
        } catch(e) {}

        try {
            const res    = await fetch(`admin.php?action=get_orders&search=${encodeURIComponent(search)}&filter=${filter}`);
            const orders = await res.json();

            if (orders.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg><p>No orders found</p></div></td></tr>`;
                return;
            }

            tbody.innerHTML = orders.map(o => {
                const itemCount = (o.items || []).reduce((s, i) => s + parseInt(i.quantity||0), 0);
                const profit    = parseFloat(o.total || 0) * 0.893; // example margin
                const date      = new Date(o.order_date).toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
                return `<tr>
                    <td>
                        <div class="font-mono" style="font-size:12px;">${o.id}</div>
                        <div class="text-muted">${date}</div>
                    </td>
                    <td>
                        <div style="font-weight:600;">${o.customer_name || 'Unknown'}</div>
                        <div class="text-muted">${o.customer_email || ''}</div>
                    </td>
                    <td style="font-weight:600;">${itemCount}</td>
                    <td class="font-mono" style="color:var(--accent);">₱${profit.toFixed(2)}</td>
                    <td class="font-mono" style="font-weight:600;">₱${parseFloat(o.total||0).toFixed(2)}</td>
                    <td>${statusBadge(o.status)}</td>
                    <td>${statusSelect(o.id, o.status)}</td>
                </tr>`;
            }).join('');
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--red);padding:24px;">Failed to load orders</td></tr>`;
        }
    }

    async function updateStatus(orderId, newStatus) {
        try {
            const fd = new FormData();
            fd.append('action', 'update_order_status');
            fd.append('order_id', orderId);
            fd.append('status', newStatus);
            const res  = await fetch('admin.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                toast('Order status updated!', 'success');
            } else {
                toast('Error: ' + data.message, 'error');
                loadOrders();
            }
        } catch (e) {
            toast('Network error', 'error');
        }
    }

    function closeOrderModal() {
        document.getElementById('orderDetailModal').classList.remove('open');
    }

    // ─── INVENTORY ────────────────────────────────────────────────────────────────
    async function loadInventory() {
        const search   = document.getElementById('invSearch')?.value || '';
        const category = document.getElementById('invCategory')?.value || 'all';
        const tbody    = document.getElementById('invTableBody');
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:24px;"><span class="spinner"></span></td></tr>`;

        try {
            const res  = await fetch(`admin.php?action=get_inventory&search=${encodeURIComponent(search)}&category=${category}`);
            const data = await res.json();

            document.getElementById('invTotal').textContent  = data.total || 0;
            document.getElementById('invValue').textContent  = '₱' + parseFloat(data.total_value||0).toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2});
            document.getElementById('invStock').textContent  = parseInt(data.total_stock||0).toLocaleString();
            document.getElementById('invLow').textContent    = data.low_stock || 0;

            const products = data.products || [];
            if (products.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><p>No products found</p></div></td></tr>`;
                return;
            }

            tbody.innerHTML = products.map((p, i) => {
                const stock    = parseInt(p.stock || 0);
                const maxStock = 500;
                const pct      = Math.min(stock / maxStock * 100, 100);
                const fillCls  = stock <= 10 ? 'low' : stock <= 50 ? 'med' : '';
                const stockBadge = stock <= 10
                    ? `<span class="badge badge-low">Low</span>`
                    : `<span class="badge badge-active">In Stock</span>`;

                return `<tr>
                    <td class="text-muted font-mono">${i+1}</td>
                 <td>
    ${IMAGE_SLOTS[i + 1]
        ? `<img src="${IMAGE_SLOTS[i + 1]}" class="prod-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
           <div class="prod-img" style="background:var(--surface2);display:none;align-items:center;justify-content:center;">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
           </div>`
        : `<div class="prod-img" style="background:var(--surface2);display:flex;align-items:center;justify-content:center;">
               <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
           </div>`}
</td>
                    <td>
                        <div style="font-weight:600;">${p.name}</div>
                        <div class="text-muted">${(p.description||'').substring(0,40)}${(p.description||'').length>40?'…':''}</div>
                    </td>
                    <td class="font-mono">₱${parseFloat(p.price||0).toFixed(2)}</td>
                    <td>${p.category || '—'}</td>
                    <td>
                        <div style="font-weight:600;">${stock.toLocaleString()} ${stockBadge}</div>
                        <div class="stock-bar"><div class="stock-fill ${fillCls}" style="width:${pct}%"></div></div>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-ghost btn-sm btn-icon" onclick="editProduct(${p.id})" title="Edit">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <button class="btn btn-danger btn-sm btn-icon" onclick="deleteProduct(${p.id}, '${p.name.replace(/'/g,"\\'")}' )" title="Delete">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;color:var(--red);padding:24px;">Failed to load inventory</td></tr>`;
        }
    }

    // ─── PRODUCT MODAL ────────────────────────────────────────────────────────────
    function openProductModal(data = null) {
        document.getElementById('productEditId').value = data ? data.id : '';
        document.getElementById('productModalTitle').textContent = data ? 'Edit Product' : 'Add Product';
        document.getElementById('pName').value        = data?.name || '';
        document.getElementById('pPrice').value       = data?.price || '';
        document.getElementById('pStock').value       = data?.stock || '';
        document.getElementById('pCategory').value    = data?.category || '';
        document.getElementById('pImage').value       = data?.image || '';
        document.getElementById('pDescription').value = data?.description || '';
        document.getElementById('productModal').classList.add('open');
    }

    function closeProductModal() {
        document.getElementById('productModal').classList.remove('open');
    }

    async function editProduct(id) {
        try {
            const res  = await fetch(`admin.php?action=get_inventory`);
            const data = await res.json();
            const p    = (data.products || []).find(x => x.id == id);
            if (p) openProductModal(p);
        } catch (e) { toast('Failed to load product', 'error'); }
    }

    async function saveProduct() {
        const fd = new FormData();
        fd.append('action', 'save_product');
        fd.append('id',          document.getElementById('productEditId').value);
        fd.append('name',        document.getElementById('pName').value.trim());
        fd.append('price',       document.getElementById('pPrice').value);
        fd.append('stock',       document.getElementById('pStock').value);
        fd.append('category',    document.getElementById('pCategory').value);
        fd.append('image',       document.getElementById('pImage').value.trim());
        fd.append('description', document.getElementById('pDescription').value.trim());

        if (!fd.get('name') || !fd.get('price') || !fd.get('stock')) {
            toast('Please fill in all required fields', 'error');
            return;
        }

        try {
            const res  = await fetch('admin.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                toast(document.getElementById('productEditId').value ? 'Product updated!' : 'Product added!', 'success');
                closeProductModal();
                loadInventory();
            } else {
                toast('Error: ' + data.message, 'error');
            }
        } catch (e) {
            toast('Network error', 'error');
        }
    }

    async function deleteProduct(id, name) {
        if (!confirm(`Delete "${name}"? This action cannot be undone.`)) return;
        const fd = new FormData();
        fd.append('action', 'delete_product');
        fd.append('id', id);
        try {
            const res  = await fetch('admin.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                toast('Product deleted', 'success');
                loadInventory();
            } else {
                toast('Error: ' + data.message, 'error');
            }
        } catch (e) { toast('Network error', 'error'); }
    }


    // ─── ORDER APPROVAL ────────────────────────────────────────────────────────────
    async function loadApproval() {
        const container = document.getElementById('approvalContent');
        container.innerHTML = '<div style="text-align:center;padding:32px;color:var(--muted);">Loading...</div>';

        try {
            const res    = await fetch('admin.php?action=get_pending_orders');
            const orders = await res.json();

            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        <p>No orders pending approval. All caught up!</p>
                    </div>`;
                return;
            }

            container.innerHTML = `<div class="approval-grid">${orders.map(o => {
                const itemsList = (o.items || []).map(i =>
                    `<div class="approval-order-item">
                        <span>${i.product_name} <span style="color:var(--muted)">x${i.quantity}</span></span>
                        <span>₱${parseFloat(i.subtotal || 0).toFixed(2)}</span>
                    </div>`
                ).join('');

                return `
                <div class="approval-card">
                    <div class="approval-card-body">
                        <div class="approval-card-name">Order #${o.id}</div>
                        <div class="approval-card-meta">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            ${o.customer_name || '—'}
                            &nbsp;&middot;&nbsp;
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            ${o.customer_address || '—'}
                            <br>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            ${(o.payment_method || '—').toUpperCase()}
                            &nbsp;&middot;&nbsp;
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            ${new Date(o.order_date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })}
                        </div>

                        <div class="approval-order-items">
                            ${itemsList || '<div style="color:var(--muted);font-size:12px;">No items</div>'}
                        </div>

                        <div class="approval-order-total">
                            Total: <strong>₱${parseFloat(o.total || 0).toFixed(2)}</strong>
                        </div>

                        <div class="approval-card-actions">
                            <button class="btn btn-success btn-sm" onclick="approveOrder('${o.id}')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Approve
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="rejectOrder('${o.id}')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Reject
                            </button>
                        </div>
                    </div>
                </div>`;
            }).join('')}</div>`;
        } catch (e) {
            container.innerHTML = `<div class="empty-state"><p>Could not load pending orders.</p></div>`;
        }
    }


    
async function approveOrder(id) {
    if (!confirm('Approve this order and move it to Processing?')) return;

    // Disable the button immediately to prevent double triggers
    const btn = document.querySelector(`button[onclick="approveOrder('${id}')"]`);
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Approving...';
    }

    const fd = new FormData();
    fd.append('action', 'approve_order');
    fd.append('id', id);

    try {
        const res  = await fetch('admin.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            toast('Order approved!', 'success');
            loadApproval(); // re-renders all cards fresh from DB
        } else {
            toast('Error: ' + (data.message || 'Unknown error'), 'error');
            if (btn) { btn.disabled = false; btn.textContent = 'Approve'; }
        }
    } catch (e) {
        toast('Network error: ' + e.message, 'error');
        if (btn) { btn.disabled = false; btn.textContent = 'Approve'; }
    }
}

async function rejectOrder(id) {
    const reason = prompt('Reason for rejection (optional):');
    if (reason === null) return;

    // Disable the button immediately
    const btn = document.querySelector(`button[onclick="rejectOrder('${id}')"]`);
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Rejecting...';
    }

    const fd = new FormData();
    fd.append('action', 'reject_order');
    fd.append('id', id);
    fd.append('reason', reason || 'No reason provided.');

    try {
        const res  = await fetch('admin.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            toast('Order rejected.', 'success');
            loadApproval();
        } else {
            toast('Error: ' + (data.message || 'Unknown error'), 'error');
            if (btn) { btn.disabled = false; btn.textContent = 'Reject'; }
        }
    } catch (e) {
        toast('Network error: ' + e.message, 'error');
        if (btn) { btn.disabled = false; btn.textContent = 'Reject'; }
    }
}


    // ─── ACTIVITY LOG ─────────────────────────────────────────────────────────────
            
    async function clearLogs() {
        if (!confirm('Clear all activity logs? This cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action', 'clear_logs');
        const res  = await fetch('admin.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) { toast('Logs cleared.', 'success'); loadActivity(); }
        else toast('Error: ' + data.message, 'error');
    }
    async function loadActivity() {
        const search = document.getElementById('activitySearch')?.value || '';
        const tbody  = document.getElementById('activityTableBody');

        try {
            const res  = await fetch(`admin.php?action=get_activity_logs&search=${encodeURIComponent(search)}`);
            const logs = await res.json();

            if (!logs || logs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state"><p>No activity logs found.</p></div></td></tr>`;
                return;
            }

            tbody.innerHTML = logs.map(log => {
            const actorBadge = log.actor === 'Admin'
        ?    `<span class="badge badge-admin">Admin</span>`
        :    `<span class="badge badge-user">${log.actor}</span>`;

    const typeMap = {
        'Order Status Change': 'badge-activity-order',
        'Stock Update':        'badge-activity-stock',
        'Login':               'badge-activity-login',
        'Product Update':      'badge-activity-product',
        'Product Add':         'badge-activity-product',
        'Product Approval':    'badge-activity-product',
        'Product Delete':      'badge-activity-product',
        'Order Approval':      'badge-activity-order',
        'Order Rejection':     'badge-activity-order',
        'Profile Update':      'badge-activity-profile',
    };
                const typeCls   = typeMap[log.action_type] || 'badge-processing';
                const typeBadge = `<span class="badge ${typeCls}">${log.action_type}</span>`;

                const timestamp = log.created_at
                    ? new Date(log.created_at).toLocaleString('en-PH', { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit' })
                    : '—';

                return `<tr class="log-row">
                    <td>${actorBadge}</td>
                    <td>${typeBadge}</td>
                    <td class="log-details">${log.details || '—'}</td>
                    <td class="log-time">${timestamp}</td>
                </tr>`;
            }).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--red);padding:24px;">Failed to load activity logs. Make sure the <strong>activity_logs</strong> table exists.</td></tr>`;
        }
    }

    // ─── LOGOUT ───────────────────────────────────────────────────────────────────
    function handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'login.php';
        }
    }

    // ─── CLOSE MODALS ON BACKDROP ─────────────────────────────────────────────────
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('open');
        });
    });

    const savedTab = sessionStorage.getItem('adminTab') || 'dashboard';
    switchTab(savedTab);
    </script>
    </body>
    </html>
