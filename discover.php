<?php
session_start();
require_once "db.php";

if (empty($_SESSION["logged_in"])) {
  header("Location: index.php");
  exit;
}

$email = $_SESSION["email"] ?? "";

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header("Location: index.php");
  exit;
}

if (empty($user["onboarding_complete"])) {
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

  document.getElementById('countdownBtn').addEventListener('click', function() {
    const dateInput = document.getElementById('dateInput').value;
    const display = document.getElementById('countdownDisplay');

    const datePattern = /^(\d{2})-(\d{2})-(\d{4})$/;
    const match = dateInput.match(datePattern);

    if (!match) {
      display.textContent = 'Ogiltigt format! Använd DD-MM-YYYY';
      display.style.color = '#ffffff';
      return;
    }

    const day = parseInt(match[1]);
    const month = parseInt(match[2]) - 1;
    const year = parseInt(match[3]);

    const targetDate = new Date(year, month, day, 23, 59, 59);

    if (isNaN(targetDate.getTime())) {
      display.textContent = 'Ogiltigt datum!';
      display.style.color = '#ff4444';
      return;
    }

    if (countdownInterval) {
      clearInterval(countdownInterval);
    }

    countdownInterval = setInterval(function() {
      const now = new Date().getTime();
      const distance = targetDate - now;

      if (distance < 0) {
        clearInterval(countdownInterval);
        display.textContent = 'Datumet har passerat!';
        display.style.color = '#ff4444';
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      display.textContent = days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's';
      display.style.color = '#ffffff';
    }, 1000);
  });
</script>

</body>
</html>