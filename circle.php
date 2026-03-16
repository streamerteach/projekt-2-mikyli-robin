<?php
session_start();
require_once "db.php";

if (empty($_SESSION["logged_in"])) {
  header("Location: index.php");
  exit;
}

$me = $_SESSION["email"] ?? "";

// Load current user
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$me]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
  header("Location: index.php");
  exit;
}

// Require onboarding complete
if (empty($currentUser["onboarding_complete"])) {
  header("Location: setup.php");
  exit;
}

// Load matched users in one query
$stmt = $conn->prepare("
  SELECT u.*
  FROM users u
  INNER JOIN matches m
    ON (
      (m.user1_email = ? AND m.user2_email = u.email)
      OR
      (m.user2_email = ? AND m.user1_email = u.email)
    )
  ORDER BY u.display_name, u.fullName, u.email
");
$stmt->execute([$me, $me]);
$myMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VerifiedCircle - Circle</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body id="home" style="background-image: url('images/VCbackground.png');">

<header>
  <div class="avatar" aria-label="Profile"></div>

  <div class="titleWrap">
    <div class="title">VerifiedCircle</div>
    <nav class="nav" aria-label="Main Navigation">
      <a href="home.php">Home</a>
      <a href="circle.php" class="active">Circle</a>
      <a href="discover.php">Discover</a>
      <a href="profile.php">Profile</a>
    </nav>
  </div>

  <div class="burger" aria-label="Menu" id="burgerBtn">
    <div class="burgerLines">
      <span></span><span></span><span></span>
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
  <div class="layout" style="grid-template-columns: 1fr; max-width: 1100px;">
    <section class="panel" style="padding: 22px;">
      <h2 style="margin-bottom: 14px;">Your matches</h2>

      <?php if (count($myMatches) === 0): ?>
        <p style="opacity:0.85;">No matches yet. Test: like each other from two accounts.</p>
      <?php else: ?>
        <div class="matchGrid">
          <?php foreach ($myMatches as $u):
            $matchEmail = $u["email"] ?? "";
            $name = $u["display_name"] ?? $u["fullName"] ?? $matchEmail;
            $photo = $u["profile_picture"] ?? "";
          ?>
            <div class="matchCard">
              <div class="matchPhoto" style="<?php echo $photo ? "background-image:url('".htmlspecialchars($photo)."');" : ""; ?>"></div>
              <div class="matchName"><?php echo htmlspecialchars($name); ?></div>
              <div class="matchMeta"><?php echo htmlspecialchars($matchEmail); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </section>
  </div>
</main>

<script>
  const burgerBtn = document.getElementById("burgerBtn");
  const menu = document.getElementById("menuDropdown");

  if (burgerBtn && menu) {
    burgerBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      menu.classList.toggle("open");
    });

    document.addEventListener("click", () => {
      menu.classList.remove("open");
    });
  }
</script>

</body>
</html>