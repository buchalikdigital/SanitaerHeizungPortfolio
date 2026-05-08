<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt.']);
    exit;
}

$to      = 'info@thermoprofi-frankfurt.de';
$subject = 'Neue Anfrage über thermoprofi-frankfurt.de';

// Honeypot
if (!empty($_POST['website'])) {
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

function clean($val) {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$name     = clean($_POST['name']     ?? '');
$email    = clean($_POST['email']    ?? '');
$phone    = clean($_POST['phone']    ?? '');
$leistung = clean($_POST['leistung'] ?? '');
$message  = clean($_POST['message']  ?? '');
$dsgvo    = isset($_POST['dsgvo']) ? true : false;
$whatsapp = isset($_POST['whatsapp']) ? 'Ja' : 'Nein';

$errors = [];
if (strlen($name) < 2)                          $errors[] = 'Bitte geben Sie Ihren Namen ein.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
if (strlen($message) < 10)                      $errors[] = 'Bitte schreiben Sie eine Nachricht (min. 10 Zeichen).';
if (!$dsgvo)                                     $errors[] = 'Bitte stimmen Sie der Datenschutzerklärung zu.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: ThermoProfi GmbH <info@thermoprofi-frankfurt.de>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$leistungRow = $leistung ? "<div class='field'><div class='label'>Leistung</div><div class='value'>{$leistung}</div></div>" : '';
$phoneRow    = $phone    ? "<div class='field'><div class='label'>Telefon</div><div class='value'><a href='tel:{$phone}' style='color:#1B3A6B'>{$phone}</a></div></div>" : '';

$body = "
<!DOCTYPE html>
<html lang='de'>
<head><meta charset='UTF-8'><style>
  body { font-family: Arial, sans-serif; color: #0F172A; background: #F8FAFC; margin: 0; padding: 0; }
  .wrap { max-width: 560px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #DBEAFE; }
  .header { background: linear-gradient(135deg, #1B3A6B, #2D5FA0); padding: 28px 32px; }
  .header h1 { margin: 0; color: #fff; font-size: 20px; }
  .header p { margin: 6px 0 0; color: rgba(255,255,255,0.7); font-size: 13px; }
  .body { padding: 28px 32px; }
  .field { margin-bottom: 20px; }
  .label { font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #2D5FA0; margin-bottom: 6px; }
  .value { font-size: 15px; color: #0F172A; line-height: 1.6; }
  .message-box { background: #EFF6FF; border-left: 3px solid #F97316; padding: 14px 16px; border-radius: 0 8px 8px 0; }
  .badge { display:inline-block; background:#FFF7ED; border:1px solid #F97316; border-radius:50px; padding:3px 12px; font-size:12px; color:#EA580C; font-weight:600; }
  .footer { background: #EFF6FF; padding: 16px 32px; font-size: 12px; color: #475569; text-align: center; }
</style></head>
<body>
<div class='wrap'>
  <div class='header'>
    <h1>Neue Kundenanfrage</h1>
    <p>Über das Kontaktformular auf thermoprofi-frankfurt.de</p>
  </div>
  <div class='body'>
    <div class='field'><div class='label'>Name</div><div class='value'>{$name}</div></div>
    <div class='field'><div class='label'>E-Mail</div><div class='value'><a href='mailto:{$email}' style='color:#1B3A6B'>{$email}</a></div></div>
    {$phoneRow}
    {$leistungRow}
    <div class='field'><div class='label'>WhatsApp-Kontakt gewünscht</div><div class='value'><span class='badge'>{$whatsapp}</span></div></div>
    <div class='field'><div class='label'>Nachricht</div><div class='value message-box'>" . nl2br($message) . "</div></div>
  </div>
  <div class='footer'>ThermoProfi GmbH &bull; Musterstraße 42, 60311 Frankfurt &bull; 069 123 456 78</div>
</div>
</body></html>
";

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    $replyHeaders  = "MIME-Version: 1.0\r\n";
    $replyHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    $replyHeaders .= "From: ThermoProfi GmbH <info@thermoprofi-frankfurt.de>\r\n";

    $replyBody = "
    <!DOCTYPE html>
    <html lang='de'><head><meta charset='UTF-8'><style>
      body { font-family: Arial, sans-serif; color: #0F172A; background: #F8FAFC; margin:0; padding:0; }
      .wrap { max-width: 520px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid #DBEAFE; }
      .header { background: linear-gradient(135deg, #1B3A6B, #2D5FA0); padding: 28px 32px; }
      .header h1 { margin:0; color:#fff; font-size:20px; }
      .body { padding: 28px 32px; font-size: 15px; line-height: 1.7; color: #475569; }
      .body strong { color: #0F172A; }
      .cta { display:inline-block; margin-top:20px; padding:12px 24px; background:linear-gradient(135deg,#F97316,#EA580C); color:#fff; border-radius:50px; text-decoration:none; font-weight:600; font-size:14px; }
      .footer { background: #EFF6FF; padding: 16px 32px; font-size: 12px; color: #475569; text-align: center; }
    </style></head>
    <body>
    <div class='wrap'>
      <div class='header'><h1>Vielen Dank, {$name}!</h1></div>
      <div class='body'>
        <p>Ihre Anfrage ist bei uns angekommen und wird umgehend bearbeitet.</p>
        <p>Wir melden uns innerhalb von <strong>2 Stunden</strong> bei Ihnen zurück — zu unseren Bürozeiten werktags 07:00 – 18:00 Uhr.</p>
        <p>Bei einem <strong>Notfall</strong> erreichen Sie uns rund um die Uhr unter:</p>
        <a href='tel:06912345678' class='cta'>📞 069 123 456 78</a>
        <p style='margin-top:28px'>Mit freundlichen Grüßen,<br><strong>Ihr ThermoProfi-Team</strong></p>
      </div>
      <div class='footer'>ThermoProfi GmbH &bull; Musterstraße 42, 60311 Frankfurt &bull; thermoprofi-frankfurt.de</div>
    </div>
    </body></html>
    ";

    mail($email, 'Ihre Anfrage bei ThermoProfi GmbH', $replyBody, $replyHeaders);
    echo json_encode(['success' => true, 'message' => 'Anfrage erfolgreich gesendet.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fehler beim Senden. Bitte rufen Sie uns direkt an: 069 123 456 78']);
}
