<?php
// Form submission handler - port of app/api/submit/route.ts

header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Load config and libraries
$appConfig = require __DIR__ . '/config.php';
require __DIR__ . '/lib/validation.php';
require __DIR__ . '/lib/format-answers.php';
require __DIR__ . '/lib/email-templates.php';

// PHPMailer
require __DIR__ . '/vendor/PHPMailer/src/Exception.php';
require __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Parse JSON body
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Honeypot check - silently accept to not reveal the trap
if (!empty($body['_hp_field'])) {
    echo json_encode(['success' => true]);
    exit;
}

// Time-based check - reject if submitted in under 3 seconds
if (isset($body['_started_at'])) {
    $elapsed = round(microtime(true) * 1000) - intval($body['_started_at']);
    if ($elapsed < 3000) {
        echo json_encode(['success' => true]);
        exit;
    }
}

$variantId    = $body['variantId'] ?? '';
$variantTitle = $body['variantTitle'] ?? '';
$answers      = $body['answers'] ?? [];

// Load questionnaire config
$configJson = file_get_contents(__DIR__ . '/questionnaire.json');
$questionnaireConfig = json_decode($configJson, true);

if (!$questionnaireConfig) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Konfigurationsfehler']);
    exit;
}

$variant = $questionnaireConfig['variants'][$variantId] ?? null;

if (!$variant || !empty($variant['placeholder'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Fragebogen-Variante']);
    exit;
}

// Server-side validation
$validationErrors = validateSubmission($variant['steps'], $answers);
if (count($validationErrors) > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $validationErrors]);
    exit;
}

$firstName   = trim($answers['first_name'] ?? '');
$lastName    = trim($answers['last_name'] ?? '');
$clientEmail = trim($answers['email'] ?? '');

$emailData = [
    'variantTitle' => $variantTitle ?: ($variant['shortTitle'] ?? $variantTitle),
    'steps'        => $variant['steps'],
    'answers'      => $answers,
    'firstName'    => $firstName,
    'lastName'     => $lastName,
    'email'        => $clientEmail,
    'calendlyUrl'  => $appConfig['calendly_url'] ?? '',
];

// Build emails
$advisorEmail       = buildAdvisorEmail($emailData);
$clientConfirmation = buildClientEmail($emailData);

$recipientEmails = $appConfig['recipient_emails'] ?? '';

// Helper: create configured PHPMailer instance
function createMailer(array $config): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = ($config['smtp_port'] == 465)
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port    = $config['smtp_port'];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(
        $config['smtp_from'] ?: $config['smtp_user'],
        'myplanb'
    );
    return $mail;
}

// Helper: send both emails, returns array of error strings
function sendAllEmails(
    array $appConfig,
    string $recipientEmails,
    array $advisorEmail,
    string $clientEmail,
    array $clientConfirmation
): array {
    $errors = [];

    if ($recipientEmails) {
        try {
            $mail = createMailer($appConfig);
            // Support comma-separated recipients
            foreach (explode(',', $recipientEmails) as $addr) {
                $addr = trim($addr);
                if ($addr) $mail->addAddress($addr);
            }
            $mail->isHTML(true);
            $mail->Subject = $advisorEmail['subject'];
            $mail->Body    = $advisorEmail['html'];
            $mail->AltBody = $advisorEmail['text'];
            $mail->send();
        } catch (Exception $e) {
            $errors[] = 'Advisor email failed: ' . $e->getMessage();
        }
    }

    if ($clientEmail) {
        try {
            $mail = createMailer($appConfig);
            $mail->addAddress($clientEmail);
            $mail->isHTML(true);
            $mail->Subject = $clientConfirmation['subject'];
            $mail->Body    = $clientConfirmation['html'];
            $mail->AltBody = $clientConfirmation['text'];
            $mail->send();
        } catch (Exception $e) {
            $errors[] = 'Client email failed: ' . $e->getMessage();
        }
    }

    return $errors;
}

// First attempt
$errors = sendAllEmails($appConfig, $recipientEmails, $advisorEmail, $clientEmail, $clientConfirmation);

if (!empty($errors)) {
    // Retry once
    $errors = sendAllEmails($appConfig, $recipientEmails, $advisorEmail, $clientEmail, $clientConfirmation);
    if (!empty($errors)) {
        error_log('myplanb submit email errors: ' . implode('; ', $errors));
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'E-Mail-Versand fehlgeschlagen. Bitte versuche es erneut.',
        ]);
        exit;
    }
}

echo json_encode(['success' => true]);
