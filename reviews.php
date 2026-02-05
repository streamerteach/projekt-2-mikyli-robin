<?php
//startar en sessionionen
session_start();

// Om användaren inte är inloggad eller saknar e-post, omdirigerar den till inloggningssidan
if (empty($_SESSION["logged_in"]) || empty($_SESSION["email"])) {
  header("Location: index.php");
  exit;
}

// laddar användardatan från en JSON-fil
$file = __DIR__ . "/users.json";
$users = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($users)) $users = [];

//Hämtar den inloggade användarens e-postadress och fulla namn
$email = $_SESSION["email"];
$fullName = $users[$email]['fullname'] ?? $email;

//Hanterar när användaren skickar en kommentar
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["comment"])) {
  $reviewsFile = __DIR__ . "/reviews.json";
  $reviews = file_exists($reviewsFile) ? json_decode(file_get_contents($reviewsFile), true) : [];
  if (!is_array($reviews)) $reviews = [];

  // Läggertill ny kommentar i listan
  $newReview = [
    "username" => $fullName,
    "email" => $email,
    "comment" => trim($_POST["comment"]),
    "timestamp" => date('Y-m-d H:i:s')
  ];

  array_unshift($reviews, $newReview); // Lägger till ny kommentar överst i listan

  //Sparar alla kommentarer i JSON-filen
  file_put_contents($reviewsFile, json_encode($reviews, JSON_PRETTY_PRINT));
  
  //laddar om sidan efter att kommentaren har skickats
  header("Location: reviews.php");
  exit;
}

// Läser alla tidigare kommentarer från JSON-filen
$reviewsFile = __DIR__ . "/reviews.json";
$reviews = file_exists($reviewsFile) ? json_decode(file_get_contents($reviewsFile), true) : [];
if (!is_array($reviews)) $reviews = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Leave a Review - VerifiedCircle</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body id="home" style="background-image: url('images/VCbackground.png');">

  <header>
    <!-- navigationsmeny -->
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
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>

    <!-- Dropdown-menyn för andra användaralternativen -->
    <div class="menuDropdown" id="menuDropdown" aria-label="User menu">
      <a class="menuItem" href="setup.php">Setup</a>
      <a class="menuItem" href="reviews.php">Leave a Review</a>
      <a class="menuItem logout" href="logout.php">Logout</a>
    </div>
  </header>

  <main class="reviews-main">
    <h1 class="reviews-title">Leave a Review</h1>

    <!-- Formulär för att lägga till kommentar -->
    <div class="reviews-form-box">
      <form method="POST" action="">
        <label for="comment">Your Comment:</label>
        <textarea 
          id="comment" 
          name="comment" 
          rows="5" 
          required 
          class="reviews-textarea"
        ></textarea>
        <button type="submit" class="reviews-submit-btn">Submit Review</button>
      </form>
    </div>

    <!-- Lista med tidigare kommentarer -->
    <h2 class="reviews-section-title">Previous Reviews</h2>
    
    <?php if (empty($reviews)): ?>
      <p class="reviews-empty">No reviews yet. Be the first to leave one!</p>
    <?php else: ?>
      <?php foreach ($reviews as $review): ?>
        <div class="review-card">
          <div class="review-header">
            <!--Visar användarnamnet och tiden för kommentaren -->
            <strong><?php echo htmlspecialchars($review['username'] ?? 'Unknown'); ?></strong>
            <span class="review-timestamp"><?php echo htmlspecialchars($review['timestamp'] ?? ''); ?></span>
          </div>
          <!-- Visar själva kommentaren -->
          <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'] ?? '')); ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <script>
    //Hanterar menyknappen för att visa/dölja menyn
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