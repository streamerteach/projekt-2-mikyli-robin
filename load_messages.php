<?php

// Startar sessionen så vi kan läsa vem som är inloggad
session_start();

// Laddar databasanslutningen
require_once "db.php";

// Denna endpoint returnerar JSON eftersom den används av JavaScript/AJAX
header("Content-Type: application/json; charset=UTF-8");

try {

  // Kontrollera att användaren är inloggad
  // annars ska chatten inte kunna laddas
  if (empty($_SESSION["logged_in"])) {

    echo json_encode([
      "success" => false,
      "error" => "Not logged in"
    ]);
    exit;
  }

  // Hämtar den inloggade användarens email från sessionen
  $me = $_SESSION["email"] ?? "";

  // Hämtar vem chatten ska laddas med (skickas från frontend via GET)
  $chatWith = trim($_GET["chat_with"] ?? "");

  // Om ingen mottagare är angiven kan vi inte hämta chatten
  if ($chatWith === "") {

    echo json_encode([
      "success" => false,
      "error" => "Missing chat user"
    ]);
    exit;
  }

  /*
  SQL-frågan hämtar alla meddelanden mellan två användare.
  Vi måste kontrollera båda riktningarna eftersom båda
  kan vara avsändare.
  */

  $sql = "
    SELECT id, sender_email, receiver_email, message, created_at
    FROM messages
    WHERE
      (sender_email = ? AND receiver_email = ?)
      OR
      (sender_email = ? AND receiver_email = ?)
    ORDER BY created_at ASC, id ASC
  ";

  // Förbereder SQL-frågan (prepared statement för säkerhet)
  $stmt = $conn->prepare($sql);

  // Kör frågan med parametrar
  $stmt->execute([$me, $chatWith, $chatWith, $me]);

  // Hämtar alla meddelanden som en array
  $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Skickar tillbaka alla meddelanden till frontend
  echo json_encode([
    "success" => true,
    "messages" => $messages
  ]);

  exit;

} catch (Throwable $e) {

  // Om något går fel skickas ett felmeddelande som JSON
  echo json_encode([
    "success" => false,
    "error" => $e->getMessage()
  ]);

  exit;
}