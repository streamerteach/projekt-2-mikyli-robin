<?php
session_start();

/* must be logged in */
if (empty($_SESSION["logged_in"])) { header("Location: index.php"); exit; }

/* me + inputs */
$me     = $_SESSION["email"];
$action = $_POST["action"] ?? "";
$other  = $_POST["candidate"] ?? "";

/* guard: no empty / no self */
if ($other === "" || $other === $me) {
  header("Location: home.php");
  exit;
}

/* load json (create file if missing) */
function load_json($path) {
  if (!file_exists($path)) file_put_contents($path, "{}");
  $data = json_decode(file_get_contents($path), true);
  return is_array($data) ? $data : [];
}

/* save json */
function save_json($path, $data) {
  file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

/* files */
$likesFile   = __DIR__ . "/likes.json";
$matchesFile = __DIR__ . "/matches.json";

/* state */
$likes   = load_json($likesFile);
$matches = load_json($matchesFile);

if ($action === "like") {

  /* add like -> likes[me][] */
  if (!isset($likes[$me]) || !is_array($likes[$me])) $likes[$me] = [];
  if (!in_array($other, $likes[$me], true)) $likes[$me][] = $other;

  /* did other already like me? */
  $otherLikesMe =
    isset($likes[$other]) &&
    is_array($likes[$other]) &&
    in_array($me, $likes[$other], true);

  /* mutual like = match */
  if ($otherLikesMe) {

    /* ensure match arrays exist */
    if (!isset($matches[$me]) || !is_array($matches[$me])) $matches[$me] = [];
    if (!isset($matches[$other]) || !is_array($matches[$other])) $matches[$other] = [];

    /* add both sides (no dupes) */
    if (!in_array($other, $matches[$me], true)) $matches[$me][] = $other;
    if (!in_array($me, $matches[$other], true)) $matches[$other][] = $me;
  }

  /* write to disk */
  save_json($likesFile, $likes);
  save_json($matchesFile, $matches);

} elseif ($action === "skip") {

  /* optional: write skips.json so they won't show again */
  /* keep same pattern as likes.json */

}

/* done -> back to home */
header("Location: home.php");
exit;
