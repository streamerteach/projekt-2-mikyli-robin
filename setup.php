<?php
session_start();
require_once "db.php";

if (empty($_SESSION["logged_in"]) || empty($_SESSION["email"])) {
  header("Location: index.php");
  exit;
}

$email = $_SESSION["email"];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header("Location: index.php");
  exit;
}

$step = (int)($_GET["step"] ?? 1);
if ($step < 1 || $step > 3) $step = 1;

$error = "";

/* ---------- helpers ---------- */
function ensure_upload_dir($dir) {
  if (!is_dir($dir)) mkdir($dir, 0755, true);
}

function handle_profile_upload($inputName, $uploadDir, &$error) {
  if (!isset($_FILES[$inputName]) || $_FILES[$inputName]["error"] !== UPLOAD_ERR_OK) {
    $error = "Please upload a profile picture.";
    return null;
  }

  $tmp = $_FILES[$inputName]["tmp_name"];
  $size = (int)$_FILES[$inputName]["size"];

  // Basic size limit: 5MB
  if ($size <= 0 || $size > 5 * 1024 * 1024) {
    $error = "Profile picture must be under 5MB.";
    return null;
  }

  // Verify it's an image
  $imgInfo = @getimagesize($tmp);
  if ($imgInfo === false) {
    $error = "Uploaded file is not a valid image.";
    return null;
  }

  // Allow only jpeg/png/webp
  $mime = $imgInfo["mime"] ?? "";
  $allowed = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp",
  ];
  if (!isset($allowed[$mime])) {
    $error = "Only JPG, PNG, or WebP images are allowed.";
    return null;
  }

  ensure_upload_dir($uploadDir);

  $ext = $allowed[$mime];
  $name = "pfp_" . bin2hex(random_bytes(8)) . "." . $ext;
  $target = rtrim($uploadDir, "/") . "/" . $name;

  if (!move_uploaded_file($tmp, $target)) {
    $error = "Could not save the uploaded image. Try again.";
    return null;
  }

  // Return relative path for storing in database
  return "uploads/" . $name;
}

/* ---------- handle POST per step ---------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  if ($step === 1) {
    $display_name = trim($_POST["display_name"] ?? "");
    $city = trim($_POST["city"] ?? "");

    if ($display_name === "") {
      $error = "Display name is required.";
    } else {
      $uploadPath = handle_profile_upload("profile_picture", __DIR__ . "/uploads", $error);

      if ($uploadPath) {
        $stmt = $conn->prepare("
          UPDATE users
          SET display_name = ?, city = ?, profile_picture = ?
          WHERE email = ?
        ");

        $stmt->execute([
          $display_name,
          $city,
          $uploadPath,
          $email
        ]);

        header("Location: setup.php?step=2");
        exit;
      }
    }
  }

  if ($step === 2) {
    $looking_for = $_POST["looking_for"] ?? "";
    $date_pref   = $_POST["first_date_pref"] ?? "";

    $allowedLooking = ["friends", "networking", "relationship"];
    if (!in_array($looking_for, $allowedLooking, true)) {
      $error = "Please choose what you're looking for.";
    } else {
      if ($looking_for === "relationship") {
        $allowedPref = ["coffee_walk", "dinner_date"];
        if (!in_array($date_pref, $allowedPref, true)) {
          $error = "Please choose your first date preference.";
        }
      }

      if ($error === "") {
        if ($looking_for === "relationship") {
          $stmt = $conn->prepare("
            UPDATE users
            SET looking_for = ?, first_date_pref = ?
            WHERE email = ?
          ");

          $stmt->execute([
            $looking_for,
            $date_pref,
            $email
          ]);
        } else {
          $stmt = $conn->prepare("
            UPDATE users
            SET looking_for = ?, first_date_pref = NULL
            WHERE email = ?
          ");

          $stmt->execute([
            $looking_for,
            $email
          ]);
        }

        header("Location: setup.php?step=3");
        exit;
      }
    }
  }

  if ($step === 3) {
    $bio = trim($_POST["bio"] ?? "");

    $stmt = $conn->prepare("
      UPDATE users
      SET bio = ?, onboarding_complete = 1
      WHERE email = ?
    ");

    $stmt->execute([
      $bio,
      $email
    ]);

    header("Location: home.php");
    exit;
  }
}

/* ---------- pull latest user values for prefills ---------- */
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$display_name = $user["display_name"] ?? "";
$city = $user["city"] ?? "";
$looking_for = $user["looking_for"] ?? "";
$first_date_pref = $user["first_date_pref"] ?? "";
$bio = $user["bio"] ?? "";

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup</title>

  <link rel="stylesheet" href="styles.css">

  <style>
    .notice{
      padding: 12px 14px;
      border-radius: 10px;
      margin: 14px 0 18px;
      text-align: left;
      line-height: 1.35;
      background: rgba(0,0,0,0.25);
      border: 1px solid rgba(255,90,90,0.45);
      color: #fff;
    }
    .stepHint{
      color:#ccc;
      margin-bottom: 18px;
      font-size: 0.95rem;
    }
    label{
      display:block;
      text-align:left;
      margin: 10px 0 6px;
      color:#ddd;
      font-size: 0.95rem;
    }
    .login-form select,
    .login-form textarea{
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 1rem;
      font-family: Arial, sans-serif;
      resize: vertical;
    }
    .row{ display:flex; gap:10px; }
    .row > *{ flex:1; }
    .preview{
      width: 92px; height: 92px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.25);
      background: rgba(255,255,255,0.06);
      overflow:hidden;
      display:grid;
      place-items:center;
      margin: 6px auto 14px;
    }
    .preview img{ width:100%; height:100%; object-fit:cover; display:block; }
    .muted{ color:#cfcfcf; font-size: 0.9rem; margin-top:-6px; margin-bottom: 12px; }
    .hidden{ display:none; }
  </style>
</head>

<body style="background-image: url('images/VCbackground.png');">
  <div class="login-container">
    <div class="login-form">

      <img src="images/VCLogoTransparent.png" alt="VerifiedCircle Logo" class="vc-logo">
      <h2>Setup (Step <?= (int)$step ?> of 3)</h2>
      <div class="stepHint">Finish your profile to start using VerifiedCircle.</div>

      <?php if ($error): ?>
        <div class="notice"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($step === 1): ?>
        <form method="POST" enctype="multipart/form-data">
          <div class="preview">
            <?php if (!empty($user["profile_picture"])): ?>
              <img src="<?= htmlspecialchars($user["profile_picture"]) ?>" alt="Profile preview">
            <?php else: ?>
              <span style="opacity:.85;">+</span>
            <?php endif; ?>
          </div>

          <label for="profile_picture">Profile picture (required)</label>
          <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required>

          <div class="muted">JPG, PNG, WebP. Max 5MB.</div>

          <input name="display_name" placeholder="Display name" value="<?= htmlspecialchars($display_name) ?>" required>
          <input name="city" placeholder="City" value="<?= htmlspecialchars($city) ?>">

          <button class="login-button" type="submit">Continue</button>
        </form>

      <?php elseif ($step === 2): ?>
        <form method="POST" id="step2Form">
          <label for="looking_for">Looking for</label>
          <select name="looking_for" id="looking_for" required>
            <option value="">Select…</option>
            <option value="friends" <?= $looking_for==="friends" ? "selected" : "" ?>>Friends</option>
            <option value="networking" <?= $looking_for==="networking" ? "selected" : "" ?>>Networking</option>
            <option value="relationship" <?= $looking_for==="relationship" ? "selected" : "" ?>>Relationship</option>
          </select>

          <div id="datePrefWrap" class="<?= $looking_for==="relationship" ? "" : "hidden" ?>">
            <label for="first_date_pref">First date preference</label>
            <select name="first_date_pref" id="first_date_pref">
              <option value="">Select…</option>
              <option value="coffee_walk" <?= $first_date_pref==="coffee_walk" ? "selected" : "" ?>>Coffee / Walk</option>
              <option value="dinner_date" <?= $first_date_pref==="dinner_date" ? "selected" : "" ?>>Dinner date</option>
            </select>
          </div>

          <button class="login-button" type="submit">Continue</button>
        </form>

        <script>
          (function(){
            const lf = document.getElementById("looking_for");
            const wrap = document.getElementById("datePrefWrap");
            const pref = document.getElementById("first_date_pref");

            function sync(){
              const isRel = lf.value === "relationship";
              wrap.classList.toggle("hidden", !isRel);
              pref.required = isRel;
              if (!isRel) pref.value = "";
            }

            lf.addEventListener("change", sync);
            sync();
          })();
        </script>

      <?php elseif ($step === 3): ?>
        <form method="POST">
          <label for="bio">Bio</label>
          <textarea name="bio" id="bio" placeholder="Short bio (optional)" rows="5"><?= htmlspecialchars($bio) ?></textarea>

          <button class="login-button" type="submit">Finish</button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</body>
</html>