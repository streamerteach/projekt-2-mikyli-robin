<?php
session_start();

if (empty($_SESSION["logged_in"])) {
  header("Location: index.php");
  exit;
}

$file = __DIR__ . "/users.json";
$users = json_decode(file_get_contents($file), true);
$email = $_SESSION["email"];

if (!isset($users[$email])) {
  session_destroy();
  header("Location: index.php");
  exit;
}

$user = $users[$email];
$step = (int)($user["onboarding_step"] ?? 1);
$error = "";

// Handle submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  if ($step === 1) {
    $displayName = trim($_POST["display_name"] ?? "");
    $city = trim($_POST["city"] ?? "");

    if ($displayName === "") $error = "Display name is required.";
    else {
      $users[$email]["profile"]["display_name"] = $displayName;
      $users[$email]["profile"]["city"] = $city;
      $users[$email]["onboarding_step"] = 2;
    }
  }

  if ($step === 2 && $error === "") {
    $lookingFor = $_POST["looking_for"] ?? "";
    $ageMin = (int)($_POST["age_min"] ?? 18);
    $ageMax = (int)($_POST["age_max"] ?? 99);

    if ($ageMin < 18 || $ageMax < $ageMin) $error = "Check age range.";
    else {
      $users[$email]["preferences"]["looking_for"] = $lookingFor;
      $users[$email]["preferences"]["age_min"] = $ageMin;
      $users[$email]["preferences"]["age_max"] = $ageMax;
      $users[$email]["onboarding_step"] = 3;
    }
  }

  if ($step === 3 && $error === "") {
    $bio = trim($_POST["bio"] ?? "");
    $users[$email]["profile"]["bio"] = $bio;

    // Mark complete
    $users[$email]["onboarding_complete"] = true;
    $users[$email]["onboarding_step"] = 0;
  }

  if ($error === "") {
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));

    // If completed, go home
    if (!empty($users[$email]["onboarding_complete"])) {
      header("Location: home.php");
      exit;
    }

    // Otherwise reload to show next step
    header("Location: onboarding.php");
    exit;
  }
}

// Reload current step after possible changes
$users = json_decode(file_get_contents($file), true);
$user = $users[$email];
$step = (int)($user["onboarding_step"] ?? 1);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup</title>
</head>
<body>
  <h2>Setup (Step <?php echo $step; ?> of 3)</h2>

  <?php if ($error): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <?php if ($step === 1): ?>
    <form method="POST">
      <input name="display_name" placeholder="Display name" required><br><br>
      <input name="city" placeholder="City"><br><br>
      <button type="submit">Continue</button>
    </form>

  <?php elseif ($step === 2): ?>
    <form method="POST">
      <label>Looking for:</label><br>
      <select name="looking_for" required>
        <option value="">Select…</option>
        <option value="friends">Friends</option>
        <option value="dating">Dating</option>
        <option value="networking">Networking</option>
      </select><br><br>

      <label>Age range:</label><br>
      <input type="number" name="age_min" value="18" min="18" max="99">
      <input type="number" name="age_max" value="35" min="18" max="99"><br><br>

      <button type="submit">Continue</button>
    </form>

  <?php elseif ($step === 3): ?>
    <form method="POST">
      <textarea name="bio" placeholder="Short bio" rows="5" cols="40"></textarea><br><br>
      <button type="submit">Finish</button>
    </form>
  <?php endif; ?>
</body>
</html>
