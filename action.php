<?php
session_start();
if (empty($_SESSION["logged_in"])) { header("Location: index.php"); exit; }

$me = $_SESSION["email"];
$action = $_POST["action"] ?? "";
$other = $_POST["candidate"] ?? "";

if ($other === "" || $other === $me) {
  header("Location: home.php");
  exit;
}

function load_json($path) {
  if (!file_exists($path)) file_put_contents($path, "{}");
  $data = json_decode(file_get_contents($path), true);
  return is_array($data) ? $data : [];
}

function save_json($path, $data) {
  file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

$likesFile = __DIR__ . "/likes.json";
$matchesFile = __DIR__ . "/matches.json";

$likes = load_json($likesFile);
$matches = load_json($matchesFile);

if ($action === "like") {

  // add to likes[me]
  if (!isset($likes[$me]) || !is_array($likes[$me])) $likes[$me] = [];
  if (!in_array($other, $likes[$me], true)) $likes[$me][] = $other;

  // check mutual like -> match
  $otherLikesMe = isset($likes[$other]) && is_array($likes[$other]) && in_array($me, $likes[$other], true);

  if ($otherLikesMe) {
    if (!isset($matches[$me]) || !is_array($matches[$me])) $matches[$me] = [];
    if (!isset($matches[$other]) || !is_array($matches[$other])) $matches[$other] = [];

    if (!in_array($other, $matches[$me], true)) $matches[$me][] = $other;
    if (!in_array($me, $matches[$other], true)) $matches[$other][] = $me;
  }

  save_json($likesFile, $likes);
  save_json($matchesFile, $matches);

} elseif ($action === "skip") {
  // optional: store skips.json so you don't show them again
}

header("Location: home.php");
exit;
