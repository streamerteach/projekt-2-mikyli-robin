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

$allowedLookingFor = ["", "friends", "networking", "relationship"]; // definierar tillåtna värden för "looking for"
$allowedDatePrefs = ["", "coffee_walk", "dinner_date"]; // definierar tillåtna värden för "first date preference" filter

$lookingForFilter = $_GET["looking_for"] ?? ""; // hämtar filtervärdet för "looking for" från querry string
$firstDatePrefFilter = $_GET["first_date_pref"] ?? ""; // hämtar filtervärdet för "first date preference" från querry string

if (!in_array($lookingForFilter, $allowedLookingFor, true)) { //om värdet för "looking for" inte är i listan av tillåtna värden, sätts det till en tom sträng (ingen filter)
  $lookingForFilter = "";
}

if (!in_array($firstDatePrefFilter, $allowedDatePrefs, true)) { //om värdet för "first date preference" inte är i listan av tillåtna värden, sätts det till en tom sträng (ingen filter)
  $firstDatePrefFilter = "";
}

/* drar ut matchade användare baserat på filters med en SQL-fråga */
$sql = " 
  SELECT u.*
  FROM users u
  INNER JOIN matches m
    ON (
      (m.user1_email = ? AND m.user2_email = u.email)
      OR
      (m.user2_email = ? AND m.user1_email = u.email)
    )
  WHERE 1 = 1
";

$params = [$me, $me]; // parametrar för SQL-frågan, börjar med den inloggade användarens e-postadress två gånger (för user1_email och user2_email i matches-tabellen)

if ($lookingForFilter !== "") { // Om det finns ett filter för "looking for", lägg till det i SQL-frågan och i parametrarna
  $sql .= " AND u.looking_for = ?"; // lägger till en condition i SQL-frågan för att filtrera på "looking for"
  $params[] = $lookingForFilter; // lägger till filtervärdet för "looking for" i parametrarna
}

if ($firstDatePrefFilter !== "") { // Om det finns ett filter för "first date preference", lägg till det i SQL-frågan och i parametrarna
  $sql .= " AND u.first_date_pref = ?"; // lägger till en condition i SQL-frågan för att filtrera på "first date preference"
  $params[] = $firstDatePrefFilter; // lägger till filtervärdet för "first date preference" i parametrarna
}

$sql .= " ORDER BY u.display_name, u.fullName, u.email"; // sorterar resultaten i SQL-frågan efter display_name, fullName och email

function circle_label_looking_for($value) { // Funktion för att översätta "looking for" värden till mer användarvänliga etiketter
  $map = [
    "friends" => "Friends",
    "networking" => "Networking",
    "relationship" => "Relationship"
  ];

  return $map[$value] ?? "Not set"; // Om värdet inte finns i mappen, returnera "Not set"
}

function circle_label_date_pref($value) { // Funktion för att översätta "first date preference" värden till mer användarvänliga etiketter
  $map = [
    "coffee_walk" => "Coffee / Walk",
    "dinner_date" => "Dinner date"
  ];

  return $map[$value] ?? "Not set"; // Om värdet inte finns i mappen, returnera "Not set"
}

// Laddar matchade användare baserat på den byggda SQL-frågan och parametrarna
$stmt = $conn->prepare($sql);
$stmt->execute($params);
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

<body id="home" class="circlePage" style="background-image: url('images/VCbackground.png');">

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

<!--classerna-->
<main>
  <div class="layout circleLayout">
    <section class="panel circlePanel">
      <div class="circleHeaderRow">
        <h2 class="circleHeading">Your matches</h2>
        <span class="circleCount"><?php echo count($myMatches); ?> result<?php echo count($myMatches) === 1 ? "" : "s"; ?></span>
      </div>

      <form method="get" class="circleFilters">
        <div class="circleFilterRow">
          <div class="circleField">
            <label for="looking_for">Looking for</label>
            <select id="looking_for" name="looking_for">
              <option value="">All</option>
              <option value="friends" <?php echo $lookingForFilter === "friends" ? "selected" : ""; ?>>Friends</option>
              <option value="networking" <?php echo $lookingForFilter === "networking" ? "selected" : ""; ?>>Networking</option>
              <option value="relationship" <?php echo $lookingForFilter === "relationship" ? "selected" : ""; ?>>Relationship</option>
            </select>
          </div>

          <div class="circleField">
            <label for="first_date_pref">First date preference</label>
            <select id="first_date_pref" name="first_date_pref">
              <option value="">All</option>
              <option value="coffee_walk" <?php echo $firstDatePrefFilter === "coffee_walk" ? "selected" : ""; ?>>Coffee / Walk</option>
              <option value="dinner_date" <?php echo $firstDatePrefFilter === "dinner_date" ? "selected" : ""; ?>>Dinner date</option>
            </select>
          </div>
        </div>

        <div class="circleFilterActions">
          <button type="submit" class="circleApplyBtn">Apply filters</button>
          <a href="circle.php" class="circleResetLink">Reset</a>
        </div>
      </form>

      <?php if (count($myMatches) === 0): ?> <!-- Om det inte finns några matchningar som matchar filtren, visa ett meddelande -->
        <p class="circleEmpty">No matches matched your filters yet.</p>
      <?php else: ?>

        <div class="matchGrid"> <!-- Om det finns matchningar, visas dom i en grid -->
          <?php foreach ($myMatches as $u):
            $matchEmail = $u["email"] ?? "";
            $name = $u["display_name"] ?? $u["fullName"] ?? $matchEmail;
            $photo = $u["profile_picture"] ?? "";
            $lookingFor = circle_label_looking_for($u["looking_for"] ?? "");
            $firstDatePref = circle_label_date_pref($u["first_date_pref"] ?? "");
          ?>
            <div class="matchCard">
              <div class="matchPhoto" style="<?php echo $photo ? "background-image:url('".htmlspecialchars($photo)."');" : ""; ?>"></div>
              <div class="matchName"><?php echo htmlspecialchars($name); ?></div>
              <div class="matchMeta"><?php echo htmlspecialchars($matchEmail); ?></div>
              <div class="matchTagRow">

                <span class="matchTag"><?php echo htmlspecialchars($lookingFor); ?></span> <!-- Visar "looking for" som en tag -->
                <?php if ($firstDatePref !== ""): ?> <!-- Om "first date preference" är satt, visa det som en tag -->
                  <span class="matchTag"><?php echo htmlspecialchars($firstDatePref); ?></span> <!-- Visar "first date preference" som en tag -->
                <?php endif; ?> <!-- End if för "first date preference" tag -->
              </div>
            </div>
          <?php endforeach; ?> <!-- End foreach för matchningar -->
        </div>
      <?php endif; ?> <!-- End if för att kolla om det finns matchningar -->

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