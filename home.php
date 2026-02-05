<?php
// startar en session för att ha koll på användarens inloggningsstatus
session_start();

// Om användaren inte är inloggad eller saknar e-post, omdirigerar den en till inloggningssidan
if (empty($_SESSION["logged_in"]) || empty($_SESSION["email"])) {
  header("Location: index.php");
  exit;
}

//laddar användardatan från JSON-fil
$file = __DIR__ . "/users.json";
$users = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($users)) $users = [];

// Hämtar den inloggade användarens e-postadress
$email = $_SESSION["email"];

//kontrollerar om användaren har slutfört onboarding, annars omdirigerrar den till setup-sidan
if (empty($users[$email]["onboarding_complete"])) {
  header("Location: setup.php");
  exit;
}

// Inkluderar besöksräknaren
include_once __DIR__ . "/visitor_counter.php";

// Hanterar besök och visar antal unika besökare
$visitorData = handleVisitor($email, $users);

/* ===== pick ONE candidate (unseen first, otherwise cycle old) ===== */
$candidateEmail = null;
$candidate = null;

// load likes/matches/skips
$likesFile = __DIR__ . "/likes.json";
$matchesFile = __DIR__ . "/matches.json";
$skipsFile = __DIR__ . "/skips.json";

$likes = file_exists($likesFile) ? json_decode(file_get_contents($likesFile), true) : [];
$matches = file_exists($matchesFile) ? json_decode(file_get_contents($matchesFile), true) : [];
$skips = file_exists($skipsFile) ? json_decode(file_get_contents($skipsFile), true) : [];

// Build list of all candidates (excluding yourself, onboarding only)
$allCandidates = [];
foreach ($users as $uEmail => $uData) {
  if (strtolower($uEmail) === strtolower($email)) continue;
  if (empty($uData["onboarding_complete"])) continue;
  $allCandidates[] = $uEmail;
}

// Build seen set (case-insensitive)
$seen = [];
if (isset($likes[$email]) && is_array($likes[$email]))   $seen = array_merge($seen, array_values($likes[$email]));
if (isset($matches[$email]) && is_array($matches[$email])) $seen = array_merge($seen, array_values($matches[$email]));
if (isset($skips[$email]) && is_array($skips[$email]))   $seen = array_merge($seen, array_values($skips[$email]));
$seen[] = $email;

$seen_lc = array_map('strtolower', array_values($seen));

// Pick unseen first
$unseenCandidates = [];
foreach ($allCandidates as $uEmail) {
  if (!in_array(strtolower($uEmail), $seen_lc, true)) $unseenCandidates[] = $uEmail;
}

if (count($allCandidates) === 0) {
  $candidateEmail = null;
  $candidate = null;
} elseif (count($unseenCandidates) > 0) {
  // random unseen
  $candidateEmail = $unseenCandidates[array_rand($unseenCandidates)];
  $candidate = is_array($users[$candidateEmail] ?? null) ? $users[$candidateEmail] : [];
} else {
  // no unseen left -> cycle old profiles
  $idx = ($_SESSION['cycle_idx'] ?? 0) % count($allCandidates);
  $candidateEmail = $allCandidates[$idx];
  $candidate = is_array($users[$candidateEmail] ?? null) ? $users[$candidateEmail] : [];
}


// Normalize candidate fields so the template can use consistent names
$primary = "";
$cName = "";
$cBio = "";
$cCity = "";
$cAge = "";
$cFirstDatePref = "";
if ($candidate) {
  $cName = $candidate['display_name'] ?? $candidate['fullname'] ?? $candidate['name'] ?? '';
  $cBio  = $candidate['bio'] ?? ($candidate['profile']['bio'] ?? '');
  $cCity = $candidate['city'] ?? ($candidate['profile']['city'] ?? '');

  // Age: prefer explicit `age`, otherwise compute from `birthdate` if available
  if (!empty($candidate['age'])) {
    $cAge = (int)$candidate['age'];
  } elseif (!empty($candidate['birthdate'])) {
    $bd = $candidate['birthdate'];
    // Try to parse YYYY or YYYY-MM-DD
    $year = null;
    if (preg_match('/^(\\d{4})/', $bd, $m)) $year = (int)$m[1];
    if ($year) $cAge = max(0, (int)date('Y') - $year);
  }

  // First date preference
  $firstDatePrefKey = $candidate['first_date_pref'] ?? ($candidate['preferences']['first_date_pref'] ?? '');
  if (is_string($firstDatePrefKey) && $firstDatePrefKey !== '') {
    $map = [
      'coffee_walk' => 'Coffee / Walk',
      'dinner_date' => 'Dinner date',
    ];
    $cFirstDatePref = $map[$firstDatePrefKey] ?? $firstDatePrefKey;
  }

  // Primary photo: prefer photos[0], then profile_picture, then nested profile
  if (!empty($candidate['photos']) && is_array($candidate['photos']) && !empty($candidate['photos'][0])) {
    $primary = $candidate['photos'][0];
  } elseif (!empty($candidate['profile_picture'])) {
    $primary = $candidate['profile_picture'];
  } elseif (!empty($candidate['profile']['profile_picture'])) {
    $primary = $candidate['profile']['profile_picture'];
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
    <!-- COUNTDOWN SECTIONIONEN -->
    <div class="countdown-wrapper">
      <h3 class="countdown-title">When is your date?</h3>
      <input type="text" id="dateInput" class="countdown-input" placeholder="DD-MM-YYYY">
      <button id="countdownBtn" class="countdown-button">Time until your date</button>
      <div id="countdownDisplay" class="countdown-display"></div>
    </div>

    <div class="layout">

      <!-- LEFT BIG PANEL -->
      <section class="panel panelLeft">
        <div class="profileCard">
  <?php if (!$candidateEmail): ?>
    <div style="height:100%;display:grid;place-items:center;text-align:center;padding:24px;opacity:.9;">
      <div>No other profiles available.</div>
      <div style="margin-top:12px;font-size:12px;opacity:0.9;text-align:left;max-width:420px;">
        <strong>Debug:</strong>
        <div>Seen (lowercased):
          <pre style="white-space:pre-wrap;background:rgba(0,0,0,0.12);padding:8px;border-radius:6px;"> <?= htmlspecialchars(json_encode($seen_lc, JSON_PRETTY_PRINT)) ?></pre>
        </div>
        <div>Users in users.json (keys):
          <pre style="white-space:pre-wrap;background:rgba(0,0,0,0.06);padding:8px;border-radius:6px;"> <?= htmlspecialchars(json_encode(array_keys($users), JSON_PRETTY_PRINT)) ?></pre>
        </div>
        <div>Available candidates (onboarding_complete & not seen):
          <pre style="white-space:pre-wrap;background:rgba(0,0,0,0.06);padding:8px;border-radius:6px;"> <?php
            $avail = [];
            foreach ($users as $uE => $uD) {
              if (!empty($uD['onboarding_complete']) && !in_array(strtolower($uE), $seen_lc, true)) $avail[] = $uE;
            }
            echo htmlspecialchars(json_encode($avail, JSON_PRETTY_PRINT));
          ?>
          </pre>
        </div>
      </div>
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

  // Countdown functionaliteten
  let countdownInterval = null;

  document.getElementById('countdownBtn').addEventListener('click', function() {
    const dateInput = document.getElementById('dateInput').value;
    const display = document.getElementById('countdownDisplay');
    
    // Validerar formatet DD-MM-YYYY
    const datePattern = /^(\d{2})-(\d{2})-(\d{4})$/;
    const match = dateInput.match(datePattern);
    
    if (!match) {
      display.textContent = 'Ogiltigt format! Använd DD-MM-YYYY';
      display.style.color = '#ffffff';
      return;
    }
    
    const day = parseInt(match[1]);
    const month = parseInt(match[2]) - 1; // Månader är 0-indexerade
    const year = parseInt(match[3]);
    
    const targetDate = new Date(year, month, day, 23, 59, 59);
    
    // Kollar om datumet är giltigt
    if (isNaN(targetDate.getTime())) {
      display.textContent = 'Ogiltigt datum!';
      display.style.color = '#ff4444';
      return;
    }
    
    // Rensar tidigare interval
    if (countdownInterval) {
      clearInterval(countdownInterval);
    }
    
    // Uppdaterar countdown varje sekund
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

<footer>
  <p>&copy; <?php echo date("Y"); ?> VerifiedCircle. All rights reserved.</p>
    <?php
    $visitorData = handleVisitor($email, $users);
    echo "Site Visitors: " . $visitorData['unique_visitors'];
    ?><br>
    <?php
    echo "Welcome, " . $visitorData['full_name'] . "! Your last visit was: " . $visitorData['last_visit'] . ".";
    ?>
</footer>

</body>
</html>
