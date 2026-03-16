<?php
session_start();
require_once "db.php";

if (empty($_SESSION["logged_in"]) || empty($_SESSION["email"])) {
  header("Location: index.php");
  exit;
}

$email = $_SESSION["email"];

// Load current user
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
  header("Location: index.php");
  exit;
}

if (empty($currentUser["onboarding_complete"])) {
  header("Location: setup.php");
  exit;
}

// Load other users as candidates
$stmt = $conn->prepare("
  SELECT * FROM users
  WHERE email != ?
    AND onboarding_complete = 1
");
$stmt->execute([$email]);

$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pick random candidate
$candidate = null;
$candidateEmail = null;

if (!empty($allUsers)) {
  shuffle($allUsers);
  $candidate = $allUsers[0];
  $candidateEmail = $candidate["email"];
}

// Normalize candidate fields
$primary = "";
$cName = "";
$cBio = "";
$cCity = "";
$cAge = "";
$cFirstDatePref = "";

if ($candidate) {
  $cName = $candidate["display_name"] ?? $candidate["fullName"] ?? "";
  $cBio  = $candidate["bio"] ?? "";
  $cCity = $candidate["city"] ?? "";

  // Calculate age from birthdate
  if (!empty($candidate["birthdate"])) {
    try {
      $birth = new DateTime($candidate["birthdate"]);
      $today = new DateTime();
      $cAge = $today->diff($birth)->y;
    } catch (Exception $e) {
      $cAge = "";
    }
  }

  // First date preference
  if (!empty($candidate["first_date_pref"])) {
    $map = [
      "coffee_walk" => "Coffee / Walk",
      "dinner_date" => "Dinner date"
    ];
    $cFirstDatePref = $map[$candidate["first_date_pref"]] ?? $candidate["first_date_pref"];
  }

  // Profile picture
  if (!empty($candidate["profile_picture"])) {
    $primary = $candidate["profile_picture"];
  }
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
        <a href="home.php" class="active">Home</a>
        <a href="circle.php">Circle</a>
        <a href="discover.php">Discover</a>
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

    <div class="menuDropdown" id="menuDropdown" aria-label="User menu">
      <a class="menuItem" href="settings.php">Settings</a>
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

    <div class="layout">

      <section class="panel panelLeft">
        <div class="profileCard">
          <?php if (!$candidateEmail): ?>
            <div style="height:100%;display:grid;place-items:center;text-align:center;padding:24px;opacity:.9;">
              <div>No other profiles available.</div>
              <div style="margin-top:12px;font-size:12px;opacity:0.9;text-align:left;max-width:420px;"></div>
            </div>
          <?php else: ?>
            <?php if ($primary): ?>
              <img src="<?= htmlspecialchars($primary) ?>" alt="Primary photo" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:12px;">
            <?php else: ?>
              <div style="height:100%;display:grid;place-items:center;text-align:center;padding:24px;opacity:.9;">No photo</div>
            <?php endif; ?>

            <div style="position:absolute;left:22px;top:18px;font-weight:800;font-size:22px;text-shadow:0 6px 18px rgba(0,0,0,.35);">
              <?= htmlspecialchars($cName ?: $candidateEmail) ?>
            </div>
          <?php endif; ?>

          <div class="dotsRow" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span><span></span>
          </div>
        </div>
      </section>

      <section class="panel panelRight">
        <div class="box">
          <div style="padding:18px;font-size:22px;font-weight:800;">
            <?= htmlspecialchars($cName ?: ($candidateEmail ?? "")) ?>
          </div>
        </div>

        <div class="box">
          <div style="padding:18px;font-size:16px;">
            <?= htmlspecialchars(trim(($cAge ? $cAge . " • " : "") . $cCity)) ?>
          </div>
        </div>

        <div class="box">
          <div style="padding:18px;font-size:15px;line-height:1.35;">
            <?= htmlspecialchars($cBio) ?>
          </div>
        </div>

        <?php if ($cFirstDatePref): ?>
          <div class="box">
            <div style="padding:18px;font-size:14px;opacity:.85;">
              <strong>First date:</strong> <?= htmlspecialchars($cFirstDatePref) ?>
            </div>
          </div>
        <?php endif; ?>
      </section>

    </div>
  </main>

  <?php if (!empty($candidateEmail)): ?>
    <form class="actions" method="post" action="action.php">
      <input type="hidden" name="candidate" value="<?= htmlspecialchars($candidateEmail) ?>">
      <button class="btn btnSkip" name="action" value="skip" type="submit">SKIP</button>
      <button class="btn btnConnect" name="action" value="like" type="submit">CONNECT</button>
    </form>
  <?php else: ?>
    <div class="actions">
      <button class="btn btnSkip" type="button" disabled>SKIP</button>
      <button class="btn btnConnect" type="button" disabled>CONNECT</button>
    </div>
  <?php endif; ?>

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

    document.getElementById("countdownBtn").addEventListener("click", function() {
      const dateInput = document.getElementById("dateInput").value;
      const display = document.getElementById("countdownDisplay");

      const datePattern = /^(\d{2})-(\d{2})-(\d{4})$/;
      const match = dateInput.match(datePattern);

      if (!match) {
        display.textContent = "Ogiltigt format! Använd DD-MM-YYYY";
        display.style.color = "#ffffff";
        return;
      }

      const day = parseInt(match[1], 10);
      const month = parseInt(match[2], 10) - 1;
      const year = parseInt(match[3], 10);

      const targetDate = new Date(year, month, day, 23, 59, 59);

      if (isNaN(targetDate.getTime())) {
        display.textContent = "Ogiltigt datum!";
        display.style.color = "#ff4444";
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
          display.textContent = "Datumet har passerat!";
          display.style.color = "#ff4444";
          return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        display.textContent = days + "d " + hours + "h " + minutes + "m " + seconds + "s";
        display.style.color = "#ffffff";
      }, 1000);
    });
  </script>

  <footer>
    <p>&copy; <?php echo date("Y"); ?> VerifiedCircle. All rights reserved.</p>
    <p>
      Welcome, <?= htmlspecialchars($currentUser["display_name"] ?? $currentUser["fullName"] ?? $currentUser["email"]) ?>!
    </p>
  </footer>

</body>
</html>