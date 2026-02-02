<?php
function handleVisitor($email, $users) {
    $visitsFile = __DIR__ . "/visits.json";
    $visitsData = file_exists($visitsFile) ? json_decode(file_get_contents($visitsFile), true) : ["visitors" => []];
    if (!is_array($visitsData)) $visitsData = ["visitors" => []];

    $current_time = date('d-m-Y H:i:s');
    $found = false;

    foreach ($visitsData['visitors'] as &$visitor) {
        if ($visitor['username'] === $email) {
            $visitor['last_visit'] = $current_time;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $visitsData['visitors'][] = ["username" => $email, "last_visit" => $current_time];
    }

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