<?php
// assets/php/objednavka.php
require __DIR__ . '/../../email-templates/phpmailer/Exception.php';
require __DIR__ . '/../../email-templates/phpmailer/PHPMailer.php';
require __DIR__ . '/../../email-templates/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// --- LOGOVÁNÍ DO JSON ---
function json_log($data) {
    $file = __DIR__ . '/debug.json';
    $log = [];
    if (file_exists($file) && filesize($file) > 0) {
        $log = json_decode(file_get_contents($file), true);
        if (!is_array($log)) $log = [];
    }
    $log[] = [
        'time' => date('Y-m-d H:i:s'),
        'data' => $data
    ];
    file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Globální handlery pro chyby a výjimky
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) json_log(['Fatal error' => $error]);
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    json_log(['PHP error' => compact('errno', 'errstr', 'errfile', 'errline')]);
});
set_exception_handler(function($exception) {
    json_log(['Uncaught exception' => $exception->getMessage()]);
});

json_log('Start objednavka.php');
json_log(['POST' => $_POST]);

$csvFile = __DIR__ . '/objednavky.csv';

// Hlavička CSV souboru
$header = [
    'cislo_objednavky','datum','sablona','jmeno','prijmeni','firma','ic','email','telefon','adresa','mesto','psc','stat','domena','hosting','gdpr','cena','payment_option','mail_status'
];

$mail_status = 'neodeslán';

try {
    // Pokud CSV neexistuje, vytvoř s hlavičkou
    if (!file_exists($csvFile)) {
        if (file_put_contents($csvFile, implode(';', $header) . "\n") === false) {
            throw new Exception('Nepodařilo se vytvořit soubor objednávek.');
        }
    }

    // Generování čísla objednávky
    $orderNumber = 1;
    $lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (count($lines) > 1) {
        $lastLine = end($lines);
        $parts = explode(';', $lastLine);
        if (isset($parts[0]) && preg_match('/^(\d{8})(\d{4})$/', $parts[0], $matches)) {
            $orderNumber = intval($matches[2]) + 1;
        } else {
            $orderNumber = count($lines);
        }
    }
    $orderId = date('ymd') . str_pad($orderNumber, 4, '0', STR_PAD_LEFT);

    // Nová pole z POST
    $template = $_POST['sablona'] ?? '';
    $jmeno = $_POST['jmeno'] ?? '';
    $prijmeni = $_POST['prijmeni'] ?? '';
    $firma = $_POST['firma'] ?? '';
    $ic = $_POST['ic'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    $adresa = $_POST['adresa'] ?? '';
    $mesto = $_POST['mesto'] ?? '';
    $psc = $_POST['psc'] ?? '';
    $stat = $_POST['stat'] ?? '';
    $domena = $_POST['domena'] ?? '';
    $hosting = $_POST['hosting'] ?? '';
    $gdpr = isset($_POST['terms_condition']) ? 'ano' : 'ne';
    $cena = $_POST['cena'] ?? '';
    $payment_option = $_POST['payment_option'] ?? '';

    json_log(['Objednávka' => compact(
        'orderId', 'template', 'jmeno', 'prijmeni', 'firma', 'ic', 'email', 'telefon', 'adresa', 'mesto', 'psc', 'stat', 'domena', 'hosting', 'gdpr', 'cena', 'payment_option'
    )]);

    // Zápis do CSV včetně statusu e-mailu
    $radek = [
        $orderId,
        date('Y-m-d H:i:s'),
        $template,
        $jmeno,
        $prijmeni,
        $firma,
        $ic,
        $email,
        $telefon,
        $adresa,
        $mesto,
        $psc,
        $stat,
        $domena,
        $hosting,
        $gdpr,
        $cena,
        $payment_option,
        $mail_status
    ];
    if (file_put_contents($csvFile, implode(';', $radek) . "\n", FILE_APPEND) === false) {
        throw new Exception('Nepodařilo se uložit objednávku do CSV.');
    }

    // Odeslání do SheetsDB
    $sheetsdb_url = 'https://sheetdb.io/api/v1/1e589fzq733tq';
    $data = [
        'cislo_objednavky' => $orderId,
        'datum' => date('Y-m-d H:i:s'),
        'sablona' => $template,
        'jmeno' => $jmeno,
        'prijmeni' => $prijmeni,
        'firma' => $firma,
        'ic' => $ic,
        'email' => $email,
        'telefon' => $telefon,
        'adresa' => $adresa,
        'mesto' => $mesto,
        'psc' => $psc,
        'stat' => $stat,
        'domena' => $domena,
        'hosting' => $hosting,
        'gdpr' => $gdpr,
        'cena' => $cena,
        'payment_option' => $payment_option,
        'mail_status' => $mail_status
    ];

    $ch = curl_init($sheetsdb_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $sheetsdb_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    json_log(['SheetsDB response' => $sheetsdb_response, 'HTTP code' => $http_code]);

    if ($http_code < 200 || $http_code >= 300) {
        throw new Exception('Nepodařilo se uložit objednávku do Google Sheets.');
    }

    // --- Odeslání e-mailu zákazníkovi + kopie tobě ---
 

    $mail = new PHPMailer(true);

    try {
        // SMTP nastavení
        $mail->isSMTP();
        $mail->Host = 'mail.webglobe.cz';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@groww.cz';
        $mail->Password = 'G0cfOwjP';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('info@groww.cz', 'Groww.cz');
        $mail->addAddress($email, $jmeno . ' ' . $prijmeni);
        $mail->addBCC('info@groww.cz', ' ');

        $mail->Subject = 'Potvrzení objednávky #' . $orderId . ' - Groww.cz';
// Nový HTML e-mail ve stylu Groww digital
$mailBody = '<!-- Moderní HTML šablona e-mailu pro Groww digital podle Mailchimp stylu a tvých barev -->
<!DOCTYPE html>
<html lang="cs" style="margin:0;padding:0;">
<head>
  <meta charset="UTF-8">
  <title>Začínáme s vaším webem – Groww.</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { background: #FEFBFB; margin: 0; padding: 0; font-family: Arial, sans-serif; color: #222; }
    .email-container { max-width: 520px; margin: 32px auto; background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 40px 32px 32px 32px; }
    .header { text-align: center; margin-bottom: 24px; }
    .header-logo { height: 38px; margin-bottom: 6px; }
    .celebrate { font-size: 2.1rem; }
    h1 { font-size: 1.5rem; color: #FF6B43; margin: 0 0 18px 0; font-weight: bold; }
    p { font-size: 1.1rem; margin: 0 0 18px 0; }
    ul { padding-left: 20px; }
    .button-wrap { text-align: center; margin: 28px 0 18px 0; }
    .button {
      display: inline-block;
      background: #FF6B43;
      color: #FEFBFB !important;
      text-decoration: none;
      font-weight: bold;
      padding: 16px 32px;
      border-radius: 50px;
      font-size: 1.1rem;
      letter-spacing: 0.01em;
      border: none;
    }
    .section { margin: 32px 0 18px 0; }
    .footer {
      margin-top: 36px;
      font-size: 0.95rem;
      color: #FEFBFB;
      background: #1F232E;
      border-radius: 0 0 14px 14px;
      padding: 24px 18px 18px 18px;
      text-align: center;
    }
    .footer a { color: #FF6B43; text-decoration: underline; }
    .guide-link { font-size: 1rem; color: #FF6B43; font-weight: 500; }
    @media (max-width: 600px) {
      .email-container { padding: 18px 5vw; }
      .header-logo { height: 28px; }
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <img src="https://groww.cz/images/Logo@2x.png" class="header-logo" alt="Groww logo" />
      <div class="celebrate">🎉</div>
    </div>
    <h1>Začínáme s vaším webem</h1>
    <p>
      Dobrý den <b>.' htmlspecialchars($jmeno) . ' ' . htmlspecialchars($prijmeni) .'</b>,<br>
        děkujeme za vaši objednávku na Groww.cz.<br>

        Potvrzujeme přijetí poptávky a brzy vám zašleme odkaz na platební bránu Stripe nebo bankovní převod.

      </p>

      <ul>

        <li><b>Číslo objednávky:</b> ' . htmlspecialchars($orderId) . '</li>

        <li><b>Vybraná šablona:</b> ' . htmlspecialchars($template) . '</li>

        <li><b>Cena:</b> ' . htmlspecialchars($cena) . ' Kč</li>

      </ul>
    Také pro vás připravím Google Disk složku, do které můžete vkládat podklady k vašemu novému webu:
    </p>
    <ul>
      <li>Strukturu webu (seznam stránek)</li>
      <li>Fotografie a obrázky</li>
      <li>Texty ke stránkám</li>
    </ul>
    <div class="section">
      <p>Pokud budete mít jakékoliv dotazy, rádi vám poradíme.</p>
      <p>S pozdravem,<br>David z Groww.cz</p>
    </div>
  </div>
  <div class="footer">
    <a class="guide-link" href="https://groww.cz/navody-dokumenty.php">Návod na celý proces</a>
    <br><br>
    &copy; 2025 Groww. Všechna práva vyhrazena.<br>
  </div>
</body>
</html>';$mail->isHTML(true);
$mail->Body = $mailBody;

        if ($mail->send()) {
            $mail_status = 'odeslán';
            json_log('E-mail odeslán');
        } else {
            $mail_status = 'neodeslán';
            json_log('E-mail NEODESLÁN');
        }
    } catch (Exception $e) {
        $mail_status = 'neodeslán';
        json_log(['PHPMailer error' => $e->getMessage()]);
    }

    // Výstup pro uživatele
    echo '<div class="alert alert-success alert-dismissable">';
    echo "<h5>Děkujeme za objednávku!</h5>";
    echo "<p>Číslo objednávky: <b>" . htmlspecialchars($orderId) . "</b></p>";
    echo "<p>Brzy Vám zašleme odkaz na platební bránu Stripe nebo bankovní převod na e-mail: <b>" . htmlspecialchars($email) . "</b></p>";
    echo "<p>Vybraná služba: <b>" . htmlspecialchars($template) . "</b></p>";
    echo '</div>';

} catch (Exception $e) {
    json_log(['Exception' => $e->getMessage()]);
    echo '<div class="alert alert-danger alert-dismissable">';
    echo "<h5>Chyba při odesílání objednávky!</h5>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Zkuste to prosím znovu, nebo nás kontaktujte e-mailem či telefonicky.</p>";
    echo '</div>';
}
?>
