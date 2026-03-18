<?php
// Startar PHP-sessionen så vi kan läsa vem som är inloggad
session_start();

// Laddar databasanslutningen från db.php
require_once "db.php";

// Stänger av att PHP visar fel direkt i webbläsaren
// men loggar dem fortfarande i bakgrunden
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Sätter att svaret från denna fil ska vara JSON
// eftersom chatten använder AJAX
header("Content-Type: application/json; charset=UTF-8");

/*
Denna funktion används för att skicka JSON tillbaka
till frontend (JavaScript). Den avslutar också skriptet.
*/
function json_out($arr) {

    // Om något redan skrivits ut rensas det
    // så att JSON-svaret inte förstörs
    if (ob_get_length()) {
        ob_clean();
    }

    // Skickar JSON till klienten
    echo json_encode($arr);

    // Stoppar resten av skriptet
    exit;
}

// Startar output buffering
// så vi kan kontrollera att inget oönskat skrivs ut
ob_start();

try {

    // Kontrollera att användaren faktiskt är inloggad
    if (empty($_SESSION["logged_in"])) {

        json_out([
            "success" => false,
            "error" => "Not logged in"
        ]);
    }

    // Hämtar avsändarens email från sessionen
    $sender = trim($_SESSION["email"] ?? "");

    // Hämtar mottagarens email från POST (AJAX request)
    $receiver = trim($_POST["receiver_email"] ?? "");

    // Själva meddelandet
    $message = trim($_POST["message"] ?? "");

    // Om sessionen saknar email betyder det att något är fel
    if ($sender === "") {

        json_out([
            "success" => false,
            "error" => "Missing sender session email"
        ]);
    }

    // Om mottagare eller meddelande saknas stoppar vi
    if ($receiver === "" || $message === "") {

        json_out([
            "success" => false,
            "error" => "Missing receiver or message"
        ]);
    }

    // Säkerhet: för långa meddelanden tillåts inte
    if (mb_strlen($message) > 1000) {

        json_out([
            "success" => false,
            "error" => "Message too long"
        ]);
    }

    /*
    Kontrollera att dessa två användare faktiskt är en match.
    Man ska inte kunna skriva till vem som helst i systemet.
    */
    $sql = "
        SELECT COUNT(*) AS match_count
        FROM matches
        WHERE
          (user1_email = ? AND user2_email = ?)
          OR
          (user1_email = ? AND user2_email = ?)
    ";

    // Förbereder SQL-frågan
    $stmt = $conn->prepare($sql);

    // Kör frågan med båda riktningarna (A->B eller B->A)
    $stmt->execute([$sender, $receiver, $receiver, $sender]);

    // Hämtar resultatet
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Om de inte är en match stoppar vi
    if (!$row || (int)$row["match_count"] < 1) {

        json_out([
            "success" => false,
            "error" => "You can only message your matches",
            "sender" => $sender,
            "receiver" => $receiver
        ]);
    }

    /*
    Om matchen finns sparar vi meddelandet i databasen
    */
    $insertSql = "
        INSERT INTO messages (sender_email, receiver_email, message)
        VALUES (?, ?, ?)
    ";

    $stmt = $conn->prepare($insertSql);

    // Kör insert
    $ok = $stmt->execute([$sender, $receiver, $message]);

    // Om databasen misslyckas skickar vi felinfo
    if (!$ok) {

        $info = $stmt->errorInfo();

        json_out([
            "success" => false,
            "error" => "Database insert failed",
            "details" => $info
        ]);
    }

    // Allt gick bra
    json_out([
        "success" => true
    ]);

} catch (Throwable $e) {

    // Om något oväntat fel händer
    json_out([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}