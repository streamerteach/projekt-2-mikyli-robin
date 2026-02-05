<?php
/* settings.php
   user settings + primary photo upload
*/

session_start();

/* auth gate */
if (empty($_SESSION["logged_in"]) || empty($_SESSION["email"])) {
  header("Location: index.php");
  exit;
}

$email = $_SESSION["email"];

/* users storage */
$file  = __DIR__ . "/users.json";

/* load users json */
$users = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($users)) $users = [];

/* user must exist */
if (!isset($users[$email])) {
  header("Location: index.php");
  exit;
}

$error = "";
$success = "";

/* quick POST test (non-destructive)
   use a hidden __test_submit field
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['__test_submit'])) {
  error_log("[settings] Test submit received");
  $success = "Test submit received (server saw POST).";
}

/* write users.json safely */
function save_users($file, $users) {
  $data = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if ($data === false) return false;

  $bytes = file_put_contents($file, $data, LOCK_EX);
  if ($bytes === false) {
    error_log("[settings] Failed to write users file: $file");
    return false;
  }
  return true;
}

/* ensure uploads folder exists */
function ensure_upload_dir($dir) {
  if (!is_dir($dir)) mkdir($dir, 0755, true);
}

/* basic path safety: only allow uploads/... */
function is_safe_upload_path($path) {
  // PHP 7+ compatible (no str_starts_with)
  return is_string($path) && substr($path, 0, 8) === "uploads/";
}

/* delete only if path is safe */
function delete_upload_file_if_safe($relativePath) {
  if (!is_safe_upload_path($relativePath)) return;
  $abs = __DIR__ . "/" . $relativePath;
  if (is_file($abs)) @unlink($abs);
}

/* handle one image upload returns relative path like uploads/photo_xxx.jpg
*/
function handle_image_upload($fileArr, $uploadDirAbs, &$error) {
  if (!isset($fileArr) || !isset($fileArr["error"])) return null;
  if ($fileArr["error"] === UPLOAD_ERR_NO_FILE) return null;

  if ($fileArr["error"] !== UPLOAD_ERR_OK) {
    $error = "Upload failed. Try again.";
    return null;
  }

  $tmp  = $fileArr["tmp_name"];
  $size = (int)$fileArr["size"];

  /* size limit */
  if ($size <= 0 || $size > 5 * 1024 * 1024) {
    $error = "Image must be under 5MB.";
    return null;
  }

  /* must be a real image */
  $imgInfo = @getimagesize($tmp);
  if ($imgInfo === false) {
    $error = "Uploaded file is not a valid image.";
    return null;
  }

  /* allowlist mime -> extension */
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

  /* random filename */
  $ext = $allowed[$mime];
  $name = "photo_" . bin2hex(random_bytes(8)) . "." . $ext;
  $targetAbs = rtrim($uploadDirAbs, "/") . "/" . $name;

  /* move to uploads */
  if (!move_uploaded_file($tmp, $targetAbs)) {
    $error = "Could not save the uploaded image. Try again.";
    return null;
  }

  return "uploads/" . $name;
}

/* current user record */
$user = $users[$email];

/* normalize photos array (string paths only) */
$photos = $user["photos"] ?? [];
if (!is_array($photos)) $photos = [];
$photos = array_values(array_filter($photos, function($p){
  return is_string($p) && $p !== "";
}));

/* backward compat: seed photos from profile_picture */
if (empty($photos)) {
  if (!empty($user["profile_picture"]) && is_string($user["profile_picture"])) {
    $photos = [$user["profile_picture"]];
  } elseif (!empty($user["profile"]["profile_picture"]) && is_string($user["profile"]["profile_picture"])) {
    $photos = [$user["profile"]["profile_picture"]];
  }
}

/* =========================
   POST: save profile fields
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  error_log("[settings] POST handler entered. _POST keys: " . implode(', ', array_keys($_POST)));

  /* inputs */
  $display_name = trim($_POST["display_name"] ?? "");
  $city = trim($_POST["city"] ?? "");
  $bio = trim($_POST["bio"] ?? "");
  $looking_for = $_POST["looking_for"] ?? "";
  $first_date_pref = $_POST["first_date_pref"] ?? "";

  /* required: display name */
  if ($display_name === "") {
    $error = "Display name is required.";
  }

  /* validate looking_for */
  $allowedLooking = ["friends", "networking", "relationship"];
  if (!$error && !in_array($looking_for, $allowedLooking, true)) {
    $error = "Please choose what you're looking for.";
  }

  /* validate date pref only when relationship */
  if (!$error && $looking_for === "relationship") {
    $allowedPref = ["coffee_walk", "dinner_date"];
    if (!in_array($first_date_pref, $allowedPref, true)) {
      $error = "Choose your first date preference.";
    }
  }

  /* primary photo upload (single)
     replaces old primary (if it was in uploads/)
  */
  if (!$error && isset($_FILES["primary_photo"])) {
    $newPrimary = handle_image_upload($_FILES["primary_photo"], __DIR__ . "/uploads", $error);
    if (!$error && $newPrimary) {
      if (!empty($photos[0])) {
        delete_upload_file_if_safe($photos[0]);
      }
      $photos = [$newPrimary]; // keep it simple: one primary
    }
  }

  /* persist */
  if (!$error) {
    $users[$email]["display_name"] = $display_name;
    $users[$email]["city"] = $city;
    $users[$email]["bio"] = $bio;
    $users[$email]["looking_for"] = $looking_for;

    if ($looking_for === "relationship") {
      $users[$email]["first_date_pref"] = $first_date_pref;
    } else {
      unset($users[$email]["first_date_pref"]);
    }

    /* cap photos and sync profile_picture */
    $photos = array_values(array_slice($photos, 0, 6));
    $users[$email]["photos"] = $photos;
    $users[$email]["profile_picture"] = $photos[0] ?? "";

    /* save + redirect (PRG pattern) */
    $ok = save_users($file, $users);
    if ($ok) {
      $_SESSION["flash_saved"] = 1;
      header("Location: settings.php");
      exit;
    } else {
      $error = "Unable to save settings (check file permissions).";
    }
  }
}

/* =========================
   RENDER DATA
   (keep vars always defined)
   ========================= */

/* ensure $photos exists before render (single source of truth)
   note: this section re-reads from $user as-is
*/
$photos = (isset($user["photos"]) && is_array($user["photos"])) ? $user["photos"] : array();

$photos = array_values(array_filter($photos, function($p){
  return is_string($p) && $p !== "";
}));

/* fallback: if profile_picture exists */
if (empty($photos)) {
  if (!empty($user["profile_picture"]) && is_string($user["profile_picture"])) {
    $photos = array($user["profile_picture"]);
  } elseif (!empty($user["profile"]["profile_picture"]) && is_string($user["profile"]["profile_picture"])) {
    $photos = array($user["profile"]["profile_picture"]);
  }
}

/* primary image path */
$primary = isset($photos[0]) ? $photos[0] : "";

/* render vars */
$display_name = isset($user["display_name"]) && $user["display_name"] !== ""
  ? $user["display_name"]
  : (isset($user["fullname"]) ? $user["fullname"] : "");

$city = isset($user["city"]) ? $user["city"] : "";
$bio  = isset($user["bio"]) ? $user["bio"] : "";

$looking_for = isset($user["looking_for"]) ? $user["looking_for"] : "";
$first_date_pref = isset($user["first_date_pref"]) ? $user["first_date_pref"] : "";

/* flash message */
$flashSaved = !empty($_SESSION["flash_saved"]);
unset($_SESSION["flash_saved"]);

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

  <?php /* notices */ ?>
  <?php if ($error): ?>
    <div class="notice"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="notice ok centered"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- header -->
  <header>
    <div class="avatar" aria-label="Profile"></div>

    <div class="titleWrap">
      <div class="title">VerifiedCircle</div>

      <!-- main nav -->
      <nav class="nav" aria-label="Main Navigation">
        <a href="home.php">Home</a>
        <a href="circle.php">Circle</a>
        <a href="discover.php">Discover</a>
        <a href="profile.php">Profile</a>
      </nav>
    </div>

    <!-- burger -->
    <div class="burger" aria-label="Menu" id="burgerBtn">
      <div class="burgerLines">
        <span></span><span></span><span></span>
      </div>
    </div>

    <!-- dropdown -->
    <div class="menuDropdown" id="menuDropdown" aria-label="User menu">
      <a class="menuItem" href="setup.php">Setup</a>
      <a class="menuItem" href="reviews.php">Leave a Review</a>
      <a class="menuItem" href="rapporten.html">Rapporten</a>
      <a class="menuItem logout" href="logout.php">Logout</a>
    </div>
  </header>

  <main>
    <div class="layout">

      <!-- LEFT: primary photo card -->
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

        <!-- triggers hidden file input -->
        <button
          type="button"
          class="addPhotoBtnInside"
          style="position: absolute; bottom: -52px; left: 50%; transform: translateX(-50%);"
          onclick="document.getElementById('primaryPhotoInput').click(); event.stopPropagation();"
        >
          + Add picture
        </button>
      </section>

      <!-- RIGHT: form fields -->
      <form method="post" enctype="multipart/form-data" id="settingsForm">
        <!-- hidden upload -->
        <input type="file" id="primaryPhotoInput" name="primary_photo" accept="image/*" class="hiddenUpload">

        <section class="panel panelRight">

          <!-- display name -->
          <div class="box glass">
            <div class="fieldWrap">
              <label class="fieldLabel" for="display_name">Display name</label>
              <input class="fieldInput" id="display_name" name="display_name"
                     value="<?= htmlspecialchars($display_name) ?>" required>
            </div>
          </div>

          <!-- city -->
          <div class="box glass">
            <div class="fieldWrap">
              <label class="fieldLabel" for="city">City</label>
              <input class="fieldInput" id="city" name="city"
                     value="<?= htmlspecialchars($city) ?>">
            </div>
          </div>

          <!-- looking_for + conditional date pref -->
          <div class="box glass">
            <div class="fieldWrap">
              <label class="fieldLabel" for="looking_for">Looking for</label>
              <select class="fieldSelect" id="looking_for" name="looking_for" required>
                <option value="">Select…</option>
                <option value="friends" <?= $looking_for==="friends" ? "selected" : "" ?>>Friends</option>
                <option value="networking" <?= $looking_for==="networking" ? "selected" : "" ?>>Networking</option>
                <option value="relationship" <?= $looking_for==="relationship" ? "selected" : "" ?>>Relationship</option>
              </select>

              <!-- only visible when relationship -->
              <div id="datePrefWrap" style="margin-top:12px; <?= $looking_for==="relationship" ? "" : "display:none;" ?>">
                <label class="fieldLabel" for="first_date_pref">First date preference</label>

                <select class="fieldSelect" id="first_date_pref" name="first_date_pref" required>
                  <option value="">Select…</option>
                  <option value="coffee_walk" <?= $first_date_pref==="coffee_walk" ? "selected" : "" ?>>Coffee / Walk</option>
                  <option value="dinner_date" <?= $first_date_pref==="dinner_date" ? "selected" : "" ?>>Dinner date</option>
                </select>
              </div>
            </div>
          </div>

          <!-- bio -->
          <div class="box glass">
            <div class="fieldWrap">
              <label class="fieldLabel" for="bio">Bio</label>
              <textarea class="fieldTextarea" id="bio" name="bio"><?= htmlspecialchars($bio) ?></textarea>
            </div>
          </div>

          <!-- save -->
          <div class="actions">
            <button id="newSaveBtn" class="btnSave" type="submit" form="settingsForm" aria-label="Save settings">SAVE</button>
          </div>

        </section>
      </form>

    </div>
  </main>

  <script>
    /* relationship -> date pref visibility + validation
       disabled inputs are ignored by native validation
    */
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
    /* auto-submit after photo select */
    document.getElementById('primaryPhotoInput')?.addEventListener('change', function () {
      if (this.files.length === 0) return;
      this.closest('form')?.submit();
    });
  </script>

  <script>
    /* force submit (debug guard) */
    (function(){
      const save = document.querySelector('.btnSave');
      const form = document.getElementById('settingsForm');
      if (!save || !form) return;

      save.addEventListener('click', function(){
        console.log('btnSave clicked — forcing submit');
        try{ form.submit(); } catch(err){ console.error('submit error', err); }
      });
    })();
  </script>

  <script>
    /* auto-hide centered success notice */
    (function(){
      const el = document.querySelector('.notice.centered');
      if (!el) return;

      setTimeout(() => el.classList.add('hide'), 2500);
      setTimeout(() => { try{ el.remove(); } catch(e){} }, 3000);
    })();
  </script>

</body>
</html>
