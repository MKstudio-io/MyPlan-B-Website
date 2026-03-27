<?php
// Pilotprojekt-Anmeldung: E-Mail an Admin + Speicherung in Liste + Bestätigung an Teilnehmer

// === Einstellungen ===
$adminMail = 'martina@myplanb.at';
$listFile = __DIR__ . '/pilot-anmeldungen.csv';

// === Hilfsfunktion: E-Mail senden ===
function sendMail($to, $subject, $body, $from = 'martina@myplanb.at') {
    $headers = "From: MY PLAN B <" . $from . ">\r\n" .
               "Reply-To: " . $from . "\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers);
}

// === Formulardaten prüfen ===
$name  = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$kind  = trim($_POST['kind'] ?? '');

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Bitte alle Pflichtfelder korrekt ausfüllen.';
    exit;
}

// === In CSV speichern ===
$date = date('Y-m-d H:i:s');
$entry = [$date, $name, $email, $kind];
file_put_contents($listFile, implode(';', $entry) . "\n", FILE_APPEND | LOCK_EX);

// === E-Mail an Admin ===
$adminSubject = "Neue Pilotprojekt-Anmeldung";
$adminBody = "Neue Anmeldung zum Pilotprojekt:\n\nName: $name\nE-Mail: $email\nKind: $kind\nDatum: $date\n";
sendMail($adminMail, $adminSubject, $adminBody);

// === Bestätigungs-E-Mail an Teilnehmer ===
$userSubject = "Deine Anmeldung zum MY PLAN B Pilotprojekt";
$userBody = "Hallo $name,\n\nvielen Dank für deine Anmeldung zum exklusiven MY PLAN B Pilotprojekt!\n\nWir melden uns in Kürze mit weiteren Infos. Die Plätze sind limitiert – du bist jetzt vorgemerkt.\n\nHerzliche Grüße\nMartina Kreiner\nMY PLAN B";
sendMail($email, $userSubject, $userBody);

// === Erfolgsseite ===
header('Location: /pilot/danke.html');
exit;
