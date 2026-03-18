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

$allowedLookingFor = ["", "friends", "networking", "relationship"];
$allowedDatePrefs = ["", "coffee_walk", "dinner_date"];

$lookingForFilter = $_GET["looking_for"] ?? "";
$firstDatePrefFilter = $_GET["first_date_pref"] ?? "";
$chatUserId = isset($_GET["chat_user"]) ? (int)$_GET["chat_user"] : 0;

if (!in_array($lookingForFilter, $allowedLookingFor, true)) {
  $lookingForFilter = "";
}

if (!in_array($firstDatePrefFilter, $allowedDatePrefs, true)) {
  $firstDatePrefFilter = "";
}

/* Load matched users */
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

$params = [$me, $me];

if ($lookingForFilter !== "") {
  $sql .= " AND u.looking_for = ?";
  $params[] = $lookingForFilter;
}

if ($firstDatePrefFilter !== "") {
  $sql .= " AND u.first_date_pref = ?";
  $params[] = $firstDatePrefFilter;
}

$sql .= " ORDER BY u.display_name, u.fullName, u.email";

function circle_label_looking_for($value) {
  $map = [
    "friends" => "Friends",
    "networking" => "Networking",
    "relationship" => "Relationship"
  ];
  return $map[$value] ?? "Not set";
}

function circle_label_date_pref($value) {
  $map = [
    "coffee_walk" => "Coffee / Walk",
    "dinner_date" => "Dinner date"
  ];
  return $map[$value] ?? "Not set";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$myMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selectedMatch = null;

if ($chatUserId > 0) {
  foreach ($myMatches as $u) {
    if ((int)($u["id"] ?? 0) === $chatUserId) {
      $selectedMatch = $u;
      break;
    }
  }
}

$selectedEmail = $selectedMatch["email"] ?? "";
$selectedName = $selectedMatch
  ? ($selectedMatch["display_name"] ?? $selectedMatch["fullName"] ?? $selectedMatch["email"])
  : "";
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
  <div class="layout circleLayoutTwoColumn">

    <section class="panel circlePanel matchesPanel">
      <div class="circleHeaderRow">
        <h2 class="circleHeading">MATCHES</h2>
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

      <?php if (count($myMatches) === 0): ?>
        <p class="circleEmpty">No matches matched your filters yet.</p>
      <?php else: ?>

        <div class="matchList">
          <?php foreach ($myMatches as $u):
            $matchId = (int)($u["id"] ?? 0);
            $matchEmail = $u["email"] ?? "";
            $name = $u["display_name"] ?? $u["fullName"] ?? $matchEmail;
            $photo = $u["profile_picture"] ?? "";
            $lookingFor = circle_label_looking_for($u["looking_for"] ?? "");
            $firstDatePrefRaw = $u["first_date_pref"] ?? "";
            $firstDatePref = circle_label_date_pref($firstDatePrefRaw);

            $isActive = ($chatUserId === $matchId);
            $cardClass = $isActive ? "matchCard activeMatch" : "matchCard";

            $query = http_build_query([
              "looking_for" => $lookingForFilter,
              "first_date_pref" => $firstDatePrefFilter,
              "chat_user" => $matchId
            ]);
          ?>
            <a class="<?php echo $cardClass; ?>" href="circle.php?<?php echo htmlspecialchars($query); ?>">
              <div class="matchPhotoWrap">
                <div class="matchPhoto" style="<?php echo $photo ? "background-image:url('" . htmlspecialchars($photo, ENT_QUOTES) . "');" : ""; ?>"></div>
              </div>

              <div class="matchName"><?php echo htmlspecialchars($name); ?></div>

              <div class="matchTagRow">
                <span class="matchTag"><?php echo htmlspecialchars($lookingFor); ?></span>
                <?php if ($firstDatePrefRaw !== ""): ?>
                  <span class="matchTag"><?php echo htmlspecialchars($firstDatePref); ?></span>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </section>

    <section class="panel circleChatPanel">
      <div class="chatPanelInner">
        <div class="chatPanelTop">
          <h2 class="chatHeading">CHAT</h2>
        </div>

        <div class="chatMessages">
          <?php if ($selectedMatch): ?>
            <?php
              $selectedName = $selectedMatch["display_name"] ?? $selectedMatch["fullName"] ?? $selectedMatch["email"] ?? "Match";
            ?>
            <div class="chatSelectedUser">
              Chatting with <strong><?php echo htmlspecialchars($selectedName); ?></strong>
            </div>

            <div class="chatPlaceholder">
              No messages yet.
            </div>
          <?php else: ?>
            <div class="chatPlaceholder">
              Select a match to start chatting.
            </div>
          <?php endif; ?>
        </div>

        <form class="chatInputRow" method="post">
          <input
            type="text"
            class="chatInput"
            placeholder="<?php echo $selectedMatch ? 'Write a message...' : 'Select a match first'; ?>"
            <?php echo $selectedMatch ? "" : "disabled"; ?>
          />
          <button
            type="submit"
            class="chatSendBtn"
            <?php echo $selectedMatch ? "" : "disabled"; ?>
          >
            Send
          </button>
        </form>
      </div>
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

  const selectedEmail = <?php echo json_encode($selectedEmail); ?>;
  const myEmail = <?php echo json_encode($_SESSION["email"] ?? ""); ?>;

  const chatMessages = document.querySelector(".chatMessages");
  const chatForm = document.querySelector(".chatInputRow");
  const chatInput = document.querySelector(".chatInput");
  const chatSendBtn = document.querySelector(".chatSendBtn");

  let pollTimer = null;

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (m) {
      return ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
      })[m];
    });
  }

  function renderMessages(messages) {
    if (!chatMessages) return;

    let topHtml = "";

    if (selectedEmail) {
      topHtml = `
        <div class="chatSelectedUser">
          Chatting with <strong><?php echo htmlspecialchars($selectedName); ?></strong>
        </div>
      `;
    }

    if (!messages || messages.length === 0) {
      chatMessages.innerHTML = topHtml + `
        <div class="chatPlaceholder">No messages yet.</div>
      `;
      return;
    }

    let html = topHtml;

    messages.forEach(msg => {
      const mine = msg.sender_email === myEmail;
      html += `
        <div class="chatBubbleWrap ${mine ? "mine" : "theirs"}">
          <div class="chatBubble ${mine ? "mine" : "theirs"}">
            ${escapeHtml(msg.message)}
          </div>
        </div>
      `;
    });

    chatMessages.innerHTML = html;
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  async function loadMessages() {
    if (!selectedEmail || !chatMessages) return;

    try {
      const res = await fetch("load_messages.php?chat_with=" + encodeURIComponent(selectedEmail), {
        cache: "no-store"
      });

      const data = await res.json();

      if (!data.success) {
        console.log(data.error || "Failed to load messages");
        return;
      }

      renderMessages(data.messages || []);
    } catch (err) {
      console.log("Load error:", err);
    }
  }

  async function sendMessage(message) {
  const res = await fetch("send_message.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
    },
    body:
      "receiver_email=" + encodeURIComponent(selectedEmail) +
      "&message=" + encodeURIComponent(message)
  });

  const raw = await res.text();
  console.log("send_message raw response:", raw);

  try {
    return JSON.parse(raw);
  } catch (e) {
    return {
      success: false,
      error: "Invalid JSON from send_message.php",
      raw: raw
    };
  }
}

  if (selectedEmail) {
    loadMessages();
    pollTimer = setInterval(loadMessages, 2000);
  }

  if (chatForm && chatInput && selectedEmail) {
    chatForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      const message = chatInput.value.trim();
      if (!message) return;

      chatInput.disabled = true;
      if (chatSendBtn) chatSendBtn.disabled = true;

      try {
        const data = await sendMessage(message);

        if (data.success) {
          chatInput.value = "";
          await loadMessages();
        } else {
          alert(data.error || "Failed to send message");
        }
      } catch (err) {
        alert("Failed to send message");
        console.log("Send error:", err);
      } finally {
        chatInput.disabled = false;
        if (chatSendBtn) chatSendBtn.disabled = false;
        chatInput.focus();
      }
    });
  }
</script>
</body>
</html>