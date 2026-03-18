<?php
session_start();
require_once "db.php";

header("Content-Type: application/json; charset=UTF-8");

try {
  if (empty($_SESSION["logged_in"])) {
    echo json_encode([
      "success" => false,
      "error" => "Not logged in"
    ]);
    exit;
  }

  $me = $_SESSION["email"] ?? "";
  $chatWith = trim($_GET["chat_with"] ?? "");

  if ($chatWith === "") {
    echo json_encode([
      "success" => false,
      "error" => "Missing chat user"
    ]);
    exit;
  }

  $sql = "
    SELECT id, sender_email, receiver_email, message, created_at
    FROM messages
    WHERE
      (sender_email = ? AND receiver_email = ?)
      OR
      (sender_email = ? AND receiver_email = ?)
    ORDER BY created_at ASC, id ASC
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute([$me, $chatWith, $chatWith, $me]);
  $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    "success" => true,
    "messages" => $messages
  ]);
  exit;

} catch (Throwable $e) {
  echo json_encode([
    "success" => false,
    "error" => $e->getMessage()
  ]);
  exit;
}