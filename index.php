<?php
session_start();
include 'handy_methods.php';
require_once 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {

            $_SESSION["logged_in"] = true;
            $_SESSION["email"] = $user["email"];
            $_SESSION["fullName"] = $user["fullName"] ?? "";

            if (empty($user["onboarding_complete"])) {
                header("Location: setup.php");
            } else {
                header("Location: home.php");
            }
            exit;

        } else {
            $error = "Wrong email or password.";
        }

    } catch (PDOException $e) {
        $error = "Login failed: " . $e->getMessage();
    }
}
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
            <img src="images/VCLogoTransparent.png" alt="VerifiedCircle Logo" class="vc-logo"> <!-- Replaced 'Your Logo' text with VCLogo image -->
            <h2>Login</h2>
            <form action="" method="POST">
                <input type="email" name="email" placeholder="Enter your email" required>
                <input type="password" name="password" placeholder="Password" required>
                <a href="#" class="forgot-password">Forgot Password?</a>
                <button type="submit" class="login-button">Log in</button>
            </form>
            <p>Don’t have an account? <a href="register.php">Register for free</a></p>
        </div>
    </div>
</body>
</html>

