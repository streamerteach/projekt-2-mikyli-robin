<?php
session_start();
if (empty($_SESSION["logged_in"]) || empty($_SESSION["email"])) {
  header("Location: index.php");
  exit;
}

$file = __DIR__ . "/users.json";
$users = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($users)) $users = [];

$email = $_SESSION["email"];

if (empty($users[$email]["onboarding_complete"])) {
  header("Location: setup.php");
  exit;
}

/* ===== pick ONE candidate (keep it minimal) ===== */
$candidateEmail = null;
$candidate = null;

foreach ($users as $uEmail => $uData) {
  if ($uEmail === $email) continue;
  if (empty($uData["onboarding_complete"])) continue;

  $candidateEmail = $uEmail;
  $candidate = is_array($uData) ? $uData : [];
  break;
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

<!-- Hamburger dropdown -->
<div class="menuDropdown" id="menuDropdown" aria-label="User menu">
  <a class="menuItem" href="setup.php">Setup</a>
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
      No other profiles available.
    </div>
  <?php else: ?>
    <!-- OPTIONAL: show name overlay (doesn't change layout) -->
    <div style="position:absolute;left:22px;top:18px;font-weight:800;font-size:22px;text-shadow:0 6px 18px rgba(0,0,0,.35);">
      <?= htmlspecialchars($candidate["name"] ?? $candidateEmail) ?>
    </div>
  <?php endif; ?>

  <div class="dotsRow" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span><span></span>
  </div>
</div>

      </section>

      <!-- RIGHT STACKED PANEL -->
      <section class="panel panelRight">
  <?php
    $cName = $candidate["name"] ?? "";
    $cBio  = $candidate["bio"] ?? "";
    $cAge  = $candidate["age"] ?? "";
    $cCity = $candidate["city"] ?? "";
  ?>

  <div class="box">
    <div style="padding:18px;font-size:22px;font-weight:800;">
      <?= htmlspecialchars($cName) ?>
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

  <div class="box">
    <div style="padding:18px;font-size:14px;opacity:.85;">
      <?= htmlspecialchars($candidateEmail ?? "") ?>
    </div>
  </div>
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

</body>
</html>
