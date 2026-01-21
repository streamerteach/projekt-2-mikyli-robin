<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body style="background-image: url('images/VCbackground.png');">
    <div class="login-container">
        <div class="login-form">
            <img src="images/VCLogo.png" alt="VerifiedCircle Logo" class="vc-logo">
            <h2>Register</h2>
            <form action="register_process.php" method="POST">
                <input type="text" name="fullname" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <input type="date" name="birthdate" placeholder="Date of Birth" required>
                <button type="submit" class="login-button">Register</button>
            </form>
            <p>Already have an account? <a href="index.php">Sign in</a></p>
        </div>
    </div>
</body>
</html>