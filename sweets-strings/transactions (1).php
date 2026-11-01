<?php
session_name("CUSTOMER_SESSION");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {

    header('Location: login.php');
    exit;

}

include 'database.php';

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE id='$user_id'";
$result = mysqli_query($conn, $sql);

$user = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Sweets & Strings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="transactions.css">
</head>
<body class="dashboard-page">
    <!-- Navigation -->
    <nav class="nav" id="nav">
        <div class="nav-container">
            <a href="index.php" class="logo">Sweets & Strings</a>
            <ul class="nav-links">
                <li><a href="order.php">Order</a></li>
                <li><a href="transactions.php">My Orders</a></li>
                <li><a href="track-order.php">Track</a></li>
            </ul>
            <div class="user-menu">
                <a href="dashboard.php" class="user-avatar" id="userAvatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
                <button class="logout-btn" id="logoutBtn" aria-label="Logout">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </button>
            </div>
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <div class="mobile-menu" id="mobileMenu">
            <ul>
                <li><a href="order.php">Order</a></li>
                <li><a href="transactions.php">My Orders</a></li>
                <li><a href="track-order.php">Track</a></li>
                <li><a href="dashboard.php">Profile</a></li>
            </ul>
            <button class="btn-secondary btn-full" id="mobileLogout">Logout</button>
        </div>
    </nav>

    <!-- Transactions Section -->
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
                    <!-- PHP fills sidebar directly -->
                    <h3 class="profile-name" id="sidebarName">
                        <?php echo htmlspecialchars($user['fullname']); ?>
                    </h3>
                    <p class="profile-email" id="sidebarEmail">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
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
                    <a href="transactions.php" class="sidebar-link active">
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
                    <a href="track-order.php" class="sidebar-link">
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
                    <h1>My Orders</h1>
                    <p>View and track your order history</p>
                </div>

                <div class="dashboard-card">
                    <div class="orders-list" id="ordersList">
                        <!-- Orders loaded by JavaScript -->
                    </div>
                </div>
            </main>
        </div>
    </section>

    <!-- Order Details Modal -->
    <div class="modal" id="orderDetailsModal">
        <div class="modal-content modal-large">
            <button class="modal-close" id="modalClose" aria-label="Close">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div id="modalContent"></div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <span class="toast-message"></span>
    </div>

    <!-- PHP Bridge Script -->
    <script>
        localStorage.setItem('sweets_session', JSON.stringify({
            id:        "<?php echo $user['id']; ?>",
            name:      "<?php echo addslashes($user['fullname']); ?>",
            email:     "<?php echo addslashes($user['email']); ?>",
            role:      "<?php echo $user['role']; ?>",
            isLoggedIn: true
        }));
    </script>

    <script>
    /**
     * Sweets & Strings - Transactions / Order History
     * Full PHP Database Version
     */
    (function() {
        'use strict';

        const session = JSON.parse(localStorage.getItem('sweets_session') || '{}');
        if (!session.isLoggedIn) {
            window.location.href = 'login.php';
            return;
        }

        const userRole = session.role || 'customer';

        const STATUS_MAP = {
            'processing':       { label: 'Processing',       class: 'status-processing' },
            'preparing':        { label: 'Preparing',        class: 'status-preparing' },
            'out-for-delivery': { label: 'Out for Delivery', class: 'status-shipped' },
            'order-received':   { label: 'Order Received',   class: 'status-delivered' },
            'cancelled':        { label: 'Cancelled',        class: 'status-cancelled' }
        };

        function getStatusInfo(status) {
            return STATUS_MAP[status] || { label: status, class: 'status-processing' };
        }

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            if (!toast) return;
            toast.textContent = message;
            toast.className = 'toast ' + (type || '');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                mobileMenu.classList.toggle('active');
            });
        }

        function handleLogout() {
            localStorage.removeItem('sweets_session');
            sessionStorage.removeItem('sweets_session');
            window.location.href = 'login.php';
        }
        const logoutBtn = document.getElementById('logoutBtn');
        const mobileLogout = document.getElementById('mobileLogout');
        if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);
        if (mobileLogout) mobileLogout.addEventListener('click', handleLogout);

        // ---- Load Orders from PHP ----
        function loadOrders() {
            const container = document.getElementById('ordersList');
            const pageTitle = document.querySelector('.dashboard-header h1');
            if (!container) return;

            container.innerHTML = '<p style="text-align:center;padding:24px;">Loading orders...</p>';

            fetch('get_orders.php')
                .then(res => res.json())
                .then(orders => {
                    if (pageTitle) {
                        pageTitle.textContent = userRole === 'admin' ? 'All Orders' : 'My Orders';
                    }

                    if (!orders || orders.length === 0) {
                        container.innerHTML = `
                            <div class="empty-orders" style="text-align:center;padding:48px 24px;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:16px;">
                                    <circle cx="9" cy="21" r="1"/>
                                    <circle cx="20" cy="21" r="1"/>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
                                </svg>
                                <h3 style="margin-bottom:8px;">No orders yet</h3>
                                <p style="margin-bottom:16px;">${userRole === 'admin' ? 'Orders will appear here once customers place them.' : "You haven't placed any orders yet."}</p>
                                ${userRole !== 'admin' ? '<a href="order.php" class="btn-primary">Place Your First Order</a>' : ''}
                            </div>
                        `;
                        return;
                    }

                    container.innerHTML = orders.map(order => {
                        const items     = order.items || [];
                        const date      = new Date(order.order_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        const sInfo     = getStatusInfo(order.status);
                        const isCancellable = order.status === 'processing';
                        const payment   = order.payment_method ? order.payment_method.toUpperCase() : 'CASH';

                        return `
                            <div class="order-card-item" data-order-id="${order.id}" style="background:white;border-radius:1rem;padding:1.25rem;margin-bottom:1rem;box-shadow:var(--shadow-card);">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                                    <div>
                                        <span style="font-weight:700;color:var(--lavender-900);">${order.id}</span>
                                        <span style="font-size:0.8rem;color:var(--lavender-500);margin-left:8px;">${date}</span>
                                    </div>
                                    <span class="status-badge ${sInfo.class}">${sInfo.label}</span>
                                </div>
                                ${userRole === 'admin' ? `
                                    <div style="font-size:0.85rem;color:var(--purple-700);margin-bottom:8px;padding:6px 10px;background:var(--purple-50);border-radius:6px;">
                                        Customer: <strong>${order.customer_name || 'Unknown'}</strong> (${order.customer_email || ''})
                                    </div>` : ''}
                                <div style="margin-bottom:0.75rem;">
                                    ${items.slice(0, 3).map(item => `
                                        <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:0.875rem;color:var(--lavender-700);">
                                            <span>${item.product_name} <span style="color:var(--lavender-400);">x${item.quantity}</span></span>
                                            <span style="font-family:'Space Mono',monospace;">₱${parseFloat(item.subtotal || 0).toFixed(2)}</span>
                                        </div>
                                    `).join('')}
                                    ${items.length > 3 ? `<div style="font-size:0.8rem;color:var(--lavender-400);padding:4px 0;">+${items.length - 3} more items</div>` : ''}
                                </div>
                                <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--lavender-100);padding-top:0.75rem;">
                                    <div>
                                        <span style="font-family:'Space Mono',monospace;font-weight:700;color:var(--purple-700);font-size:1.1rem;">₱${parseFloat(order.total || 0).toFixed(2)}</span>
                                        <span style="font-size:0.75rem;color:var(--lavender-500);margin-left:8px;">${payment}</span>
                                    </div>
                                    <div style="display:flex;gap:8px;align-items:center;">
                                        ${isCancellable ? `<button class="btn-text danger" onclick="cancelOrder('${order.id}')">Cancel</button>` : ''}
                                        <button class="btn-text" onclick="trackOrder('${order.id}')">Track</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                })
                .catch(err => {
                    console.error('Error loading orders:', err);
                    container.innerHTML = '<p style="text-align:center;color:red;padding:24px;">Failed to load orders. Please try again.</p>';
                });
        }

        window.cancelOrder = function(orderId) {
            if (!confirm('Are you sure you want to cancel this order?')) return;

            fetch('cancel_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Order cancelled successfully.', 'success');
                    loadOrders();
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(err => {
                showToast('Something went wrong.', 'error');
                console.error(err);
            });
        };

        window.trackOrder = function(orderId) {
            window.location.href = 'track-order.php?order=' + orderId;
        };

        // ---- Init ----
        loadOrders();

        const urlParams = new URLSearchParams(window.location.search);
        const orderParam = urlParams.get('order');
        if (orderParam) {
            setTimeout(() => {
                const el = document.querySelector(`[data-order-id="${orderParam}"]`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 500);
        }

        console.log('Transactions page ready | Role:', userRole);
    })();
    </script>
</body>
</html>