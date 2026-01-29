<?php
session_start();
if (empty($_SESSION["logged_in"])) {
  header("Location: index.php");
  exit;
}

$file = __DIR__ . "/users.json";
$users = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($users)) $users = [];

$email = $_SESSION["email"] ?? "";

if (empty($users[$email]["onboarding_complete"])) {
  header("Location: setup.php"); // changed from onboarding.php to setup.php
  exit;
}

// Fetchar userns data
$user = $users[$email];
$fullname = $user['fullname'] ?? 'N/A';
$birthdate = $user['birthdate'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VerifiedCircle</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body id="home">

  <header>
    <div class="avatar" aria-label="Profile"></div>

    <div class="titleWrap">
      <div class="title">VerifiedCircle</div>
        <nav class="nav" aria-label="Main Navigation">
        <a href="home.php">Home</a>
        <a href="circle.php">Circle</a>
        <a href="discover.php">Discover</a>
        <a href="profile.php" class="active">Profile</a>
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
  <a class="menuItem logout" href="logout.php">Logout</a>
</div>

  </header>

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
</script>

<div id="countdown">
  <h2>Time to find your valentine's date</h2>
  <p id="timer"></p>
</div>

<script>
  // Sets the date for Valentine's Day
  const valentineDate = new Date(new Date().getFullYear(), 1, 14, 0, 0, 0).getTime();

  // Updates the countdown every second
  const countdownInterval = setInterval(() => {
    const now = new Date().getTime();
    const timeLeft = valentineDate - now;

    // Calculates the days, hours, minutes, and seconds
    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

    // Display the result
    document.getElementById("timer").innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;

    // When the countdown is over the message is displayed
    if (timeLeft < 0) {
      clearInterval(countdownInterval);
      document.getElementById("timer").innerHTML = "Hopefully you found someone special!";
    }
  }, 1000);
</script>

<div class="profile-box">
  <h2>Profile Information</h2>
  <p><strong>Full Name:</strong> <?php echo htmlspecialchars($fullname); ?></p>
  <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($birthdate); ?></p>
</div>

</body>
</html>