<?php
function handleVisitor($email, $users) {
    //filen där besöksdatan sparas
    $visitsFile = __DIR__ . "/visits.json";
    //laddar upp besöksdatan från filen eller skapar en tom lista om filen inte finns
    $visitsData = file_exists($visitsFile) ? json_decode(file_get_contents($visitsFile), true) : ["visitors" => []];
    if (!is_array($visitsData)) $visitsData = ["visitors" => []];

    // hämtar den nuvarande tiden
    $current_time = date('d-m-Y H:i:s');
    $found = false;

    //Kollar om användaren redan finns i besökslistan
    foreach ($visitsData['visitors'] as &$visitor) {
        if ($visitor['username'] === $email) {
            // Uppdaterar senaste besöket om användaren redan finns
            $visitor['last_visit'] = $current_time;
            $found = true;
            break;
        }
    }

    //om användaren inte finns, lägger den till i besökslistan
    if (!$found) {
        $visitsData['visitors'][] = ["username" => $email, "last_visit" => $current_time];
    }
//Sparar uppdaterade besöksdatan tillbaka till filen
    file_put_contents($visitsFile, json_encode($visitsData, JSON_PRETTY_PRINT));

    // Hämtar användarens "Full Name"
    $fullName = $users[$email]['fullname'] ?? $email;

    // Returnerar datan
    return [
        "unique_visitors" => count($visitsData['visitors']),
        "full_name" => $fullName,
        "last_visit" => $current_time
    ];
}
?>