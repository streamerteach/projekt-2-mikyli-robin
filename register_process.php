<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email.'); window.location.href='register.php';</script>";
        exit;
    }

    // Calculate age
    $birthDateObj = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthDateObj)->y;

    if ($age < 18) {
        echo "<script>alert('You must be at least 18 years old to register.'); window.location.href='register.php';</script>";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match. Please try again.'); window.location.href='register.php';</script>";
        exit;
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ===== Local storage (JSON) =====
    $file = __DIR__ . "/users.json";
    if (!file_exists($file)) {
        file_put_contents($file, "{}");
    }

    $users = json_decode(file_get_contents($file), true);
    if (!is_array($users)) $users = [];

    // Prevent duplicate registration
    if (isset($users[$email])) {
        echo "<script>alert('Email already registered. Please sign in.'); window.location.href='index.php';</script>";
        exit;
    }

    // Save user
    $users[$email] = [
        "fullname" => $fullname,
        "password" => $hashed_password,
        "birthdate" => $birthdate,
        "created_at" => date("c")
    ];

    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));

    echo "<script>alert('Registration successful!'); window.location.href='index.php';</script>";
    exit;
}
?>
