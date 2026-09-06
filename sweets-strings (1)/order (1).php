<?php
session_name("CUSTOMER_SESSION");
session_start();
if (!isset($_SESSION['user_id'])) {
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
    <title>Place Order - Sweets & Strings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="order.css">
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

    <!-- Order Section -->
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
                    <a href="order.php" class="sidebar-link active">
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
                    <h1>Place Order</h1>
                    <p>Select your favorite sweets and we'll deliver them fresh</p>
                </div>

                <!-- Products Grid -->
                <div class="dashboard-card">
                    <h2>Select Products</h2>
                    <div class="products-grid" id="productsGrid">
                        <!-- Products will be loaded by JavaScript -->
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="dashboard-card order-summary" id="orderSummary">
                    <h2>Order Summary</h2>
                    <div class="summary-content">
                        <div class="summary-items" id="summaryItems">
                            <p class="empty-cart">No items selected</p>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span class="total-amount" id="totalAmount">₱0.00</span>
                        </div>

                        <!-- Payment Method -->
                        <div class="payment-method">
                            <h3>Payment Method</h3>
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="paymentMethod" value="cash" checked>
                                    <span class="payment-card">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                                            <path d="M2 10h20"/>
                                        </svg>
                                        Cash on Delivery
                                    </span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="paymentMethod" value="gcash">
                                    <span class="payment-card">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                                        </svg>
                                        GCash
                                    </span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="paymentMethod" value="card">
                                    <span class="payment-card">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                            <line x1="1" y1="10" x2="23" y2="10"/>
                                        </svg>
                                        Credit/Debit Card
                                    </span>
                                </label>
                            </div>
                        </div>

                        <button class="btn-primary btn-full" id="placeOrderBtn" disabled>
                            Place Order
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </section>

    <!-- Order Confirmation Modal -->
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-icon success">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <h2>Order Placed!</h2>
            <p>Your order <strong id="orderNumber"></strong> has been placed successfully.</p>
            <div class="order-details" id="orderDetails"></div>
            <p class="modal-hint">Estimated delivery: <strong id="deliveryEstimate"></strong></p>
            <div class="modal-actions">
                <a href="transactions.php" class="btn-primary">View My Orders</a>
                <a href="track-order.php" class="btn-secondary">Track Order</a>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <span class="toast-message"></span>
    </div>
<!-- Add this BEFORE your existing script tag -->
<script>
    localStorage.setItem('sweets_session', JSON.stringify({
        id:        "<?php echo $user['id']; ?>",
        name:      "<?php echo addslashes($user['fullname']); ?>",
        email:     "<?php echo addslashes($user['email']); ?>",
        role:      "<?php echo $user['role']; ?>",
        isLoggedIn: true
    }));
</script>

<script>/**
 * Sweets & Strings - Order Page JavaScript
 ...


   <script>/**
 * Sweets & Strings - Order Page JavaScript (FULL PHP Backend)
 * All functions preserved: mobile nav, toasts, profile, modals, cart
 */
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

   (function() {
    'use strict';
    // Session check
    const session = JSON.parse(localStorage.getItem('sweets_session') || sessionStorage.getItem('sweets_session') || '{}');
    if (!session.isLoggedIn) {
        window.location.href = 'login.php';
        return;
    }

    function showToast(message, type) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast ' + (type || '');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // Mobile menu (PRESERVED)
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });
    }

    // User data (PRESERVED - localStorage for profile display)
// ✅ FETCHES FROM DATABASE
function loadUserData() {
    fetch('get_user.php')
        .then(res => res.json())
        .then(user => {
            if (!user || user.error) return;
            const sName  = document.getElementById('sidebarName');
            const sEmail = document.getElementById('sidebarEmail');
            if (sName)  sName.textContent  = user.fullname;
            if (sEmail) sEmail.textContent = user.email;
        })
        .catch(err => console.error('Error loading user:', err));
}

    // PHP Backend variables
    let products = [];
    let cart = {};

    // 🔥 PHP: Load products from database
    async function loadProducts() {
        try {
            const response = await fetch('get_products.php');
            if (!response.ok) throw new Error('Failed to fetch products');
            products = await response.json();
            
            const grid = document.getElementById('productsGrid');
            if (!grid) return;
            
            grid.innerHTML = products.map(product => `
                <div class="product-card">
                    <div class="product-image">
                        <img src="${IMAGE_SLOTS[products.indexOf(product) + 1] || product.image || 'images/products/product_macaron_small.jpg'}" alt="${product.name}" loading="lazy" onerror="this.src='images/products/product_macaron_small.jpg'">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">${product.name}</h3>
                        <p class="product-desc">${product.description || ''}</p>
                        <div class="product-footer">
                            <span class="product-price">₱${parseFloat(product.price || 0).toFixed(2)}</span>
                            <div class="quantity-control">
                                <button class="qty-btn minus" data-id="${product.id}">-</button>
                                <span class="qty-value" id="qty-${product.id}">0</span>
                                <button class="qty-btn plus" data-id="${product.id}">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');

            attachQuantityListeners();
            console.log(`✅ ${products.length} products loaded from PHP`);
        } catch (error) {
            showToast('Failed to load products. Using demo mode.', 'error');
            console.error('Products load error:', error);
            // Fallback to empty products
            products = [];
        }
    }

    function attachQuantityListeners() {
        document.querySelectorAll('.qty-btn.plus').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                cart[id] = (cart[id] || 0) + 1;
                updateQuantityDisplay(id);
                updateSummary();
            });
        });

        document.querySelectorAll('.qty-btn.minus').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                if (cart[id] && cart[id] > 0) {
                    cart[id]--;
                    if (cart[id] === 0) delete cart[id];
                    updateQuantityDisplay(id);
                    updateSummary();
                }
            });
        });
    }

    function updateQuantityDisplay(productId) {
        const qtyEl = document.getElementById('qty-' + productId);
        if (qtyEl) qtyEl.textContent = cart[productId] || 0;
    }

    function updateSummary() {
        const summaryItems = document.getElementById('summaryItems');
        const totalAmount = document.getElementById('totalAmount');
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        const cartItems = Object.keys(cart).filter(id => cart[id] > 0);

        if (cartItems.length === 0) {
            summaryItems.innerHTML = '<p class="empty-cart">No items selected</p>';
            totalAmount.textContent = '₱0.00';
            if (placeOrderBtn) placeOrderBtn.disabled = true;
            return;
        }

        let total = 0;
        summaryItems.innerHTML = cartItems.map(id => {
            const product = products.find(p => p.id == id);
            if (!product) return '';
            const qty = cart[id];
            const subtotal = parseFloat(product.price || 0) * qty;
            total += subtotal;
            return `
               <div class="summary-item">
                <div class="item-info">
                    <span class="item-name">${product.name}</span>
                    <span class="item-qty">x${qty}</span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="item-price">₱${subtotal.toFixed(2)}</span>
                    <button onclick="removeFromCart('${id}')" style="
                        background:none;
                        border:none;
                        cursor:pointer;
                        color:#ef4444;
                        font-size:16px;
                        font-weight:700;
                        line-height:1;
                        padding:2px 6px;
                        border-radius:4px;
                        transition:background .15s;
                    " onmouseover="this.style.background='rgba(239,68,68,.1)'" 
                    onmouseout="this.style.background='none'"
                    title="Remove item">✕</button>
                </div>
            </div>
        `;
    }).join('');


        totalAmount.textContent = '₱' + total.toFixed(2);
        if (placeOrderBtn) placeOrderBtn.disabled = false;
            function removeFromCart(id) {
    delete cart[id];
    updateQuantityDisplay(id);
    updateSummary();
}
window.removeFromCart = removeFromCart;
    }

    // 🔥 PHP: Place order to database
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    if (placeOrderBtn) {
        placeOrderBtn.addEventListener('click', async function() {
            const cartItems = Object.keys(cart).filter(id => cart[id] > 0);
            if (cartItems.length === 0) { 
                showToast('Please select at least one item', 'error'); 
                return; 
            }

            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value || 'cash';

            let total = 0;
            const items = cartItems.map(id => {
                const product = products.find(p => p.id == id);
                if (!product) return null;
                const qty = cart[id];
                const subtotal = parseFloat(product.price || 0) * qty;
                total += subtotal;
                return { 
                    productId: parseInt(product.id), 
                    name: product.name, 
                    price: parseFloat(product.price), 
                    quantity: qty, 
                    subtotal: subtotal 
                };
            }).filter(item => item !== null);

            if (items.length === 0) {
                showToast('Invalid cart items', 'error');
                return;
            }

            // Show loading
            placeOrderBtn.disabled = true;
            placeOrderBtn.textContent = 'Processing...';

            try {
                const response = await fetch('place_order.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-User-ID': session.id
                    },
                    body: JSON.stringify({
                        items: items,
                        total: total,
                        paymentMethod: paymentMethod,
                        userId: session.id
                    })
                });

                const data = await response.json();
                
                if (!data.success) {
                    showToast('Order failed: ' + (data.message || 'Unknown error'), 'error');
                    return;
                }

                // Success - clear cart
                cart = {};
                products.forEach(p => updateQuantityDisplay(p.id));
                updateSummary();

                // Show your existing modal (PRESERVED)
                const orderModal = document.getElementById('orderModal');
                const orderNumber = document.getElementById('orderNumber');
                const orderDetails = document.getElementById('orderDetails');
                const deliveryEstimate = document.getElementById('deliveryEstimate');

                if (orderNumber) orderNumber.textContent = data.order_id;
                if (orderDetails) {
                    orderDetails.innerHTML = `
                        <div class="detail-row"><span>Total:</span><strong>₱${total.toFixed(2)}</strong></div>
                        <div class="detail-row"><span>Items:</span><strong>${items.reduce((sum, i) => sum + i.quantity, 0)} items</strong></div>
                        <div class="detail-row"><span>Payment:</span><strong>${paymentMethod.toUpperCase()}</strong></div>
                    `;
                }
                if (deliveryEstimate) {
                    const d = new Date(data.delivery_date);
                    deliveryEstimate.textContent = d.toLocaleDateString('en-US', { 
                        month: 'long', day: 'numeric', year: 'numeric' 
                    });
                }
                if (orderModal) orderModal.classList.add('active');

                showToast('Order placed successfully! #' + data.order_id, 'success');
                
            } catch (error) {
                showToast('Network error. Please try again.', 'error');
                console.error('Order error:', error);
            } finally {
                // Reset button
                placeOrderBtn.disabled = false;
                placeOrderBtn.textContent = 'Place Order';
            }
        });
    }

    // Modal close functionality (NEW - nice to have)
    const orderModal = document.getElementById('orderModal');
    if (orderModal) {
        orderModal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    }

    // Logout (PRESERVED)
    function handleLogout() {
        localStorage.removeItem('sweets_session');
        sessionStorage.removeItem('sweets_session');
        window.location.href = 'login.php';
    }
    const logoutBtn = document.getElementById('logoutBtn');
    const mobileLogout = document.getElementById('mobileLogout');
    if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);
    if (mobileLogout) mobileLogout.addEventListener('click', handleLogout);

    // Initialize (PRESERVED order)
    loadProducts();

    console.log('✅ Full PHP Order page ready - All functions preserved!');
})();
</script>
</body>
</html>
