<?php
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email.'); window.location.href='register.php';</script>";
        exit;
    }

    if (empty($birthdate)) {
        echo "<script>alert('Birthdate is required.'); window.location.href='register.php';</script>";
        exit;
    }

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

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        $emailExists = $checkStmt->fetchColumn();

        if ($emailExists > 0) {
            echo "<script>alert('Email already registered. Please sign in.'); window.location.href='index.php';</script>";
            exit;
        }

        // Insert new user
        $insertStmt = $conn->prepare("
            INSERT INTO users (fullname, email, birthdate, password, onboarding_complete)
            VALUES (?, ?, ?, ?, ?)
        ");

        $insertStmt->execute([
            $fullname,
            $email,
            $birthdate,
            $hashed_password,
            0
        ]);

        echo "<script>alert('Registration successful!'); window.location.href='index.php';</script>";
        exit;

    } catch (PDOException $e) {
        echo "Registration failed: " . $e->getMessage();
        exit;
    }
}
?>