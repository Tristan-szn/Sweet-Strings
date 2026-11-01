<?php
session_name("CUSTOMER_SESSION");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {

    header('Location: login.php');
    exit;

}

include 'database.php';
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_order_received') {
    header('Content-Type: application/json');

    $order_id = mysqli_real_escape_string($conn, trim($_POST['order_id'] ?? ''));

    if (empty($order_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
        exit;
    }
    $check = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT status, user_id FROM orders WHERE id='$order_id'"
    ));

    if (!$check) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }
    if ($check['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }
    if ($check['status'] !== 'out-for-delivery') {
        echo json_encode(['success' => false, 'message' => 'Order is not out for delivery.']);
        exit;
    }

    mysqli_query($conn, "UPDATE orders SET status='order-received' WHERE id='$order_id' AND user_id='$user_id' AND status='out-for-delivery'");
    $affected = mysqli_affected_rows($conn);

    if ($affected > 0) {
        $detail = mysqli_real_escape_string($conn, "Customer confirmed receipt of Order #$order_id");
        mysqli_query($conn, "INSERT INTO activity_logs (actor, action_type, details) VALUES ('Customer', 'Order Status Change', '$detail')");
        echo json_encode(['success' => true, 'message' => 'Order marked as received!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order.']);
    }
    exit;
}
// Fetch user
$user_sql    = "SELECT * FROM users WHERE id='$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user        = mysqli_fetch_assoc($user_result);

// Fetch orders
if ($role === 'admin') {
    $orders_sql = "SELECT o.*, u.fullname as customer_name, u.address as customer_address
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id
                   ORDER BY o.order_date DESC";
} else {
    $orders_sql = "SELECT o.*, u.fullname as customer_name, u.address as customer_address
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id
                   WHERE o.user_id = '$user_id'
                   ORDER BY o.order_date DESC";
}
$orders_result = mysqli_query($conn, $orders_sql);
$orders        = [];
while ($row = mysqli_fetch_assoc($orders_result)) {
    // Fetch items for each order
    $oid        = $row['id'];
    $items_sql  = "SELECT * FROM order_items WHERE order_id='$oid'";
    $items_res  = mysqli_query($conn, $items_sql);
    $items      = [];
    while ($item = mysqli_fetch_assoc($items_res)) {
        $items[] = $item;
    }
    $row['items'] = $items;
    $orders[]     = $row;
}

// Get selected order from URL or first order
$selected_order_id = $_GET['order'] ?? ($orders[0]['id'] ?? null);
$selected_order    = null;
foreach ($orders as $o) {
    if ($o['id'] === $selected_order_id) {
        $selected_order = $o;
        break;
    }
}

// Status config
$status_map = [
    'pending'          => ['label' => 'Pending',          'class' => 'status-pending'],
    'processing'       => ['label' => 'Processing',       'class' => 'status-processing'],
    'preparing'        => ['label' => 'Preparing',        'class' => 'status-preparing'],
    'out-for-delivery' => ['label' => 'Out for Delivery', 'class' => 'status-shipped'],
    'order-received'   => ['label' => 'Order Received',   'class' => 'status-delivered'],
    'completed'        => ['label' => 'Completed',        'class' => 'status-completed'],
    'cancelled'        => ['label' => 'Cancelled',        'class' => 'status-cancelled'],
];

$tracking_steps = [
    ['key' => 'pending',          'label' => 'Pending'],
    ['key' => 'processing',       'label' => 'Processing'],
    ['key' => 'preparing',        'label' => 'Preparing'],
    ['key' => 'out-for-delivery', 'label' => 'Out for Delivery'],
    ['key' => 'order-received',   'label' => 'Order Received'],
    ['key' => 'completed',        'label' => 'Completed'],
];

function getStatusInfo($status, $status_map) {
    return $status_map[$status] ?? ['label' => $status, 'class' => 'status-processing'];
}

function getProgressPercent($status, $steps) {
    foreach ($steps as $i => $step) {
        if ($step['key'] === $status) {
            return round(($i + 1) / count($steps) * 100);
        }
    }
    return 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Sweets & Strings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="track-order.css">
</head>
<body class="dashboard-page">
    <nav class="nav" id="nav">
        <div class="nav-container">
            <a href="index.php" class="logo">Sweets & Strings</a>
            <ul class="nav-links">
                <li><a href="order.php">Order</a></li>
                <li><a href="transactions.php">My Orders</a></li>
                <li><a href="track-order.php">Track</a></li>
            </ul>
            <div class="user-menu">
                <a href="dashboard.php" class="user-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
                <a href="login.php" class="logout-btn" aria-label="Logout">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </a>
            </div>
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
        <div class="mobile-menu" id="mobileMenu">
            <ul>
                <li><a href="order.php">Order</a></li>
                <li><a href="transactions.php">My Orders</a></li>
                <li><a href="track-order.php">Track</a></li>
                <li><a href="dashboard.php">Profile</a></li>
            </ul>
            <a href="login.php" class="btn-secondary btn-full">Logout</a>
        </div>
    </nav>

    <section class="dashboard-section">
        <div class="dashboard-container">
            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <div class="profile-preview">
                    <div class="profile-photo-wrapper">
<div class="profile-photo">
    <?php if (!empty($user['photo'])): ?>
        <img src="<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
    <?php else: ?>
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
    <?php endif; ?>
</div>
                    </div>
                    <h3 class="profile-name"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>

                <nav class="sidebar-nav">
                    <a href="dashboard.php" class="sidebar-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Profile
                    </a>
                    <a href="order.php" class="sidebar-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
                        </svg>
                        Place Order
                    </a>
                    <a href="transactions.php" class="sidebar-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                        My Orders
                    </a>
                    <a href="track-order.php" class="sidebar-link active">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="3" width="15" height="13"/>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/>
                            <circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                        Track Order
                    </a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="dashboard-main">
                <div class="dashboard-header">
                    <h1>Track Order</h1>
                    <p>Monitor your delivery status</p>
                </div>

                <!-- Order Selector -->
                <div class="dashboard-card">
                    <h2>Select Order</h2>
                    <div class="order-selector">
                        <?php if (empty($orders)): ?>
                            <p style="color:var(--lavender-500);text-align:center;padding:24px;">
                                No orders to track yet.
                                <a href="order.php" style="color:var(--pink-accent);">Place an order</a> to get started.
                            </p>
                        <?php else: ?>
                            <?php foreach ($orders as $o):
                                $s = getStatusInfo($o['status'], $status_map);
                                $date = date('M d', strtotime($o['order_date']));
                                $active = ($o['id'] === $selected_order_id) ? 'active' : '';
                            ?>
                                <a href="track-order.php?order=<?php echo urlencode($o['id']); ?>"
                                   class="order-select-btn <?php echo $active; ?>">
                                    <span class="os-id"><?php echo htmlspecialchars($o['id']); ?></span>
                                    <span class="os-date"><?php echo $date; ?></span>
                                    <span class="status-badge <?php echo $s['class']; ?>"><?php echo $s['label']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tracking Display -->
                <?php if ($selected_order): ?>
                <?php
                    $s            = getStatusInfo($selected_order['status'], $status_map);
                    $item_count   = array_sum(array_column($selected_order['items'], 'quantity'));
                    $delivery_fmt = date('F d, Y', strtotime($selected_order['delivery_date']));
                    $progress     = ($selected_order['status'] === 'cancelled') ? 0 : getProgressPercent($selected_order['status'], $tracking_steps);
                    $status_index = 0;
                    foreach ($tracking_steps as $i => $step) {
                        if ($step['key'] === $selected_order['status']) {
                            $status_index = $i;
                            break;
                        }
                    }
                ?>
                <div class="dashboard-card tracking-display">
                    <div class="tracking-header">
                        <div>
                            <h2><?php echo htmlspecialchars($selected_order['id']); ?></h2>
                            <p><span class="status-badge <?php echo $s['class']; ?>"><?php echo $s['label']; ?></span></p>
                        </div>
                        <div class="delivery-estimate">
                            <span class="label">Estimated Delivery</span>
                            <span class="date"><?php echo $delivery_fmt; ?></span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="tracking-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="progress-steps">
                            <?php if ($selected_order['status'] === 'cancelled'): ?>
                                <div class="step">
                                    <div class="step-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    </div>
                                    <span class="step-label">Order Confirmed</span>
                                </div>
                                <div class="step">
                                    <div class="step-icon" style="color:#dc2626;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                    </div>
                                    <span class="step-label" style="color:#dc2626;">Order Cancelled</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tracking_steps as $i => $step):
                                    $is_active  = $i <= $status_index;
                                    $is_current = $i === $status_index;
                                    $class      = ($is_active ? 'active' : '') . ' ' . ($is_current ? 'current' : '');
                                ?>
                                    <div class="step <?php echo trim($class); ?>">
                                        <div class="step-icon">
                                            <?php if ($step['key'] === 'pending'): ?>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                                </svg>
                                            <?php elseif ($step['key'] === 'processing'): ?>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            <?php elseif ($step['key'] === 'preparing'): ?>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            <?php elseif ($step['key'] === 'out-for-delivery'): ?>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                            <?php elseif ($step['key'] === 'order-received'): ?>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                            <?php elseif ($step['key'] === 'completed'): ?>
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                                <polyline points="22 4 12 14.01 9 11.01"/>
                                                </svg>
                                                <?php endif; ?>
                                        </div>
                                        <span class="step-label"><?php echo $step['label']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="delivery-info-card">
                        <h3>Delivery Details</h3>
                        <div class="delivery-info-grid">
                            <div class="info-item">
                                <span class="label">Order Total</span>
                                <span class="value">₱<?php echo number_format($selected_order['total'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Items</span>
                                <span class="value"><?php echo $item_count; ?> item(s)</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Delivery Address</span>
                                <span class="value"><?php echo htmlspecialchars($selected_order['customer_address'] ?? $user['address']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Payment Method</span>
                                <span class="value"><?php echo strtoupper($selected_order['payment_method']); ?></span>
                            </div>
                            <?php if ($role === 'admin'): ?>
                            <div class="info-item">
                                <span class="label">Customer</span>
                                <span class="value"><?php echo htmlspecialchars($selected_order['customer_name']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="delivery-info-card" style="margin-top:1rem;">
                        <h3>Order Items</h3>
                        <?php foreach ($selected_order['items'] as $item): ?>
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--lavender-100);font-size:0.875rem;">
                                <span><?php echo htmlspecialchars($item['product_name']); ?> <span style="color:var(--lavender-400);">x<?php echo $item['quantity']; ?></span></span>
                                <span style="font-family:'Space Mono',monospace;">₱<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                        <!-- Order Received Button — customers only, out-for-delivery only -->
                      <?php if ($role === 'customer' && $selected_order['status'] === 'out-for-delivery'): ?>
                   <div class="order-received-section" style="margin-top:1.5rem; text-align:center;">
                   <button type="button" class="btn-primary" id="orderReceivedBtn" onclick="confirmOrderReceived('<?php echo htmlspecialchars($selected_order['id']); ?>')">
                   <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                   <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                          </svg>
                         I Received My Order
                         </button>
                         <p style="font-size:12px;color:var(--lavender-500);margin-top:6px;">
                          Click this once you have received your order.
                         </p>
                        </div>
              <?php endif; ?>

                    <!-- Cancel Button — customers only, processing only -->
                    <?php if ($role === 'customer' && in_array($selected_order['status'], ['pending', 'processing'])): ?>
                    <div class="cancel-order-section" style="margin-top:1.5rem;">
                        <form method="POST" action="cancel_order_form.php">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($selected_order['id']); ?>">
                            <button type="submit" class="btn-danger-outline">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                                Cancel Order
                            </button>
                            <p class="cancel-hint">You can only cancel orders that are Pending or Processing.</p>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </section>

    <div class="toast" id="toast"></div>

<script>
// ─── MOBILE MENU ─────────────────────────────────────────────────────────────
const menuToggle = document.getElementById('menuToggle');
const mobileMenu = document.getElementById('mobileMenu');
if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', function() {
        this.classList.toggle('active');
        mobileMenu.classList.toggle('active');
    });
}

// ─── TOAST ───────────────────────────────────────────────────────────────────
function toast(msg, type = '') {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.className = 'toast show ' + type;
    clearTimeout(el._t);
    el._t = setTimeout(() => el.className = 'toast', 3000);
}

// ─── CONFIRM ORDER RECEIVED ───────────────────────────────────────────────────
function confirmOrderReceived(orderId) {
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed; inset: 0; background: rgba(0,0,0,0.6);
        z-index: 9999; display: flex; align-items: center; justify-content: center;
    `;

    overlay.innerHTML = `
        <div style="
            background: #fff; border-radius: 16px; padding: 28px;
            max-width: 400px; width: 90%; text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        ">
            <div style="
                width: 56px; height: 56px; border-radius: 50%;
                background: rgba(34,197,94,0.1); display: flex;
                align-items: center; justify-content: center; margin: 0 auto 16px;
            ">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;color:#1a1a2e;">
                Confirm Order Received?
            </h3>
            <p style="font-size:14px;color:#64748b;margin-bottom:24px;line-height:1.5;">
                By clicking confirm, you are verifying that you have received your order. This cannot be undone.
            </p>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button id="receivedNo" style="
                    padding:10px 24px;border-radius:8px;font-size:14px;
                    font-weight:600;cursor:pointer;border:1px solid #e2e8f0;
                    background:#f8fafc;color:#64748b;
                ">Cancel</button>
                <button id="receivedYes" style="
                    padding:10px 24px;border-radius:8px;font-size:14px;
                    font-weight:600;cursor:pointer;border:none;
                    background:#22c55e;color:#fff;
                ">Confirm Received</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    overlay.querySelector('#receivedNo').addEventListener('click', () => {
        document.body.removeChild(overlay);
    });

    overlay.querySelector('#receivedYes').addEventListener('click', async () => {
        document.body.removeChild(overlay);

        const btn = document.getElementById('orderReceivedBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Updating...'; }

        const fd = new FormData();
        fd.append('action', 'mark_order_received');
        fd.append('order_id', orderId);

        try {
            const res  = await fetch('track-order.php', { method: 'POST', body: fd });
            const text = await res.text();
            console.log('Raw response:', text);
            const data = JSON.parse(text);

            if (data.success) {
                toast('Order marked as received! Thank you.', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                toast('Error: ' + (data.message || 'Unknown error'), 'error');
                if (btn) { btn.disabled = false; btn.textContent = 'I Received My Order'; }
            }
        } catch (e) {
            toast('Network error: ' + e.message, 'error');
            if (btn) { btn.disabled = false; btn.textContent = 'I Received My Order'; }
        }
    });

    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) document.body.removeChild(overlay);
    });
}

// ─── CANCEL ORDER ─────────────────────────────────────────────────────────────
document.querySelectorAll('.cancel-order-section form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            z-index: 9999; display: flex; align-items: center; justify-content: center;
        `;

        overlay.innerHTML = `
            <div style="
                background: #fff; border-radius: 16px; padding: 28px;
                max-width: 400px; width: 90%; text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            ">
                <div style="
                    width: 56px; height: 56px; border-radius: 50%;
                    background: rgba(239,68,68,0.1); display: flex;
                    align-items: center; justify-content: center; margin: 0 auto 16px;
                ">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <h3 style="font-size:18px;font-weight:700;margin-bottom:8px;color:#1a1a2e;">
                    Cancel Order?
                </h3>
                <p style="font-size:14px;color:#64748b;margin-bottom:24px;line-height:1.5;">
                    Are you sure you want to cancel this order? This action cannot be undone.
                </p>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button id="cancelNo" style="
                        padding:10px 24px;border-radius:8px;font-size:14px;
                        font-weight:600;cursor:pointer;border:1px solid #e2e8f0;
                        background:#f8fafc;color:#64748b;
                    ">Keep Order</button>
                    <button id="cancelYes" style="
                        padding:10px 24px;border-radius:8px;font-size:14px;
                        font-weight:600;cursor:pointer;border:none;
                        background:#ef4444;color:#fff;
                    ">Yes, Cancel</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        overlay.querySelector('#cancelNo').addEventListener('click', () => {
            document.body.removeChild(overlay);
            toast('Order cancellation aborted.');
        });

        overlay.querySelector('#cancelYes').addEventListener('click', () => {
            document.body.removeChild(overlay);
            toast('Cancelling your order...');
            setTimeout(() => form.submit(), 800);
        });

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) document.body.removeChild(overlay);
        });
    });
});
</script>
</body>
</html>