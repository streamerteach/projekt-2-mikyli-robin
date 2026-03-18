<?php
session_start();
require_once "db.php";
include_once __DIR__ . "/visitor_counter.php";

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

if (count($allUsers) > 0) {
  $candidate = $allUsers[array_rand($allUsers)];
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

  $cName = $candidate['display_name'] ?? $candidate['fullName'] ?? '';
  $cBio  = $candidate['bio'] ?? '';
  $cCity = $candidate['city'] ?? '';

  // Calculate age from birthdate
  if (!empty($candidate['birthdate'])) {
    $birth = new DateTime($candidate['birthdate']);
    $today = new DateTime();
    $cAge = $today->diff($birth)->y;
  }

  // First date preference
  if (!empty($candidate['first_date_pref'])) {
    $map = [
      'coffee_walk' => 'Coffee / Walk',
      'dinner_date' => 'Dinner date'
    ];
    $cFirstDatePref = $map[$candidate['first_date_pref']] ?? $candidate['first_date_pref'];
  }

  // Profile picture
  if (!empty($candidate['profile_picture'])) {
    $primary = $candidate['profile_picture'];
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
    <!-- navigationsmeny -->
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

<!-- Hamburger dropdown -->
<div class="menuDropdown" id="menuDropdown" aria-label="User menu">
  <a class="menuItem" href="settings.php">Settings</a>
  <a class="menuItem" href="reviews.php">Leave a Review</a>
  <a class="menuItem" href="rapporten.html">Rapporten</a>
  <a class="menuItem logout" href="logout.php">Logout</a>
</div>

  </header>

  <main>
    <div class="layout">

      <!-- LEFT BIG PANEL -->
      <section class="panel panelLeft">
        <div class="profileCard">
  <?php if (!$candidateEmail): ?>
    <div style="height:100%;display:grid;place-items:center;text-align:center;padding:24px;opacity:.9;">
      <div>No other profiles available.</div>
              <div style="margin-top:12px;font-size:12px;opacity:0.9;text-align:left;max-width:420px;"></div>
    </div>
  <?php else: ?>
    <!-- show primary photo -->
    <?php if ($primary): ?>
      <img src="<?= htmlspecialchars($primary) ?>" alt="Primary photo" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:12px;">
    <?php else: ?>
      <div style="height:100%;display:grid;place-items:center;text-align:center;padding:24px;opacity:.9;">No photo</div>
    <?php endif; ?>
    <!-- name overlay -->
    <div style="position:absolute;left:22px;top:18px;font-weight:800;font-size:22px;text-shadow:0 6px 18px rgba(0,0,0,.35);">
      <?= htmlspecialchars($cName ?: $candidateEmail) ?>
    </div>
  <?php endif; ?>

  <div class="dotsRow" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span><span></span>
  </div>
</div>

      </section>

      <!-- RIGHT STACKED PANEL -->
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
</script>

<footer>
  <p>&copy; <?php echo date("Y"); ?> VerifiedCircle. All rights reserved.</p>
    <?php
    $visitorData = handleVisitor($email, $currentUser);
    echo "Site Visitors: " . $visitorData['unique_visitors'];
    ?><br>
    <?php
    echo "Welcome, " . $visitorData['full_name'] . "! Your last visit was: " . $visitorData['last_visit'] . ".";
    ?>
</footer>

</body>
</html>