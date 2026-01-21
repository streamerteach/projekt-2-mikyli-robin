<?php
    include 'handy_methods.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body style="background-image: url('images/VCbackground.png');">
    <div class="login-container">
        <div class="login-form">
            <img src="images/VCLogo.png" alt="VerifiedCircle Logo" class="vc-logo"> <!-- Replaced 'Your Logo' text with VCLogo image -->
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Enter your email" required>
                <input type="password" name="password" placeholder="Password" required>
                <a href="#" class="forgot-password">Forgot Password?</a>
                <button type="submit" class="login-button">Sign in</button>
            </form>
            <div class="social-login">
                <button class="google-login">
                    <img src="images/google-logo.png" alt="Google Logo">
                    Google
                </button>
                <button class="facebook-login">
                    <img src="images/facebook-logo.png" alt="Facebook Logo">
                    Facebook
                </button>
                <button class="apple-login">
                    <img src="images/apple-logo.png" alt="Apple Logo">
                    Apple
                </button>
            </div>
            <p>Don’t have an account? <a href="register.php">Register for free</a></p>
        </div>
    </div>
</body>
</html>