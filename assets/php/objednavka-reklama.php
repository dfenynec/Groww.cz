<?php
// assets/php/objednavka_reklama.php

$csvFile = __DIR__ . '/objednavky.csv';

// Hlavička CSV souboru (nové pole mail_status)
$header = [
    'cislo_objednavky',
    'datum',
    'sablona',
    'jmeno',
    'prijmeni',
    'firma',
    'ic',
    'email',
    'telefon',
    'adresa',
    'mesto',
    'psc',
    'stat',
    'domena',
    'hosting',
    'gdpr',
    'cena',
    'payment_option',
    'mail_status'
];

$mail_status = 'neodeslán';

// Pomocná funkce pro JSON odpověď a ukončení
function respond_json($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

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
    if ($lines && count($lines) > 1) {
        $lastLine = end($lines);
        $parts = explode(';', $lastLine);
        if (isset($parts[0]) && preg_match('/^(\d{8})(\d{4})$/', $parts[0], $matches)) {
            $orderNumber = intval($matches[2]) + 1;
        } else {
            $orderNumber = count($lines);
        }
    }
    $orderId = date('ymd') . str_pad($orderNumber, 4, '0', STR_PAD_LEFT);

    // Data z POST
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

    // Zápis do CSV
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

    if ($http_code < 200 || $http_code >= 300) {
        throw new Exception('Nepodařilo se uložit objednávku do Google Sheets.');
    }

    // Statický Stripe platební odkaz pro reklamu (nahraď svým skutečným odkazem)
    $stripe_url = 'https://buy.stripe.com/28EcN6cRldg517x7i433W02?locale=cs';

    // Úspěch – vrať JSON pro frontend
    respond_json([
        'message' => 'Objednávka byla úspěšně odeslána. Budete přesměrováni na platební bránu Stripe.',
        'order_id' => $orderId,
        'stripe_url' => $stripe_url
    ]);

} catch (Exception $e) {
    respond_json([
        'message' => 'Chyba při odesílání objednávky: ' . $e->getMessage(),
        'stripe_url' => null
    ], 500);
}
?>

