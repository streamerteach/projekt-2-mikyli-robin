<?php
// Startar en session för att ha koll på användarens inloggningsstatus
session_start();

// Om användaren inte är inloggad, omdirigera den till inloggningssidan
if (empty($_SESSION["logged_in"])) {
  header("Location: index.php");
  exit;
}

// Laddar användardatan från en JSON-fil
$file = __DIR__ . "/users.json";
$users = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($users)) $users = [];

//Hämtar den inloggade användarens e-postadress
$email = $_SESSION["email"] ?? "";

//Kontrollerar om användaren har slutfört onboarding, annars omdirigera den till setup-sidan
if (empty($users[$email]["onboarding_complete"])) {
  header("Location: setup.php"); 
  exit;
}
//Inkluderar besöksräknaren
include_once __DIR__ . "/visitor_counter.php";

// Hanterar besök och visar välkomstmeddelande
$visitorData = handleVisitor($email, $users);
/*echo "Välkommen, " . $visitorData['full_name'] . "! Ditt senaste besök var: " . $visitorData['last_visit'] . "."; */ 

// Fetchar userns data
$user = $users[$email];
// Prefer the fields written by settings.php (top-level), fall back to older nested keys
$fullname = $user['display_name'] ?? $user['fullname'] ?? 'N/A';
$birthdate = $user['birthdate'] ?? 'N/A';
$profile = 'images/default-profile.png';
if (!empty($user['profile']['profile_picture'])) {
  $profile = $user['profile']['profile_picture'];
} elseif (!empty($user['profile_picture'])) {
  $profile = $user['profile_picture'];
}
$city = $user['city'] ?? ($user['profile']['city'] ?? 'N/A');
$lookingFor = $user['looking_for'] ?? ($user['preferences']['looking_for'] ?? 'N/A');
$ageMin = $user['age_min'] ?? ($user['preferences']['age_min'] ?? 'N/A');
$ageMax = $user['age_max'] ?? ($user['preferences']['age_max'] ?? 'N/A');
$bio = $user['bio'] ?? ($user['profile']['bio'] ?? 'N/A');

// First date preference (top-level or nested). Map to a readable label.
$firstDatePrefKey = $user['first_date_pref'] ?? ($user['preferences']['first_date_pref'] ?? '');
$firstDatePref = '';
if (is_string($firstDatePrefKey) && $firstDatePrefKey !== '') {
  $map = [
    'coffee_walk' => 'Coffee / Walk',
    'dinner_date' => 'Dinner date',
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