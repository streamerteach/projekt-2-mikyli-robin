<?php
session_start();
require_once "db.php";

if (empty($_SESSION["logged_in"])) { // Om användaren inte är inloggad, omdirigerar den till inloggningssidan
  header("Location: index.php");
  exit;
}

$email = $_SESSION["email"] ?? ""; // Hämtar den inloggade användarens e-postadress

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header("Location: index.php");
  exit;
}

if (empty($user["onboarding_complete"])) { // Om användaren inte har slutfört onboarding, omdirigerar den till setup-sidan
  header("Location: setup.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VerifiedCircle</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body id="home" style="background-image: url('images/VCbackground.png');">

  <header>
    <div class="avatar" aria-label="Profile"></div>

    <div class="titleWrap">
      <div class="title">VerifiedCircle</div>
        <nav class="nav" aria-label="Main Navigation">
        <a href="home.php">Home</a>
        <a href="circle.php">Circle</a>
        <a href="discover.php" class="active">Discover</a>
        <a href="profile.php">Profile</a>
        </nav>
    </div>
    
    <div class="burger" aria-label="Menu" id="burgerBtn">
  <div class="burgerLines">
    <span></span>
    <span></span>
    <span></span>
  </div>
</div>

<!-- Hamburger dropdown -->
<div class="menuDropdown" id="menuDropdown" aria-label="User menu">
  <a class="menuItem" href="setup.php">Setup</a>
  <a class="menuItem" href="reviews.php">Leave a Review</a>
  <a class="menuItem" href="rapporten.html">Rapporten</a>
  <a class="menuItem logout" href="logout.php">Logout</a>
</div>

  </header>

  <main>
    <div class="countdown-wrapper">
      <h3 class="countdown-title">When is your date?</h3>
      <input type="text" id="dateInput" class="countdown-input" placeholder="DD-MM-YYYY">
      <button id="countdownBtn" class="countdown-button">Time until your date</button>
      <div id="countdownDisplay" class="countdown-display"></div>
    </div>
  </main>

  <script>
  const burgerBtn = document.getElementById("burgerBtn");
  const menu = document.getElementById("menuDropdown");

  burgerBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    menu.classList.toggle("open");
  });

  document.addEventListener("click", () => {
    menu.classList.remove("open");
  });

  let countdownInterval = null;

  document.getElementById("countdownBtn").addEventListener("click", function() {  //när knappen klickas, startas nedräkningen
    const dateInput = document.getElementById("dateInput").value; //hämtar datumet från inputfältet
    const display = document.getElementById("countdownDisplay"); //hämtar elementet där nedräkningen ska visas

    const datePattern = /^(\d{2})-(\d{2})-(\d{4})$/; // för att validera datumformatet DD-MM-YYYY
    const match = dateInput.match(datePattern); //matchar det inskrivna datumet mot mönstret

    if (!match) {
      display.textContent = "Invalid format! Use DD-MM-YYYY"; // Om formatet är ogiltigt, visar det ett felmeddelande
      display.style.color = "#ffffff";
      return;
    }

    const day = parseInt(match[1]); // drar ut dag, månad och år från det inskrivna datumet
    const month = parseInt(match[2]) - 1; // javasript måndar är 0-indexerade, så vi det tas -1
    const year = parseInt(match[3]); // drar ut året

    const targetDate = new Date(year, month, day, 23, 59, 59); // skapar ett datumobjekt för det inskrivna datumet, sätter tiden till 23:59:59 för att räkna ned till slutet av dagen

    if (isNaN(targetDate.getTime())) { // Om det skapade datumet är ogiltigt, visar det ett felmeddelande
      display.textContent = "Invalid date!";
      display.style.color = "#ff4444";
      return;
    }

    if (countdownInterval) { // Om det redan finns en nedräkning igång, rensar den innan den startar en ny
      clearInterval(countdownInterval);
    }

    countdownInterval = setInterval(function() { // Startar en nedräkning som uppdateras varje sekund
      const now = new Date().getTime();
      const distance = targetDate - now;

      if (distance < 0) { // När nedräkningen är slut, visar den ett meddelande och stoppar nedräkningen
        clearInterval(countdownInterval);
        display.textContent = "The date has passed!";
        display.style.color = "#ff4444";
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      display.textContent = days + "d " + hours + "h " + minutes + "m " + seconds + "s"; // Uppdaterar nedräkningen i display-elementet
      display.style.color = "#ffffff";
    }, 1000);
  });
</script>

</body>
</html>