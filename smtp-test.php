<?php
// SMTP Test - NACH DEM TEST SOFORT VOM SERVER LÖSCHEN!
$config = require __DIR__ . '/config.php';

require_once __DIR__ . '/myplanbfragebogen/api/vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/myplanbfragebogen/api/vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/myplanbfragebogen/api/vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
$mail->SMTPDebug = SMTP::DEBUG_SERVER;
$mail->isSMTP();
$mail->Host       = $config['smtp_host'];
$mail->SMTPAuth   = true;
$mail->Username   = $config['smtp_user'];
$mail->Password   = trim($config['smtp_pass']);
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = $config['smtp_port'];
$mail->AuthType   = 'LOGIN';

echo "<pre>Host: " . $config['smtp_host'] . "\n";
echo "Port: " . $config['smtp_port'] . "\n";
echo "User: " . $config['smtp_user'] . "\n";
echo "Pass Länge: " . strlen($config['smtp_pass']) . " Zeichen\n";
echo "Pass Anfang: " . substr($config['smtp_pass'], 0, 12) . "\n";
echo "Pass Ende: ..." . substr($config['smtp_pass'], -8) . "\n\n";

try {
    $mail->addAddress('martina@myplanb.at');
    $mail->Subject = 'SMTP Test';
    $mail->Body    = 'Test';
    $mail->setFrom($config['smtp_user'], 'Test');
    $mail->send();
    echo "\n✅ ERFOLG - E-Mail gesendet!\n";
} catch (Exception $e) {
    echo "\n❌ FEHLER: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
