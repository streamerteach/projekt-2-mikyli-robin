<?php
session_start();
require_once "db.php";

if (empty($_SESSION["logged_in"])) {
  header("Location: index.php");
  exit;
}

$email = $_SESSION["email"] ?? "";

/* load user from SQL */
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header("Location: index.php");
  exit;
}

/* onboarding check */
if (empty($user["onboarding_complete"])) {
  header("Location: setup.php");
  exit;
}

/* include visitor counter */
include_once __DIR__ . "/visitor_counter.php";

/* visitor handler */
$visitorData = handleVisitor($email, $user);

/* profile values */
$fullname = $user["display_name"] ?? $user["fullName"] ?? "N/A";
$birthdate = $user["birthdate"] ?? "N/A";

$profile = "images/default-profile.png";
if (!empty($user["profile_picture"])) {
  $profile = $user["profile_picture"];
}

$city = $user["city"] ?? "N/A";
$lookingFor = $user["looking_for"] ?? "N/A";
$ageMin = $user["age_min"] ?? "N/A";
$ageMax = $user["age_max"] ?? "N/A";
$bio = $user["bio"] ?? "N/A";

/* first date preference */
$firstDatePrefKey = $user["first_date_pref"] ?? "";
$firstDatePref = "";

if ($firstDatePrefKey !== "") {
  $map = [
    "coffee_walk" => "Coffee / Walk",
    "dinner_date" => "Dinner date"
  ];

  $firstDatePref = $map[$firstDatePrefKey] ?? $firstDatePrefKey;
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

<body id="home" style = "background-image: url('images/VCbackground.png');">

  <header>
    <!--navigationsmeny -->
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
  <a class="menuItem" href="settings.php">Settings</a>
  <a class="menuItem" href="reviews.php">Leave a Review</a>
  <a class="menuItem" href="rapporten.html">Rapporten</a>
  <a class="menuItem logout" href="logout.php">Logout</a>
</div>

  </header>

  <script>
  // Hanterar menyknappen för att visa/dölja menyn
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
  <h2>Time to find your valentine's date:</h2>
  <p id="timer"></p>
</div>

<script>
  //sätter datumet för valentines day
  const valentineDate = new Date(new Date().getFullYear(), 1, 14, 0, 0, 0).getTime();

  // Uppdaterar nedräkningen varje sekund
  const countdownInterval = setInterval(() => {
    const now = new Date().getTime();
    const timeLeft = valentineDate - now;

    // Räknar dagar, timmar, minuter och sekunder kvar
    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

    //Visar resultatet
    document.getElementById("timer").innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;

    //När räkningen är slut visas ett meddelande
    if (timeLeft < 0) {
      clearInterval(countdownInterval);
      document.getElementById("timer").innerHTML = "Hopefully you found someone special!";
    }
  }, 1000);
</script>

<div class="profile-box">
  <h2>Profile Information</h2>
  <!-- Visar användarens profilinformation -->
  <img src="<?php echo htmlspecialchars($profile); ?>" alt="Profile Picture" style="width:150px;height:150px;border-radius:50%;"><br>
  <p><strong>Full Name:</strong> <?php echo htmlspecialchars($fullname); ?></p>
  <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($birthdate); ?></p>
  <p><strong>City:</strong> <?php echo htmlspecialchars($city); ?></p>
  <p><strong>Looking for:</strong> <?php echo htmlspecialchars($lookingFor); ?></p>
  <?php if ($lookingFor === 'relationship' && $firstDatePref): ?>
    <p><strong>First date preference:</strong> <?php echo htmlspecialchars($firstDatePref); ?></p>
  <?php endif; ?>
  <p><strong>Age range:</strong> <?php echo htmlspecialchars($ageMin); ?> to <?php echo htmlspecialchars($ageMax); ?></p>
  <p><strong>Short bio:</strong> <?php echo nl2br(htmlspecialchars($bio)); ?></p>
</div>

</body>
</html>