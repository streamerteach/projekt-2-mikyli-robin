<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $birthdate = $_POST['birthdate'];

    // Calculate age
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;

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

    // Save user data to the database (example code, replace with your database logic)
    // $db = new mysqli('localhost', 'username', 'password', 'database');
    // $stmt = $db->prepare("INSERT INTO users (fullname, email, password, birthdate) VALUES (?, ?, ?, ?)");
    // $stmt->bind_param('ssss', $fullname, $email, $hashed_password, $birthdate);
    // $stmt->execute();

    echo "<script>alert('Registration successful!'); window.location.href='index.php';</script>";
}
?>