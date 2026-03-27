<?php
// Email templates - port of config/email-templates.ts

function buildAdvisorEmail(array $data): array {
    $variantTitle = $data['variantTitle'];
    $steps        = $data['steps'];
    $answers      = $data['answers'];
    $firstName    = $data['firstName'];
    $lastName     = $data['lastName'];

    $tz   = new DateTimeZone('Europe/Vienna');
    $now  = new DateTime('now', $tz);
    $date = $now->format('d.m.Y');
    $time = $now->format('H:i');

    $subject = "[$variantTitle] $firstName $lastName – $date";

    $textBody = formatAnswersText($steps, $answers);
    $htmlBody = formatAnswersHtml($steps, $answers);

    $text = "Neuer Fragebogen eingegangen\n===========================\n\n"
          . "Variante: $variantTitle\nEingegangen am: $date um $time\n\n"
          . $textBody . "\n\n---\nAutomatisch versendet vom myplanb Fragebogen.";

    $vtEsc = htmlspecialchars($variantTitle, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #2D3748; max-width: 600px; margin: 0 auto; padding: 20px;">
  <div style="background: #F7FAFC; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
    <h1 style="font-size: 20px; margin: 0 0 8px;">Neuer Fragebogen eingegangen</h1>
    <p style="margin: 0; color: #718096;">
      <strong>{$vtEsc}</strong> &middot; {$date} um {$time}
    </p>
  </div>

  {$htmlBody}

  <div style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #A0AEC0;">
    Automatisch versendet vom myplanb Fragebogen.
  </div>
</body>
</html>
HTML;

    return ['subject' => $subject, 'text' => $text, 'html' => $html];
}

function buildClientEmail(array $data): array {
    $firstName   = $data['firstName'];
    $calendlyUrl = $data['calendlyUrl'] ?? '';

    $subject = 'Danke für deine Angaben – so geht es weiter';

    $calendlyText = $calendlyUrl
        ? "\nDu möchtest gleich einen Beratungstermin vereinbaren?\n→ $calendlyUrl\n"
        : '';

    $text = "Hallo $firstName,\n\n"
          . "vielen Dank, dass du dir die Zeit genommen hast! Deine Angaben sind bei mir angekommen.\n\n"
          . "So geht es weiter:\n"
          . "- Ich erstelle ein persönliches Profil und bereite konkrete Empfehlungen vor.\n"
          . "- Innerhalb weniger Werktage melde ich mich bei dir.\n"
          . $calendlyText
          . "\nBei Fragen erreichst du mich unter beratung@myplanb.at.\n\n"
          . "Herzliche Grüße,\nMartina von myplanb";

    $fnEsc = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $calendlyHtml = '';
    if ($calendlyUrl) {
        $cuEsc = htmlspecialchars($calendlyUrl, ENT_QUOTES, 'UTF-8');
        $calendlyHtml = <<<HTML
  <div style="background: #F7FAFC; border-radius: 8px; padding: 24px; margin: 24px 0; text-align: center;">
    <p style="margin: 0 0 16px; font-weight: 600;">Du möchtest gleich einen Beratungstermin vereinbaren?</p>
    <a href="{$cuEsc}" style="display: inline-block; background: #3182CE; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 500;">
      Jetzt Termin buchen
    </a>
  </div>
HTML;
    }

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #2D3748; max-width: 600px; margin: 0 auto; padding: 20px;">
  <h1 style="font-size: 22px;">Hallo {$fnEsc}!</h1>

  <p>Vielen Dank, dass du dir die Zeit genommen hast! Deine Angaben sind bei mir angekommen.</p>

  <h2 style="font-size: 16px; margin-top: 24px;">So geht es weiter:</h2>
  <ul>
    <li>Ich erstelle ein persönliches Profil und bereite konkrete Empfehlungen vor.</li>
    <li>Innerhalb weniger Werktage melde ich mich bei dir.</li>
  </ul>

  {$calendlyHtml}

  <p>Bei Fragen erreichst du mich unter <a href="mailto:beratung@myplanb.at">beratung@myplanb.at</a>.</p>

  <p>Herzliche Grüße,<br><strong>Martina von myplanb</strong></p>

  <div style="margin-top: 32px; padding-top: 16px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #A0AEC0;">
    Diese E-Mail wurde automatisch versendet. Bitte antworte nicht direkt auf diese E-Mail.
  </div>
</body>
</html>
HTML;

    return ['subject' => $subject, 'text' => $text, 'html' => $html];
}
