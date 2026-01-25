<?php
session_start();
if (empty($_SESSION["logged_in"])) {
  header("Location: index.php");
  exit;
}

$file = __DIR__ . "/users.json";
$users = json_decode(file_get_contents($file), true);
$email = $_SESSION["email"];

if (empty($users[$email]["onboarding_complete"])) {
  header("Location: onboarding.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VerifiedCircle</title>

  <style>
    :root{
      --bg: #000000;
      --headerBlue: #0a3ea8;
      --panelBlue: #0a3ea8;
      --panelBlueDark: #062f7a;
      --gray: #6e6e6e;
      --grayLight: #d9d9d9;
      --white: #ffffff;

      --btnSkip: #ff5a5a;
      --btnConnect: #6cff6c;

      --radiusBig: 28px;
      --radiusMid: 20px;
    }

    *{
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
    }
  </style>
</head>

<body>

  <header>
    <div class="avatar" aria-label="Profile"></div>

    <div class="titleWrap">
      <div class="title">VerifiedCircle</div>
      <nav class="nav" aria-label="Main Navigation">
        <a href="#" class="active">Home</a>
        <a href="#">Circle</a>
        <a href="#">Discover</a>
      </nav>
    </div>

    <div class="burger" aria-label="Menu">
      <div class="burgerLines">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </header>

  <main>
    <div class="layout">

      <!-- LEFT BIG PANEL -->
      <section class="panel panelLeft">
        <div class="profileCard">
          <div class="dotsRow" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span><span></span>
          </div>
        </div>
      </section>

      <!-- RIGHT STACKED PANEL -->
      <section class="panel panelRight">
        <div class="box"></div>
        <div class="box"></div>
        <div class="box"></div>
        <div class="box"></div>
      </section>

    </div>
  </main>

  <div class="actions">
    <button class="btn btnSkip" type="button">SKIP</button>
    <button class="btn btnConnect" type="button">CONNECT</button>
  </div>

</body>
</html>
