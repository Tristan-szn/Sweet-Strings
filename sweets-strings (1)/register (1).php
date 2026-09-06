<?php

    include 'database.php';

    if(isset($_POST['register'])) {

        $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $contact = mysqli_real_escape_string($conn, $_POST['contact']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        // Check if passwords match
        if($password != $confirmPassword) {

            echo "<script>alert('Passwords do not match!');</script>";

        } else {

            // Encrypt password
            $hashed_password = $password;

            // Check if email already existsy
            $checkEmail = "SELECT * FROM users WHERE email='$email'";
            $result = mysqli_query($conn, $checkEmail);

            if(mysqli_num_rows($result) > 0) {

                echo "<script>alert('Email already registered!');</script>";

            } else {

                // Insert user into database
                $sql = "INSERT INTO users(fullname, email, contact, address, password)
                        VALUES('$fullname', '$email', '$contact', '$address', '$hashed_password')";

                if(mysqli_query($conn, $sql)) {

                    echo "<script
                            alert('Registration Successful!');
                            window.location.href='login.php';
                        </script>";

                } else {

                    echo "<script>alert('Something went wrong!');</script>";

                }
            }
        }
    }

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register - Sweets & Strings</title>
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
                <a href="index.html" class="logo">Sweets & Strings</a>
                <ul class="nav-links">
                    <li><a href="index.html#collections">Shop</a></li>
                    <li><a href="index.html#collections">Collections</a></li>
                    <li><a href="index.html#how-it-works">Gifting</a></li>
                    <li><a href="index.html#testimonials">About</a></li>
                </ul>
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <div class="mobile-menu" id="mobileMenu">
                <ul>
                    <li><a href="index.html#collections">Shop</a></li>
                    <li><a href="index.html#collections">Collections</a></li>
                    <li><a href="index.html#how-it-works">Gifting</a></li>
                    <li><a href="index.html#testimonials">About</a></li>
                </ul>
            </div>
        </nav>

        <!-- Register Section -->
        <section class="auth-section">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <h1>Create Account</h1> 
                        <p>Join us for a sweeter experience</p>
                    </div>

                    <form class="auth-form" id="registerForm" method="POST" action="register.php">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <input type="text" id="fullname" name="fullname" placeholder="Your full name" required>
                            </div>
                        </div>

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
                                <input type="tel" id="contact" name="contact" placeholder="+1 234 567 8900" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Delivery Address</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <input type="text" id="address" name="address" placeholder="Your complete address" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                                </svg>
                                <input type="password" id="password" name="password" placeholder="Create a password" required minlength="6">
                                <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <span class="form-hint">Must be at least 6 characters</span>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                                </svg>
                                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-wrapper terms">
                                <input type="checkbox" name="terms" required>
                                <span class="checkmark"></span>
                                <span class="checkbox-label">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                            </label>
                        </div>

                        <button type="submit" name="register" class="btn-primary btn-full">
        Create Account
    </button>
                    </form>

                    <div class="auth-divider">
                        <span>or</span>
                    </div>

                    <p class="auth-switch">
                        Already have an account? <a href="login.php">Sign in</a>
                    </p>

                    <a href="index.html" class="back-link">← Back to home</a>
                </div>
            </div>
        </section>

        <!-- Toast Notification -->
        <div class="toast" id="toast">
            <span class="toast-message"></span>
        </div>



        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const contactInput = document.getElementById('contact');

            contactInput.addEventListener('input', function (e) {
                // 1. Remove all non-numeric characters (letters, symbols, spaces)
                let value = e.target.value.replace(/\D/g, '');

                // 2. Truncate the string to a maximum of 11 digits
                if (value.length > 11) {
                    value = value.slice(0, 11);
                }

                // 3. Set the input value to the cleaned number
                e.target.value = value;
            });

            // Optional: Block non-numeric key presses directly for a smoother feel
            contactInput.addEventListener('keypress', function (e) {
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });
    </script>
    </body>
    </html>
