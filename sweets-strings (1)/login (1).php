<?php

include 'database.php';

if(isset($_POST['login'])) {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $password = $_POST['password'];


    //SQL INJECTION//
    $sql = "SELECT * FROM users WHERE email='$email' AND contact='$contact'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0) {

        $row = mysqli_fetch_assoc($result);

        if($password == $row['password']) {

            // SET SESSION NAME BASED ON ROLE
            if($row['role'] == 'admin') {

                session_name("ADMIN_SESSION");

            }
            elseif($row['role'] == 'employee') {

                session_name("EMPLOYEE_SESSION");

            }
            else {

                session_name("CUSTOMER_SESSION");

            }

            // START SESSION AFTER session_name()
            session_start();

            // SESSION DATA
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['role'] = $row['role'];

            // ROLE REDIRECT
            if($row['role'] == 'admin') {

                echo "<script>alert('Welcome Admin!'); window.location.href='admin.php';</script>";

            }
            elseif($row['role'] == 'employee') {

                echo "<script>alert('Welcome Employee!'); window.location.href='employee.php';</script>";

            }
            else {

                echo "<script>alert('Welcome Customer!'); window.location.href='dashboard.php';</script>";

            }

        } else {

            echo "<script>alert('Incorrect password!');</script>";

        }

    } else {

        echo "<script>alert('Account not found!');</script>";

    }

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sweets & Strings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
    <!-- Navigation -->
    <nav class="nav" id="nav">
        <div class="nav-container">
            <a href="homepage.php" class="logo">Sweets & Strings</a>
            <ul class="nav-links">
                <li><a href="homepage.php#collections">Shop</a></li>
                <li><a href="homepage.php#collections">Collections</a></li>
                <li><a href="homepage.php#how-it-works">Gifting</a></li>
                <li><a href="homepage.php#testimonials">About</a></li>
            </ul>
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <div class="mobile-menu" id="mobileMenu">
            <ul>
                <li><a href="homepage.php#collections">Shop</a></li>
                <li><a href="homepage.php#collections">Collections</a></li>
                <li><a href="homepage.php#how-it-works">Gifting</a></li>
                <li><a href="homepage.php#testimonials">About</a></li>
            </ul>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Welcome Back</h1>
                    <p>Sign in to continue your sweet journey</p>
                </div>

                <form class="auth-form" id="loginForm" method="POST"action="login.php">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" name="email" placeholder="you@example.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contact">Contact Number</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                            </svg>
                            <input type="tel" id="contact" name="contact" placeholder="09XXXXXXXXX" required maxlength="11">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            <span class="checkbox-label">Remember me</span>
                        </label>
                        <a href="forgot-password.html" class="forgot-link">Forgot password?</a>
                    </div>

                   <button type="submit" name="login" class="btn-primary btn-full">
    Sign In
</button>
                </form>

                <div class="auth-divider">
                    <span>or</span>
                </div>

                <p class="auth-switch">
                    Don't have an account? <a href="register.php">Create one</a>
                </p>

                <a href="dashboard.php" class="back-link">← Back to home</a>
            </div>
        </div>
    </section>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <span class="toast-message"></span>
    </div>

    <script>/**
 * Sweets & Strings - UI Script (PHP Compatible Version)
 * Handles ONLY frontend interactions
 */

(function () {
    'use strict';

    // ================================
    // Toast Notification
    // ================================
    function showToast(message, type = 'default') {
        const toast = document.getElementById('toast');
        if (!toast) return;

        toast.textContent = message;
        toast.className = 'toast';

        if (type) toast.classList.add(type);

        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // expose globally (optional)
    window.showToast = showToast;

    // ================================
    // Mobile Menu Toggle
    // ================================
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function () {
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });

        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
            });
        });
    }

    // ================================
    // Toggle Password Visibility
    // ================================
    const togglePassword = document.getElementById('togglePassword');

    if (togglePassword) {
        togglePassword.addEventListener('click', function () {
            const input = document.getElementById('password');
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            this.innerHTML = isPassword
                ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                   </svg>`
                : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                   </svg>`;
        });
    }
// ================================
    // Contact Number: numbers only, max 11 digits
    // ================================
    const contactInput = document.getElementById('contact');
    if (contactInput) {
        contactInput.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        contactInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });

        contactInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            const numbersOnly = pasted.replace(/[^0-9]/g, '').slice(0, 11);
            this.value = numbersOnly;
        });
    }
    // ================================
    // Optional: Prevent empty form submit UI-side
    // (PHP still validates backend)
    // ================================
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function () {
            showToast('Processing...', 'default');
        });
    });

})();
</script>
</body>
</html>
