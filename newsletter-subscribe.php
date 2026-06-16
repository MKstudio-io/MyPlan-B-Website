<?php
/**
 * MY PLAN B – Newsletter Subscription
 * Adds subscriber to Brevo (Sendinblue) contact list via API v3
 *
 * Config: set BREVO_API_KEY and BREVO_LIST_ID below
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── CONFIG ──────────────────────────────────────────────────────────────────
$config = require __DIR__ . '/config.php';
define('BREVO_API_KEY', $config['brevo_api_key']);
define('BREVO_LIST_ID', 3);   // Newsletter-Liste ID
// ────────────────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email   = trim($_POST['email'] ?? '');
$consent = $_POST['consent'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Bitte eine gültige E-Mail-Adresse eingeben.']);
    exit;
}

if ($consent !== '1') {
    echo json_encode(['success' => false, 'message' => 'Einwilligung zum Datenschutz ist erforderlich.']);
    exit;
}

if (BREVO_API_KEY === 'YOUR_BREVO_API_KEY_HERE' || BREVO_LIST_ID === 0) {
    echo json_encode(['success' => false, 'message' => 'Newsletter ist noch nicht konfiguriert.']);
    exit;
}

$payload = json_encode([
    'email'         => $email,
    'listIds'       => [BREVO_LIST_ID],
    'updateEnabled' => true,   // aktualisiert bestehende Kontakte statt Fehler
]);

$ch = curl_init('https://api.brevo.com/v3/contacts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'accept: application/json',
        'content-type: application/json',
        'api-key: ' . BREVO_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 10,
]);

$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log('Newsletter cURL error: ' . $curlError);
    echo json_encode(['success' => false, 'message' => 'Verbindungsfehler. Bitte versuchen Sie es erneut.']);
    exit;
}

// 201 = created, 204 = updated (already exists)
if ($httpCode === 201 || $httpCode === 204) {
    echo json_encode(['success' => true]);
} else {
    $body = json_decode($response, true);
    $msg  = $body['message'] ?? 'Unbekannter Fehler.';
    error_log('Newsletter Brevo error ' . $httpCode . ': ' . $response);
    echo json_encode(['success' => false, 'message' => 'Fehler: ' . $msg]);
}
