<?php
session_start();
require_once "db.php";

/* auth gate */
if (empty($_SESSION["logged_in"]) || empty($_SESSION["email"])) {
  header("Location: index.php");
  exit;
}

$email = $_SESSION["email"];
$error = "";
$success = "";
$deleteError = "";

/* ensure uploads folder exists */
function ensure_upload_dir($dir) {
  if (!is_dir($dir)) mkdir($dir, 0755, true);
}

/* basic path safety: only allow uploads/... */
function is_safe_upload_path($path) {
  return is_string($path) && substr($path, 0, 8) === "uploads/";
}

/* delete only if path is safe */
function delete_upload_file_if_safe($relativePath) {
  if (!is_safe_upload_path($relativePath)) return;
  $abs = __DIR__ . "/" . $relativePath;
  if (is_file($abs)) @unlink($abs);
}

/* handle one image upload returns relative path like uploads/photo_xxx.jpg */
function handle_image_upload($fileArr, $uploadDirAbs, &$error) {
  if (!isset($fileArr) || !isset($fileArr["error"])) return null;
  if ($fileArr["error"] === UPLOAD_ERR_NO_FILE) return null;

  if ($fileArr["error"] !== UPLOAD_ERR_OK) {
    $error = "Upload failed. Try again.";
    return null;
  }

  $tmp  = $fileArr["tmp_name"];
  $size = (int)$fileArr["size"];

  if ($size <= 0 || $size > 5 * 1024 * 1024) {
    $error = "Image must be under 5MB.";
    return null;
  }

  $imgInfo = @getimagesize($tmp);
  if ($imgInfo === false) {
    $error = "Uploaded file is not a valid image.";
    return null;
  }

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

  ensure_upload_dir($uploadDirAbs);

  $ext = $allowed[$mime];
  $name = "photo_" . bin2hex(random_bytes(8)) . "." . $ext;
  $targetAbs = rtrim($uploadDirAbs, "/") . "/" . $name;

  if (!move_uploaded_file($tmp, $targetAbs)) {
    $error = "Could not save the uploaded image. Try again.";
    return null;
  }

  return "uploads/" . $name;
}

/* load current user */
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header("Location: index.php");
  exit;
}

/* =========================
   POST: save profile fields
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "save_profile") === "save_profile") {

  $display_name = trim($_POST["display_name"] ?? "");
  $city = trim($_POST["city"] ?? "");
  $bio = trim($_POST["bio"] ?? "");
  $looking_for = $_POST["looking_for"] ?? "";
  $first_date_pref = $_POST["first_date_pref"] ?? "";

  if ($display_name === "") {
    $error = "Display name is required.";
  }

  $allowedLooking = ["friends", "networking", "relationship"];
  if (!$error && !in_array($looking_for, $allowedLooking, true)) {
    $error = "Please choose what you're looking for.";
  }

  if (!$error && $looking_for === "relationship") {
    $allowedPref = ["coffee_walk", "dinner_date"];
    if (!in_array($first_date_pref, $allowedPref, true)) {
      $error = "Choose your first date preference.";
    }
  }

  $newPrimary = null;

  if (!$error && isset($_FILES["primary_photo"])) {
    $newPrimary = handle_image_upload($_FILES["primary_photo"], __DIR__ . "/uploads", $error);
  }

  if (!$error) {
    $oldPrimary = $user["profile_picture"] ?? "";

    if ($looking_for !== "relationship") {
      $first_date_pref = null;
    }

    $profile_picture = $oldPrimary;
    if ($newPrimary) {
      $profile_picture = $newPrimary;
    }

    $stmt = $conn->prepare("
      UPDATE users
      SET display_name = ?,
          city = ?,
          bio = ?,
          looking_for = ?,
          first_date_pref = ?,
          profile_picture = ?
      WHERE email = ?
    ");

    $stmt->execute([
      $display_name,
      $city,
      $bio,
      $looking_for,
      $first_date_pref,
      $profile_picture,
      $email
    ]);

    if ($newPrimary && !empty($oldPrimary) && $oldPrimary !== $newPrimary) {
      delete_upload_file_if_safe($oldPrimary);
    }

    $_SESSION["flash_saved"] = 1;
    header("Location: settings.php");
    exit;
  }
}

/* =========================
   POST: delete profile
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_profile") {
  $deletePassword = $_POST["delete_password"] ?? "";

  if ($deletePassword === "") {
    $deleteError = "Enter your password to remove your profile.";
  } elseif (empty($user["password"]) || !password_verify($deletePassword, $user["password"])) {
    $deleteError = "Wrong password. Profile was not removed.";
  } else {
    $oldPrimary = $user["profile_picture"] ?? "";

    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if (!empty($oldPrimary)) {
      delete_upload_file_if_safe($oldPrimary);
    }

    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_destroy();
    }

    header("Location: index.php");
    exit;
  }
}

/* reload latest user after possible POST redirect flow */
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* render vars */
$primary = $user["profile_picture"] ?? "";

$display_name = isset($user["display_name"]) && $user["display_name"] !== ""
  ? $user["display_name"]
  : ($user["fullName"] ?? "");

$city = $user["city"] ?? "";
$bio  = $user["bio"] ?? "";
$looking_for = $user["looking_for"] ?? "";
$first_date_pref = $user["first_date_pref"] ?? "";

/* flash message */
$flashSaved = !empty($_SESSION["flash_saved"]);
unset($_SESSION["flash_saved"]);
$showDeletePanel = $deleteError !== "";

if ($flashSaved) {
  $success = "Saved.";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="settings.css">
</head>

<body id="home" class="page-settings" style="background-image:url('images/VCbackground.png');">

  <?php if ($error): ?>
    <div class="notice"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="notice ok centered"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <header>
    <div class="avatar" aria-label="Profile"></div>

    <div class="titleWrap">
      <div class="title">VerifiedCircle</div>

      <nav class="nav" aria-label="Main Navigation">
        <a href="home.php">Home</a>
        <a href="circle.php">Circle</a>
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
      <a class="menuItem" href="setup.php">Setup</a>
      <a class="menuItem" href="reviews.php">Leave a Review</a>
      <a class="menuItem" href="rapporten.html">Rapporten</a>
      <a class="menuItem logout" href="logout.php">Logout</a>
    </div>
  </header>

  <main>
    <div class="layout">

      <section class="panel panelLeft" style="position: relative; margin-bottom: 60px;">
        <div class="profileCard" id="bigProfileCard">
          <div class="pfpFill">
            <img id="bigPrimaryImg" src="<?= htmlspecialchars($primary) ?>" alt="Primary photo" style="<?= $primary ? "" : "display:none;" ?>">
            <?php if (!$primary): ?>
              <div class="pfpPlaceholder">Add photos</div>
            <?php endif; ?>
          </div>

          <div class="pfpOverlay">
            <div class="name"><?= htmlspecialchars($display_name ?: "User") ?></div>
          </div>
        </div>

        <button
          type="button"
          class="addPhotoBtnInside"
          style="position: absolute; bottom: -52px; left: 50%; transform: translateX(-50%);"
          onclick="document.getElementById('primaryPhotoInput').click(); event.stopPropagation();"
        >
          + Add picture
        </button>
      </section>

      <form method="post" enctype="multipart/form-data" id="settingsForm">
        <input type="hidden" name="action" value="save_profile">
        <input type="file" id="primaryPhotoInput" name="primary_photo" accept="image/*" class="hiddenUpload">

        <section class="panel panelRight">

          <div class="box glass">
            <div class="fieldWrap">
              <label class="fieldLabel" for="display_name">Display name</label>
              <input class="fieldInput" id="display_name" name="display_name"
                     value="<?= htmlspecialchars($display_name) ?>" required>
            </div>
          </div>

          <div class="box glass">
            <div class="fieldWrap">
              <label class="fieldLabel" for="city">City</label>
              <input class="fieldInput" id="city" name="city"
                     value="<?= htmlspecialchars($city) ?>">
            </div>
          </div>

          <div class="box glass">
            <div class="fieldWrap">
              <label class="fieldLabel" for="looking_for">Looking for</label>
              <select class="fieldSelect" id="looking_for" name="looking_for" required>
                <option value="">Select…</option>
                <option value="friends" <?= $looking_for==="friends" ? "selected" : "" ?>>Friends</option>
                <option value="networking" <?= $looking_for==="networking" ? "selected" : "" ?>>Networking</option>
                <option value="relationship" <?= $looking_for==="relationship" ? "selected" : "" ?>>Relationship</option>
              </select>

              <div id="datePrefWrap" style="margin-top:12px; <?= $looking_for==="relationship" ? "" : "display:none;" ?>">
                <label class="fieldLabel" for="first_date_pref">First date preference</label>

                <select class="fieldSelect" id="first_date_pref" name="first_date_pref">
                  <option value="">Select…</option>
                  <option value="coffee_walk" <?= $first_date_pref==="coffee_walk" ? "selected" : "" ?>>Coffee / Walk</option>
                  <option value="dinner_date" <?= $first_date_pref==="dinner_date" ? "selected" : "" ?>>Dinner date</option>
                </select>
              </div>
            </div>
          </div>

          <div class="box glass">
            <div class="fieldWrap">
              <label class="fieldLabel" for="bio">Bio</label>
              <textarea class="fieldTextarea" id="bio" name="bio"><?= htmlspecialchars($bio) ?></textarea>
            </div>
          </div>

          <div class="actions">
            <button id="newSaveBtn" class="btnSave" type="submit" form="settingsForm" aria-label="Save settings">SAVE</button>
          </div>

        </section>
      </form>

    </div>

    <section class="deleteProfileSection">
      <button
        type="button"
        class="btnDanger"
        id="toggleDeletePanelBtn"
        aria-expanded="<?= $showDeletePanel ? "true" : "false" ?>"
        aria-controls="deleteProfilePanel"
      >
        REMOVE PROFILE
      </button>

      <div id="deleteProfilePanel" class="deleteProfilePanel<?= $showDeletePanel ? " is-open" : "" ?>">
        <div class="deletePanelInner glass">
          <h3 class="deletePanelTitle">Remove profile</h3>
          <p class="deletePanelText">To remove your profile, first verify your password. Accounts cannot be recovered after deletion.</p>

          <?php if ($deleteError): ?>
            <div class="notice deletePanelNotice"><?= htmlspecialchars($deleteError) ?></div>
          <?php endif; ?>

          <form method="post" class="deletePanelForm">
            <input type="hidden" name="action" value="delete_profile">

            <label class="fieldLabel" for="delete_password">Password</label>
            <input class="fieldInput" id="delete_password" name="delete_password" type="password" placeholder="Enter your password" required>

            <div class="deletePanelActions">
              <button class="btnDanger deleteConfirmBtn" type="submit">Confirm deletion</button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </main>

  <script>
    (function(){
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
    })();
  </script>

  <script>
    (function(){
      const lf = document.getElementById("looking_for");
      const wrap = document.getElementById("datePrefWrap");
      const pref = document.getElementById("first_date_pref");
      if (!lf || !wrap || !pref) return;

      function syncPref(){
        const isRel = lf.value === "relationship";
        wrap.style.display = isRel ? "" : "none";
        pref.disabled = !isRel;
        if (!isRel) pref.value = "";
      }
      lf.addEventListener("change", syncPref);
      syncPref();
    })();
  </script>

  <script>
    document.getElementById('primaryPhotoInput')?.addEventListener('change', function () {
      if (this.files.length === 0) return;
      this.closest('form')?.submit();
    });
  </script>

  <script>
    (function(){
      const save = document.querySelector('.btnSave');
      const form = document.getElementById('settingsForm');
      if (!save || !form) return;

      save.addEventListener('click', function(){
        try { form.submit(); } catch(err) { console.error('submit error', err); }
      });
    })();
  </script>

  <script>
    (function(){
      const el = document.querySelector('.notice.centered');
      if (!el) return;

      setTimeout(() => el.classList.add('hide'), 2500);
      setTimeout(() => { try { el.remove(); } catch(e){} }, 3000);
    })();
  </script>

  <script>
    (function(){
      const toggleBtn = document.getElementById('toggleDeletePanelBtn');
      const panel = document.getElementById('deleteProfilePanel');
      if (!toggleBtn || !panel) return;

      toggleBtn.addEventListener('click', function(){
        const isOpen = panel.classList.toggle('is-open');
        toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    })();
  </script>

</body>
</html>