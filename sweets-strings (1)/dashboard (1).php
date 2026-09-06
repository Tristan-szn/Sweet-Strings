<?php

session_name("CUSTOMER_SESSION");
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {

    header('Location: login.php');
    exit;

}

// Fetch user from database
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
    <title>Dashboard - Sweets & Strings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="dashboard-page">
    <!-- Navigation -->
    <nav class="nav" id="nav">
        <div class="nav-container">
            <a href="index.html" class="logo">Sweets & Strings</a>
            <ul class="nav-links" id="navLinks">
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
            <ul id="mobileNavLinks">
                <li><a href="order.php">Order</a></li>
                <li><a href="transactions.php">My Orders</a></li>
                <li><a href="track-order.php">Track</a></li>
                <li><a href="dashboard.php">Profile</a></li>
            </ul>
            <button class="btn-secondary btn-full" id="mobileLogout">Logout</button>
        </div>
    </nav>

    <!-- Dashboard Section -->
    <section class="dashboard-section">
        <div class="dashboard-container">
            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <div class="profile-preview">
                    <div class="profile-photo-wrapper">
<div class="profile-photo" id="profilePhoto">
    <?php if (!empty($user['photo'])): ?>
        <img src="<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile">
    <?php else: ?>
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
    <?php endif; ?>
</div>
                        <button class="photo-upload-btn" id="photoUploadBtn" aria-label="Upload photo">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                        </button>
                        <input type="file" id="photoInput" accept="image/*" hidden>
                    </div>
  <!-- Sidebar profile -->
<h3 class="profile-name" id="sidebarName">
    <?php echo htmlspecialchars($user['fullname']); ?>
</h3>
<p class="profile-email" id="sidebarEmail">
    <?php echo htmlspecialchars($user['email']); ?>
</p>
<span class="role-badge" id="roleBadge">
    
    <?php
        $roleLabels = ['admin' => 'Administrator', 'employee' => 'Employee', 'customer' => 'Customer'];
        echo $roleLabels[$user['role']] ?? 'Customer';
    ?>
</span>
                </div>

                <!-- Tab Navigation - built dynamically by JS based on role -->
                <nav class="sidebar-nav" id="sidebarNav"></nav>
            </aside>

            <!-- Main Content -->
            <main class="dashboard-main" id="dashboardMain">
                <div class="dashboard-header">
                    <h1 id="pageTitle">Dashboard</h1>
                    <p id="pageSubtitle">Welcome back</p>
                </div>

                <!-- ============ PROFILE TAB ============ -->
                <div class="tab-content" id="tab-profile">
                    <div class="dashboard-card">
                        <h2>Personal Information</h2>
                        <form class="profile-form" id="profileForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editName">Full Name</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                        <input type="text" id="editName" name="name" 
    value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="editEmail">Email Address</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                            <polyline points="22,6 12,13 2,6"/>
                                        </svg>
                                        <input type="email" id="editEmail" name="email" 
    value="<?php echo htmlspecialchars($user['email']); ?>" disabled required>
                                    </div>
                                    <span class="form-hint">Email cannot be changed</span>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editContact">Contact Number</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                                        </svg>
                                        <input type="tel" id="editContact" name="contact" 
    value="<?php echo htmlspecialchars($user['contact']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="editAddress">Delivery Address</label>
                                <div class="input-wrapper textarea-wrapper">
                                    <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <textarea id="editAddress" name="address" rows="3" required>
    <?php echo htmlspecialchars($user['address']); ?>

                                    </textarea>
                                </div>  
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                                        <polyline points="17 21 17 13 7 13 7 21"/>
                                        <polyline points="7 3 7 8 15 8"/>
                                    </svg>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="dashboard-card">
                        <h2>Account Information</h2>
                        <div class="account-info">
                            <div class="info-row">
    <span class="info-label">Member Since</span>
    <span class="info-value" id="memberSince">
        <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
    </span>
</div>
<div class="info-row">
    <span class="info-label">Account ID</span>
    <span class="info-value" id="accountId">
        <?php echo $user['id']; ?>
    </span>
</div>
<div class="info-row">
    <span class="info-label">Account Type</span>
    <span class="info-value" id="accountType">
        <?php
            $roleLabels = ['admin' => 'Administrator', 'employee' => 'Employee', 'customer' => 'Customer'];
            echo $roleLabels[$user['role']] ?? 'Customer';
        ?>
    </span>
</div>
                        </div>
                    </div>
                </div>

                <!-- ============ ADMIN: ACCOUNTS TAB ============ -->
                <div class="tab-content hidden" id="tab-accounts">
                    <div class="dashboard-card">
                        <div class="section-header-row">
                            <div>
                                <h2>Account Management</h2>
                                <p class="section-desc">View, search, and manage all registered accounts</p>
                            </div>
                        </div>
                        <div class="search-filter-bar">
                            <div class="search-box">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <input type="text" id="accountSearch" placeholder="Search by name, email, or contact...">
                            </div>
                            <select id="accountFilter" class="filter-select">
                                <option value="all">All Account Types</option>
                                <option value="customer">Customer</option>
                                <option value="employee">Employee</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="users-table-wrapper">
                            <table class="data-table" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Address</th>
                                        <th>Account Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody"></tbody>
                            </table>
                            <p class="empty-state hidden" id="accountsEmpty">No accounts found matching your search.</p>
                        </div>
                    </div>
                </div>

                <!-- ============ ADMIN: SALES REPORTS TAB ============ -->
                <div class="tab-content hidden" id="tab-sales">
                    <div class="dashboard-card">
                        <h2>Sales Reports</h2>
                        <p class="section-desc">View generated sales from successfully delivered orders</p>
                        <div class="stats-grid" id="salesStats">
                            <div class="stat-card">
                                <span class="stat-label">Total Sales Revenue</span>
                                <span class="stat-value" id="totalSales">P0.00</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">Delivered Orders</span>
                                <span class="stat-value" id="deliveredOrdersCount">0</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">Total Orders</span>
                                <span class="stat-value" id="totalOrdersCount">0</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">Cancelled Orders</span>
                                <span class="stat-value" id="cancelledOrdersCount">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <h2>Delivered Orders Log</h2>
                        <div class="sales-list" id="salesList"></div>
                    </div>
                </div>

                <!-- ============ EMPLOYEE: PRODUCT MANAGEMENT TAB ============ -->
                <div class="tab-content hidden" id="tab-products">
                    <div class="dashboard-card">
                        <div class="section-header-row">
                            <div>
                                <h2>Product Management</h2>
                                <p class="section-desc">Add, modify, or remove products from the catalog</p>
                            </div>
                            <button class="btn-primary btn-small" onclick="showProductForm()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Add Product
                            </button>
                        </div>
                        <div class="products-table-wrapper">
                            <table class="data-table" id="productsTable">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTableBody"></tbody>
                            </table>
                            <p class="empty-state hidden" id="productsEmpty">No products in catalog.</p>
                        </div>
                    </div>
                </div>

                <!-- ============ EMPLOYEE/ADMIN: ORDER QUEUE TAB ============ -->
                <div class="tab-content hidden" id="tab-orders">
                    <div class="dashboard-card">
                        <h2>Order Queue</h2>
                        <p class="section-desc">Monitor and update the status of customer orders</p>
                        <div class="orders-table-wrapper">
                            <table class="data-table" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersTableBody"></tbody>
                            </table>
                            <p class="empty-state hidden" id="ordersEmpty">No orders in the queue.</p>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </section>

    <!-- Product Form Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content modal-large">
            <button class="modal-close" id="productModalClose" aria-label="Close">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <h2 id="productModalTitle">Add Product</h2>
            <form class="product-form" id="productForm">
                <input type="hidden" id="productEditId">
                <div class="form-row">
                    <div class="form-group">
                        <label for="productName">Product Name</label>
                        <input type="text" id="productName" required>
                    </div>
                    <div class="form-group">
                        <label for="productPrice">Price (P)</label>
                        <input type="number" id="productPrice" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="productStock">Stock</label>
                        <input type="number" id="productStock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="productCategory">Category</label>
                        <select id="productCategory" required>
                            <option value="chocolates">Chocolates</option>
                            <option value="cakes">Cakes</option>
                            <option value="cookies">Cookies</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="productImage">Image URL</label>
                    <input type="text" id="productImage" placeholder="images/products/product_name.jpg" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeProductModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

<script>
    localStorage.setItem('sweets_session', JSON.stringify({
        id:        "<?php echo $user['id']; ?>",
        name:      "<?php echo addslashes($user['fullname']); ?>",
        email:     "<?php echo addslashes($user['email']); ?>",
        contact:   "<?php echo addslashes($user['contact']); ?>",
        address:   "<?php echo addslashes($user['address']); ?>",
        role:      "<?php echo $user['role']; ?>",
        photo:     "<?php echo addslashes($user['photo'] ?? ''); ?>",
        isLoggedIn: true
    }));
</script>

<script>
(function() {
    'use strict';

    const session = JSON.parse(localStorage.getItem('sweets_session') || sessionStorage.getItem('sweets_session') || '{}');
    if (!session.isLoggedIn) {
        window.location.href = 'login.php';
        return;
    }

    const userRole = session.role || 'customer';
    let currentTab = 'profile';
    let allUsersCache = [];
    let allOrdersCache = [];

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
    window.handleLogout = handleLogout;

    // ============================================================
    // TAB SYSTEM
    // ============================================================
    const tabConfig = {
        admin: [
            { id: 'profile', label: 'Profile', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
            { id: 'accounts', label: 'Accounts', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>' },
            { id: 'sales', label: 'Sales Reports', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>' },
            { id: 'products', label: 'Products', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>' },
            { id: 'orders', label: 'Order Queue', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>' }
        ],
        employee: [
            { id: 'profile', label: 'Profile', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
            { id: 'products', label: 'Products', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>' },
            { id: 'orders', label: 'Order Queue', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>' }
        ],
        customer: [
            { id: 'profile', label: 'Profile', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
            { id: 'link-order', label: 'Place Order', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>', href: 'order.php' },
            { id: 'link-orders', label: 'My Orders', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>', href: 'transactions.php' },
            { id: 'link-track', label: 'Track Order', icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>', href: 'track-order.php' }
        ]
    };

    function buildSidebar() {
        const nav = document.getElementById('sidebarNav');
        const config = tabConfig[userRole] || tabConfig.customer;

        nav.innerHTML = config.map(tab => {
            if (tab.href) {
                return `<a href="${tab.href}" class="sidebar-link">${tab.icon} ${tab.label}</a>`;
            }
            const activeClass = tab.id === 'profile' ? 'active' : '';
            return `<a href="#" class="sidebar-link ${activeClass}" data-tab="${tab.id}" onclick="switchTab('${tab.id}', event)">${tab.icon} ${tab.label}</a>`;
        }).join('');

        const navLinks = document.getElementById('navLinks');
        const mobileNavLinks = document.getElementById('mobileNavLinks');
        if (userRole === 'admin') {
            if (navLinks) navLinks.innerHTML = '<li><a href="order.php">Order</a></li><li><a href="transactions.php">All Orders</a></li><li><a href="dashboard.php">Dashboard</a></li>';
            if (mobileNavLinks) mobileNavLinks.innerHTML = '<li><a href="order.php">Order</a></li><li><a href="transactions.php">All Orders</a></li><li><a href="dashboard.php">Dashboard</a></li><li><a href="#" onclick="handleLogout()">Logout</a></li>';
        } else if (userRole === 'employee') {
            if (navLinks) navLinks.innerHTML = '<li><a href="order.php">Order</a></li><li><a href="transactions.php">Orders</a></li><li><a href="dashboard.php">Dashboard</a></li>';
            if (mobileNavLinks) mobileNavLinks.innerHTML = '<li><a href="order.php">Order</a></li><li><a href="transactions.php">Orders</a></li><li><a href="dashboard.php">Dashboard</a></li><li><a href="#" onclick="handleLogout()">Logout</a></li>';
        }
    }

    window.switchTab = function(tabId, event) {
        if (event) event.preventDefault();
        currentTab = tabId;

        document.querySelectorAll('.sidebar-link[data-tab]').forEach(link => {
            link.classList.toggle('active', link.dataset.tab === tabId);
        });

        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        const target = document.getElementById('tab-' + tabId);
        if (target) target.classList.remove('hidden');

        const titles = {
            profile: { title: userRole === 'admin' ? 'Admin Profile' : userRole === 'employee' ? 'Employee Profile' : 'My Profile', subtitle: 'Manage your personal information' },
            accounts: { title: 'Account Management', subtitle: 'Search, filter, and manage registered accounts' },
            sales: { title: 'Sales Reports', subtitle: 'Generated sales from successfully delivered orders' },
            products: { title: 'Product Management', subtitle: 'Manage products in the catalog' },
            orders: { title: 'Order Queue', subtitle: 'Monitor and update customer order statuses' }
        };
        const t = titles[tabId];
        if (t) {
            document.getElementById('pageTitle').textContent = t.title;
            document.getElementById('pageSubtitle').textContent = t.subtitle;
        }

        if (tabId === 'accounts') renderAccounts();
        if (tabId === 'sales') renderSalesReports();
        if (tabId === 'products') renderProducts();
        if (tabId === 'orders') renderOrderQueue();
    };

    // ============================================================
    // PHOTO UPLOAD
    // ============================================================
    const photoUploadBtn = document.getElementById('photoUploadBtn');
    const photoInput = document.getElementById('photoInput');

    if (photoUploadBtn && photoInput) {
        photoUploadBtn.addEventListener('click', () => photoInput.click());

        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) { showToast('Image must be under 5MB', 'error'); return; }

            const formData = new FormData();
            formData.append('photo', file);

            fetch('upload_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const imgTag = `<img src="${data.photo}" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                    document.getElementById('profilePhoto').innerHTML = imgTag;
                    document.getElementById('userAvatar').innerHTML = imgTag;
                    session.photo = data.photo;
                    localStorage.setItem('sweets_session', JSON.stringify(session));
                    showToast('Photo updated!', 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(() => showToast('Upload failed', 'error'));
        });
    }

    // ============================================================
    // PROFILE FORM
    // ============================================================
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const name    = document.getElementById('editName').value.trim();
            const contact = document.getElementById('editContact').value.trim();
            const address = document.getElementById('editAddress').value.trim();

            if (!name || !contact || !address) {
                showToast('Please fill all fields', 'error');
                return;
            }

            fetch('update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, contact, address })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('sidebarName').textContent = name;
                    showToast('Profile saved!', 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(err => {
                showToast('Something went wrong', 'error');
                console.error(err);
            });
        });
    }

    // ============================================================
    // ADMIN: ACCOUNT MANAGEMENT
    // ============================================================
    function renderAccounts() {
        allUsersCache = JSON.parse(localStorage.getItem('sweets_users') || '[]');
        filterAndRenderAccounts();
    }

    function filterAndRenderAccounts() {
        const search = (document.getElementById('accountSearch')?.value || '').toLowerCase().trim();
        const filter = document.getElementById('accountFilter')?.value || 'all';
        const tbody = document.getElementById('usersTableBody');
        const emptyMsg = document.getElementById('accountsEmpty');

        let filtered = allUsersCache.filter(u => {
            if (filter !== 'all' && u.role !== filter) return false;
            if (search) {
                const hay = (u.name + ' ' + u.email + ' ' + u.contact + ' ' + u.address).toLowerCase();
                return hay.includes(search);
            }
            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = '';
            emptyMsg.classList.remove('hidden');
            return;
        }
        emptyMsg.classList.add('hidden');

        tbody.innerHTML = filtered.map(user => `
            <tr>
                <td><strong>${user.name}</strong></td>
                <td>${user.email}</td>
                <td>${user.contact}</td>
                <td>${user.address}</td>
                <td>
                    <select class="role-select" onchange="changeUserRole('${user.id}', this.value)">
                        <option value="customer" ${user.role === 'customer' ? 'selected' : ''}>Customer</option>
                        <option value="employee" ${user.role === 'employee' ? 'selected' : ''}>Employee</option>
                        <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                    </select>
                </td>
                <td>
                    <button class="btn-icon danger" onclick="deleteUser('${user.id}')" ${user.id === session.id ? 'disabled title="Cannot delete yourself"' : ''}>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    const accountSearch = document.getElementById('accountSearch');
    const accountFilter = document.getElementById('accountFilter');
    if (accountSearch) accountSearch.addEventListener('input', filterAndRenderAccounts);
    if (accountFilter) accountFilter.addEventListener('change', filterAndRenderAccounts);

    window.changeUserRole = function(userId, newRole) {
        const users = JSON.parse(localStorage.getItem('sweets_users') || '[]');
        const idx = users.findIndex(u => u.id === userId);
        if (idx !== -1) {
            users[idx].role = newRole;
            localStorage.setItem('sweets_users', JSON.stringify(users));
            allUsersCache = users;
            filterAndRenderAccounts();
            showToast('Account type changed to ' + newRole, 'success');
        }
    };

    window.deleteUser = function(userId) {
        if (userId === session.id) { showToast('You cannot delete your own account', 'error'); return; }
        if (!confirm('Delete this account permanently?')) return;
        let users = JSON.parse(localStorage.getItem('sweets_users') || '[]');
        users = users.filter(u => u.id !== userId);
        localStorage.setItem('sweets_users', JSON.stringify(users));
        allUsersCache = users;
        filterAndRenderAccounts();
        showToast('Account deleted', 'success');
    };

    // ============================================================
    // ADMIN: SALES REPORTS
    // ============================================================
    function renderSalesReports() {
        const orders = JSON.parse(localStorage.getItem('sweets_orders') || '[]');
        const delivered = orders.filter(o => o.status === 'delivered');
        const cancelled = orders.filter(o => o.status === 'cancelled');
        const totalSales = delivered.reduce((sum, o) => sum + (parseFloat(o.total) || 0), 0);

        document.getElementById('totalSales').textContent = '₱' + totalSales.toFixed(2);
        document.getElementById('deliveredOrdersCount').textContent = delivered.length;
        document.getElementById('totalOrdersCount').textContent = orders.length;
        document.getElementById('cancelledOrdersCount').textContent = cancelled.length;

        const salesList = document.getElementById('salesList');
        if (delivered.length === 0) {
            salesList.innerHTML = '<p class="empty-state">No delivered orders yet.</p>';
            return;
        }
        salesList.innerHTML = delivered
            .sort((a, b) => new Date(b.orderDate) - new Date(a.orderDate))
            .map(order => {
                const date = new Date(order.orderDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const payment = order.paymentMethod ? order.paymentMethod.toUpperCase() : 'CASH';
                return `
                    <div class="sales-item">
                        <div class="sales-info">
                            <span class="sales-order">${order.id}</span>
                            <span class="sales-date">${date} &middot; ${payment}</span>
                        </div>
                        <span class="sales-amount">₱${(parseFloat(order.total) || 0).toFixed(2)}</span>
                    </div>
                `;
            }).join('');
    }

    // ============================================================
    // PRODUCT MANAGEMENT
    // ============================================================
function renderProducts() {
    const products = JSON.parse(localStorage.getItem('sweets_products') || '[]');
    const tbody = document.getElementById('productsTableBody');
    const emptyMsg = document.getElementById('productsEmpty');

    if (products.length === 0) {
        tbody.innerHTML = '';
        emptyMsg.classList.remove('hidden');
        return;
    }
    emptyMsg.classList.add('hidden');

    const IMAGE_SLOTS = {
        1:  'images/products/product1.jpg',
        2:  'images/products/product2.jpg',
        3:  'images/products/product3.jpg',
        4:  'images/products/product4.jpg',
        5:  'images/products/product5.jpg',
        6:  'images/products/product6.jpg',
        7:  'images/products/product7.jpg',
        8:  'images/products/product8.jpg',
        9:  'images/products/product9.jpg',
        10: 'images/products/product10.jpg',
        11: 'images/products/product11.jpg',
        12: 'images/products/product12.jpg',
        13: 'images/products/product13.jpg',
    };

    tbody.innerHTML = products.map((p, i) => {
        const slot = i + 1;
        const imgSrc = IMAGE_SLOTS[slot] || 'images/products/product_macaron_small.jpg';
        return `
        <tr>
            <td><img src="${imgSrc}" alt="${p.name}" class="product-thumb" onerror="this.src='images/products/product_macaron_small.jpg'"></td>
            <td><strong>${p.name}</strong></td>
            <td>₱${parseFloat(p.price || 0).toFixed(2)}</td>
            <td>${p.stock}</td>
            <td>${p.category}</td>
            <td>
                <button class="btn-icon" onclick="editProduct('${p.id}')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
                <button class="btn-icon danger" onclick="deleteProduct('${p.id}')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                    </svg>
                </button>
            </td>
        </tr>`;
    }).join('');
}

    window.showProductForm = function(productId) {
        const modal = document.getElementById('productModal');
        const title = document.getElementById('productModalTitle');
        document.getElementById('productForm').reset();
        document.getElementById('productEditId').value = '';

        if (productId) {
            const products = JSON.parse(localStorage.getItem('sweets_products') || '[]');
            const p = products.find(x => x.id === productId);
            if (p) {
                title.textContent = 'Edit Product';
                document.getElementById('productEditId').value = p.id;
                document.getElementById('productName').value = p.name;
                document.getElementById('productPrice').value = p.price;
                document.getElementById('productStock').value = p.stock;
                document.getElementById('productCategory').value = p.category;
                document.getElementById('productDescription').value = p.description || '';
                document.getElementById('productImage').value = p.image;
            }
        } else {
            title.textContent = 'Add Product';
        }
        modal.classList.add('active');
    };

    window.closeProductModal = function() {
        document.getElementById('productModal').classList.remove('active');
    };

    window.editProduct = function(id) { showProductForm(id); };

    window.deleteProduct = function(id) {
        if (!confirm('Remove this product from the catalog?')) return;
        let products = JSON.parse(localStorage.getItem('sweets_products') || '[]');
        products = products.filter(p => p.id !== id);
        localStorage.setItem('sweets_products', JSON.stringify(products));
        renderProducts();
        showToast('Product removed', 'success');
    };

    document.getElementById('productForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const editId = document.getElementById('productEditId').value;
        const name = document.getElementById('productName').value.trim();
        const price = parseFloat(document.getElementById('productPrice').value);
        const stock = parseInt(document.getElementById('productStock').value);
        const category = document.getElementById('productCategory').value;
        const description = document.getElementById('productDescription').value.trim();
        const image = document.getElementById('productImage').value.trim();

        let products = JSON.parse(localStorage.getItem('sweets_products') || '[]');
        if (editId) {
            const idx = products.findIndex(p => p.id === editId);
            if (idx !== -1) products[idx] = { ...products[idx], name, price, stock, category, description, image };
        } else {
            products.push({ id: 'p' + Date.now(), name, price, stock, category, description, image });
        }
        localStorage.setItem('sweets_products', JSON.stringify(products));
        closeProductModal();
        renderProducts();
        showToast(editId ? 'Product updated!' : 'Product added!', 'success');
    });

    document.getElementById('productModalClose').addEventListener('click', closeProductModal);

    // ============================================================
    // ORDER QUEUE
    // ============================================================
    const STATUS_OPTIONS = [
        { value: 'processing', label: 'Processing' },
        { value: 'preparing', label: 'Preparing' },
        { value: 'out-for-delivery', label: 'Out for Delivery' },
        { value: 'order-received', label: 'Order Received' },
        { value: 'cancelled', label: 'Cancelled' }
    ];

    function getStatusLabel(value) {
        const found = STATUS_OPTIONS.find(s => s.value === value);
        return found ? found.label : value;
    }

    function getStatusClass(value) {
        const map = {
            'processing': 'status-processing',
            'preparing': 'status-preparing',
            'out-for-delivery': 'status-shipped',
            'order-received': 'status-delivered',
            'cancelled': 'status-cancelled'
        };
        return map[value] || 'status-processing';
    }

    function renderOrderQueue() {
        const orders = JSON.parse(localStorage.getItem('sweets_orders') || '[]');
        const users = JSON.parse(localStorage.getItem('sweets_users') || '[]');
        const tbody = document.getElementById('ordersTableBody');
        const emptyMsg = document.getElementById('ordersEmpty');

        if (orders.length === 0) {
            tbody.innerHTML = '';
            emptyMsg.classList.remove('hidden');
            return;
        }
        emptyMsg.classList.add('hidden');

        tbody.innerHTML = orders
            .sort((a, b) => new Date(b.orderDate) - new Date(a.orderDate))
            .map(order => {
                const user = users.find(u => u.id === order.userId);
                const itemCount = (order.items || []).reduce((sum, i) => sum + (i.quantity || 0), 0);
                const payment = order.paymentMethod ? order.paymentMethod.toUpperCase() : 'CASH';
                const date = new Date(order.orderDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

                return `
                    <tr>
                        <td><strong>${order.id}</strong><br><small class="text-muted">${date}</small></td>
                        <td>${user ? user.name : 'Unknown'}<br><small class="text-muted">${user ? user.contact : ''}</small></td>
                        <td>${itemCount} item(s)</td>
                        <td>₱${(parseFloat(order.total) || 0).toFixed(2)}</td>
                        <td>${payment}</td>
                        <td><span class="status-badge ${getStatusClass(order.status)}">${getStatusLabel(order.status)}</span></td>
                        <td>
                            <select class="status-select-small" onchange="updateOrderStatus('${order.id}', this.value)">
                                ${STATUS_OPTIONS.map(s => `<option value="${s.value}" ${order.status === s.value ? 'selected' : ''}>${s.label}</option>`).join('')}
                            </select>
                        </td>
                    </tr>
                `;
            }).join('');
    }

    window.updateOrderStatus = function(orderId, newStatus) {
        const orders = JSON.parse(localStorage.getItem('sweets_orders') || '[]');
        const order = orders.find(o => o.id === orderId);
        if (!order) return;
        order.status = newStatus;

        if (!order.trackingSteps) {
            order.trackingSteps = [
                { status: 'confirmed', label: 'Order Confirmed', completed: true },
                { status: 'processing', label: 'Processing', completed: false },
                { status: 'preparing', label: 'Preparing', completed: false },
                { status: 'out-for-delivery', label: 'Out for Delivery', completed: false },
                { status: 'order-received', label: 'Order Received', completed: false }
            ];
        }

        const statusIndex = STATUS_OPTIONS.findIndex(s => s.value === newStatus);
        order.trackingSteps.forEach(step => {
            const stepIdx = STATUS_OPTIONS.findIndex(s => s.value === step.status);
            step.completed = stepIdx <= statusIndex && step.status !== 'cancelled';
            if (step.completed && !step.date) step.date = new Date().toISOString();
        });

        if (newStatus === 'cancelled') {
            order.trackingSteps.forEach(s => { if (s.status !== 'confirmed') s.completed = false; });
        }

        localStorage.setItem('sweets_orders', JSON.stringify(orders));
        renderOrderQueue();
        showToast('Order status: ' + getStatusLabel(newStatus), 'success');
    };

    // ============================================================
    // INIT
    // ============================================================
    buildSidebar();
    switchTab('profile');

    console.log('Dashboard ready | Role:', userRole);
})();
</script>
</body>
</html>
