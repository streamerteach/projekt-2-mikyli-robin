<?php
session_start();
require_once "db.php";

if (empty($_SESSION["logged_in"]) || empty($_SESSION["email"])) {
    header("Location: index.php");
    exit;
}

$currentEmail = $_SESSION["email"];
$action = $_POST["action"] ?? "";
$candidateEmail = trim($_POST["candidate"] ?? "");

if ($candidateEmail === "" || strtolower($candidateEmail) === strtolower($currentEmail)) {
    header("Location: home.php");
    exit;
}

try {
    // Make sure candidate exists and has completed onboarding
    $stmt = $conn->prepare("
        SELECT email, onboarding_complete
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$candidateEmail]);
    $candidateUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidateUser || empty($candidateUser["onboarding_complete"])) {
        header("Location: home.php");
        exit;
    }

    if ($action === "skip") {
        // Insert skip only if it doesn't already exist
        $stmt = $conn->prepare("
            IF NOT EXISTS (
                SELECT 1 FROM skips WHERE from_email = ? AND to_email = ?
            )
            INSERT INTO skips (from_email, to_email)
            VALUES (?, ?)
        ");
        $stmt->execute([
            $currentEmail, $candidateEmail,
            $currentEmail, $candidateEmail
        ]);

        header("Location: home.php");
        exit;
    }

    if ($action === "like") {
        // Insert like only if it doesn't already exist
        $stmt = $conn->prepare("
            IF NOT EXISTS (
                SELECT 1 FROM likes WHERE from_email = ? AND to_email = ?
            )
            INSERT INTO likes (from_email, to_email)
            VALUES (?, ?)
        ");
        $stmt->execute([
            $currentEmail, $candidateEmail,
            $currentEmail, $candidateEmail
        ]);

        // Check if reverse like exists
        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM likes
            WHERE from_email = ? AND to_email = ?
        ");
        $stmt->execute([$candidateEmail, $currentEmail]);
        $reverseLikeExists = ((int)$stmt->fetchColumn() > 0);

        if ($reverseLikeExists) {
            // Normalize order to avoid duplicate match rows
            if (strtolower($currentEmail) < strtolower($candidateEmail)) {
                $user1 = $currentEmail;
                $user2 = $candidateEmail;
            } else {
                $user1 = $candidateEmail;
                $user2 = $currentEmail;
            }

            $stmt = $conn->prepare("
                IF NOT EXISTS (
                    SELECT 1 FROM matches WHERE user1_email = ? AND user2_email = ?
                )
                INSERT INTO matches (user1_email, user2_email)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $user1, $user2,
                $user1, $user2
            ]);
        }

        header("Location: home.php");
        exit;
    }

    header("Location: home.php");
    exit;

} catch (PDOException $e) {
    die("Action failed: " . $e->getMessage());
}
?>