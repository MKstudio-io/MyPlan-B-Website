<?php
// ============================================================
//  MY PLAN B – Vortrag Anmeldung
//  submit-vortrag.php
// ============================================================

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ---- Eingabe validieren ----
$vorname  = trim($_POST['vorname'] ?? '');
$nachname = trim($_POST['nachname'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefon  = trim($_POST['telefon'] ?? '');
$termin   = trim($_POST['termin'] ?? '');
$frage    = trim($_POST['frage'] ?? '');

if (empty($vorname) || empty($nachname) || empty($email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Pflichtfelder fehlen']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültige E-Mail-Adresse']);
    exit;
}

// ---- SMTP-Konfiguration aus config.php ----
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.php nicht gefunden unter: ' . $config_file]);
    exit;
}
$config = require $config_file;
$smtp_host    = $config['smtp_host'];
$smtp_port    = $config['smtp_port'];
$smtp_user    = $config['smtp_user'];
$smtp_pass    = $config['smtp_pass'];
$from_email   = $config['from_email'];
$from_name    = $config['from_name'];
$notify_email = $config['notify_email'];

// ============================================================
//  EINWAHL-LINK – hier eintragen sobald bekannt
// ============================================================
$einwahl_link = 'PLATZHALTER – Link folgt per E-Mail';
// Beispiel wenn du den Link hast:
// $einwahl_link = 'https://zoom.us/j/1234567890';
// ============================================================

// PHPMailer laden (aus bestehendem Fragebogen-Verzeichnis)
$phpmailer_path = __DIR__ . '/myplanbfragebogen/api/vendor/PHPMailer/src/PHPMailer.php';
if (!file_exists($phpmailer_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'PHPMailer nicht gefunden unter: ' . $phpmailer_path]);
    exit;
}
require_once $phpmailer_path;
require_once __DIR__ . '/myplanbfragebogen/api/vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/myplanbfragebogen/api/vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function create_mailer($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from_email, $from_name) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp_port;
    $mail->AuthType   = 'LOGIN';
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom($from_email, $from_name);
    return $mail;
}


// ============================================================
//  E-MAIL 1: Bestätigung an Teilnehmer
//  → Hier kannst du den Text anpassen
// ============================================================
$mail_error = '';

try {
    $mail = create_mailer($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from_email, $from_name);
    $mail->addAddress($email, $vorname . ' ' . $nachname);
    $mail->Subject = 'Deine Anmeldung zum MY PLAN B Vortrag – ' . $termin;
    $mail->isHTML(true);
    $mail->Body = '
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color: #2D2D2D; max-width: 600px; margin: 0 auto; padding: 20px;">

  <div style="background: #1B6E7C; padding: 30px 30px 20px; border-radius: 12px 12px 0 0; text-align: center;">
    <h1 style="color: #FFBD59; font-size: 22px; margin: 0 0 8px;">MY PLAN B</h1>
    <p style="color: rgba(255,255,255,0.85); margin: 0; font-size: 15px;">Deine Anmeldung ist bestätigt!</p>
  </div>

  <div style="background: #F7F5F2; padding: 30px; border-radius: 0 0 12px 12px; border: 1px solid #e0ddd8;">
    <p style="font-size: 16px;">Hallo ' . htmlspecialchars($vorname) . ',</p>
    <p>vielen Dank für deine Anmeldung zum kostenlosen Vortrag:</p>

    <div style="background: #1B6E7C; border-radius: 10px; padding: 20px 24px; margin: 20px 0; color: white;">
      <p style="margin: 0 0 8px; font-size: 18px; font-weight: bold; color: #FFBD59;">Schulwechsel, Lehre, Matura – Welcher Weg passt für Ihr Kind?</p>
      <p style="margin: 4px 0; font-size: 15px;">📅 ' . htmlspecialchars($termin) . '</p>
      <p style="margin: 4px 0; font-size: 15px;">💻 Online</p>
      <p style="margin: 4px 0; font-size: 15px;">⏱ ca. 60 Minuten</p>
    </div>

    <div style="background: #E4F0F2; border-radius: 10px; padding: 18px 24px; margin: 20px 0;">
      <p style="margin: 0 0 8px; font-weight: bold; color: #155A66; font-size: 15px;">🔗 Dein Einwahl-Link:</p>
      <p style="margin: 0; font-size: 15px; color: #1B6E7C;">' . htmlspecialchars($einwahl_link) . '</p>
    </div>

    <p style="font-size: 15px; line-height: 1.7;">Ich freue mich, dich beim Vortrag zu sehen!</p>
    <p style="font-size: 15px; line-height: 1.7; margin-bottom: 0;">Herzliche Grüße,<br>
    <strong>Martina Kreiner</strong><br>
    <span style="color: #6B6B6B;">martina@myplanb.at · +43 699 196 11701 · myplanb.at</span>
    </p>
  </div>

</body>
</html>';
    $mail->AltBody = "Hallo $vorname,\n\nDeine Anmeldung zum MY PLAN B Vortrag ist bestätigt!\n\n"
        . "Schulwechsel, Lehre, Matura – Welcher Weg passt für Ihr Kind?\n"
        . "Termin: $termin\n"
        . "Online\n\n"
        . "Einwahl-Link: $einwahl_link\n\n"
        . "Herzliche Grüße,\nMartina Kreiner\nmartina@myplanb.at";
    $mail->send();
} catch (Exception $e) {
    $mail_error = $e->getMessage();
    error_log('Vortrag Bestätigung fehlgeschlagen: ' . $mail_error);
}

// ============================================================
//  E-MAIL 2: Benachrichtigung an Martina
// ============================================================
try {
    $mail2 = create_mailer($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $from_email, $from_name);
    $mail2->addAddress($notify_email, 'Martina Kreiner');
    $mail2->Subject = '✅ Neue Vortrag-Anmeldung: ' . $vorname . ' ' . $nachname;
    $mail2->isHTML(true);
    $mail2->Body = '
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color: #2D2D2D; max-width: 600px; margin: 0 auto; padding: 20px;">
  <h2 style="color: #1B6E7C;">Neue Anmeldung – Online-Vortrag</h2>
  <table style="width:100%; border-collapse: collapse; font-size: 15px;">
    <tr style="border-bottom: 1px solid #e0ddd8;"><td style="padding: 10px 0; color:#6B6B6B; width:140px;">Name</td><td style="padding: 10px 0; font-weight:bold;">' . htmlspecialchars($vorname . ' ' . $nachname) . '</td></tr>
    <tr style="border-bottom: 1px solid #e0ddd8;"><td style="padding: 10px 0; color:#6B6B6B;">E-Mail</td><td style="padding: 10px 0;">' . htmlspecialchars($email) . '</td></tr>
    <tr style="border-bottom: 1px solid #e0ddd8;"><td style="padding: 10px 0; color:#6B6B6B;">Telefon</td><td style="padding: 10px 0;">' . (empty($telefon) ? '–' : htmlspecialchars($telefon)) . '</td></tr>
    <tr><td style="padding: 10px 0; color:#6B6B6B; vertical-align:top;">Frage</td><td style="padding: 10px 0;">' . (empty($frage) ? '–' : nl2br(htmlspecialchars($frage))) . '</td></tr>
  </table>
</body>
</html>';
    $mail2->AltBody = "Neue Anmeldung:\n\nName: $vorname $nachname\nE-Mail: $email\nTelefon: " . ($telefon ?: '–') . "\nTermin: $termin\nFrage: " . ($frage ?: '–');
    $mail2->send();
} catch (Exception $e) {
    $mail_error = $e->getMessage();
    error_log('Vortrag Benachrichtigung fehlgeschlagen: ' . $mail_error);
}

// Bei Fehler zurückmelden
if (!empty($mail_error)) {
    http_response_code(500);
    echo json_encode(['error' => 'SMTP-Fehler: ' . $mail_error]);
    exit;
}

// ============================================================
//  CSV: Anmeldung in Datei schreiben
//  → Datei: anmeldungen-vortrag.csv (auf dem Server)
// ============================================================
$csv_file = __DIR__ . '/anmeldungen-vortrag.csv';
$new_file  = !file_exists($csv_file);
$fp = fopen($csv_file, 'a');
if ($fp) {
    if ($new_file) {
        fputcsv($fp, ['Datum', 'Vorname', 'Nachname', 'E-Mail', 'Telefon', 'Termin'], ';');
    }
    fputcsv($fp, [
        date('d.m.Y H:i'),
        $vorname,
        $nachname,
        $email,
        $telefon,
        $termin,
    ], ';');
    fclose($fp);
}

// ============================================================
//  BREVO API: Kontakt zur Liste hinzufügen
// ============================================================
$brevo_api_key = $config['brevo_api_key'] ?? '';
$brevo_list_id = $config['brevo_list_id'] ?? 0;

if (!empty($brevo_api_key) && !empty($brevo_list_id)) {
    $contact_data = json_encode([
        'email'      => $email,
        'attributes' => [
            'VORNAME'   => $vorname,
            'NACHNAME'  => $nachname,
            'SMS'       => $telefon,
        ],
        'listIds'           => [$brevo_list_id],
        'updateEnabled'     => true,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/contacts');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $contact_data,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $brevo_api_key,
        ],
    ]);
    $brevo_response = curl_exec($ch);
    $brevo_status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($brevo_status !== 201 && $brevo_status !== 204) {
        error_log('Brevo API Fehler (' . $brevo_status . '): ' . $brevo_response);
    }
}

// ---- Erfolg zurückgeben ----
echo json_encode(['success' => true]);
