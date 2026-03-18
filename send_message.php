<?php
session_start();
require_once "db.php";

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

function json_out($arr) {
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($arr);
    exit;
}

ob_start();

try {
    if (empty($_SESSION["logged_in"])) {
        json_out([
            "success" => false,
            "error" => "Not logged in"
        ]);
    }

    $sender = trim($_SESSION["email"] ?? "");
    $receiver = trim($_POST["receiver_email"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($sender === "") {
        json_out([
            "success" => false,
            "error" => "Missing sender session email"
        ]);
    }

    if ($receiver === "" || $message === "") {
        json_out([
            "success" => false,
            "error" => "Missing receiver or message"
        ]);
    }

    if (mb_strlen($message) > 1000) {
        json_out([
            "success" => false,
            "error" => "Message too long"
        ]);
    }

    $sql = "
        SELECT COUNT(*) AS match_count
        FROM matches
        WHERE
          (user1_email = ? AND user2_email = ?)
          OR
          (user1_email = ? AND user2_email = ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$sender, $receiver, $receiver, $sender]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || (int)$row["match_count"] < 1) {
        json_out([
            "success" => false,
            "error" => "You can only message your matches",
            "sender" => $sender,
            "receiver" => $receiver
        ]);
    }

    $insertSql = "
        INSERT INTO messages (sender_email, receiver_email, message)
        VALUES (?, ?, ?)
    ";

    $stmt = $conn->prepare($insertSql);
    $ok = $stmt->execute([$sender, $receiver, $message]);

    if (!$ok) {
        $info = $stmt->errorInfo();
        json_out([
            "success" => false,
            "error" => "Database insert failed",
            "details" => $info
        ]);
    }

    json_out([
        "success" => true
    ]);

} catch (Throwable $e) {
    json_out([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}