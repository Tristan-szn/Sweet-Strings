<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sweets & Strings</title>
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
                <li><a href="order.html">Order</a></li>
                <li><a href="transactions.html">All Orders</a></li>
                <li><a href="#" id="dashboardLink" class="active">Admin Dashboard</a></li>
            </ul>
            <div class="user-menu">
                <a href="admin-dashboard.html" class="user-avatar" id="userAvatar">
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
                <li><a href="order.html">Order</a></li>
                <li><a href="transactions.html">All Orders</a></li>
                <li><a href="#" id="mobileDashboardLink" class="active">Admin Dashboard</a></li>
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
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <button class="photo-upload-btn" id="photoUploadBtn" aria-label="Upload photo">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                        </button>
                        <input type="file" id="photoInput" accept="image/*" hidden>
                    </div>
                    <h3 class="profile-name" id="sidebarName">Admin User</h3>
                    <p class="profile-email" id="sidebarEmail">admin@example.com</p>
                    <span class="role-badge" id="roleBadge">Administrator</span>
                </div>

                <!-- Admin Tab Navigation -->
                <nav class="sidebar-nav" id="sidebarNav">
    <a href="#" class="sidebar-link active" data-tab="profile" onclick="switchTab('profile', event)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg> <span>Admin Profile</span>
    </a>
    <a href="#" class="sidebar-link" data-tab="accounts" onclick="switchTab('accounts', event)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
        </svg> <span>Accounts</span>
    </a>
    <a href="#" class="sidebar-link" data-tab="sales" onclick="switchTab('sales', event)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>
        </svg> <span>Sales Reports</span>
    </a>
    <a href="#" class="sidebar-link" data-tab="products" onclick="switchTab('products', event)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
        </svg> <span>Products</span>
    </a>
    <a href="#" class="sidebar-link" data-tab="orders" onclick="switchTab('orders', event)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
        </svg> <span>Order Queue</span>
    </a>
</nav>
            </aside>

            <!-- Main Content -->
            <main class="dashboard-main" id="dashboardMain">
                <div class="dashboard-header">
                    <h1 id="pageTitle">Admin Dashboard</h1>
                    <p id="pageSubtitle">Complete administrative control</p>
                </div>

                <!-- ============ ADMIN PROFILE ============ -->
                <div class="tab-content" id="tab-profile">
                    <div class="dashboard-card">
                        <h2>Admin Profile</h2>
                        <form class="profile-form" id="profileForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="editName">Full Name</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                        <input type="text" id="editName" name="name" placeholder="Your full name" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="editEmail">Email Address</label>
                                    <div class="input-wrapper">
                                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                            <polyline points="22,6 12,13 2,6"/>
                                        </svg>
                                        <input type="email" id="editEmail" name="email" placeholder="you@example.com" required>
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
                                        <input type="tel" id="editContact" name="contact" placeholder="+63 912 345 6789" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="editAddress">Admin Notes</label>
                                <div class="input-wrapper textarea-wrapper">
                                    <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <textarea id="editAddress" name="address" rows="3" placeholder="Internal admin notes or delivery address" required></textarea>
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
                        <h2>Admin Account Information</h2>
                        <div class="account-info">
                            <div class="info-row">
                                <span class="info-label">Admin Since</span>
                                <span class="info-value" id="memberSince">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Admin ID</span>
                                <span class="info-value" id="accountId">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Account Type</span>
                                <span class="info-value" id="accountType">Administrator</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============ ACCOUNTS MANAGEMENT ============ -->
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

                <!-- ============ SALES REPORTS ============ -->
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

                <!-- ============ PRODUCT MANAGEMENT ============ -->
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

                <!-- ============ ORDER QUEUE ============ -->
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

    <script src="admin-dashboard.js"></script>
</body>
</html>