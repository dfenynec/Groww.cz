<?php
if (!empty($_POST['email'])) {

    // Enable / Disable Mailchimp
    $enable_mailchimp = 'yes'; // yes OR no

    // Mailchimp API Key and List ID
    $api_key = '6b670257682753672ef1374f58bebc94-us17';
    $list_id = 'e67d5c9e49';

    // Enable / Disable SMTP
    $enable_smtp = 'yes'; // yes OR no

    // Email Receiver Address
    $receiver_email = 'info@groww.cz';

    // Email Receiver Name for SMTP Email
    $receiver_name = 'David Fenynec';

    // Email Subject
    $subject = 'Kontaktní formulář Groww.cz';

    // Google reCaptcha secret Key
    $grecaptcha_secret_key = '6Le3G7krAAAAAPQ9z5AQFgSSBsNrHlujxCgVbYSC';

    $from = $_POST['email'];
    $name = isset($_POST['name']) ? $_POST['name'] : '';

    // --- reCaptcha Verification ---
    if (!empty($grecaptcha_secret_key) && !empty($_POST['g-recaptcha-response'])) {
        $token = $_POST['g-recaptcha-response'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $grecaptcha_secret_key, 'response' => $token)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $arrResponse = json_decode($response, true);

        if (isset($_POST['action']) && !(isset($arrResponse['success']) && $arrResponse['success'] == '1' && $arrResponse['action'] == $_POST['action'] && $arrResponse['score'] >= 0.5)) {
            echo '{ "alert": "alert-danger", "message": "Bohužel, nepodařilo se ověření reCaptcha!" }';
            die;
        } else if (!isset($_POST['action']) && !(isset($arrResponse['success']) && $arrResponse['success'] == '1')) {
            echo '{ "alert": "alert-danger", "message": "Bohužel, nepodařilo se ověření reCaptcha!" }';
            die;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $prefix = !empty($_POST['prefix']) ? $_POST['prefix'] : '';
        $submits = $_POST;
        $fields = array();
        foreach ($submits as $field_name => $value) {
            if (empty($value)) continue;
            $field_name = str_replace($prefix, '', $field_name);
            $field_name = function_exists('mb_convert_case') ? mb_convert_case($field_name, MB_CASE_TITLE, "UTF-8") : ucwords($field_name);
            if (is_array($value)) $value = implode(', ', $value);
            $fields[$field_name] = nl2br(filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS));
        }

        $response = array();
        foreach ($fields as $fieldname => $fieldvalue) {
            $response[] = '<tr>
                <td align="right" valign="top" style="border-top:1px solid #dfdfdf; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#000; padding:7px 5px 7px 0;">' . $fieldname . ': </td>
                <td align="left" valign="top" style="border-top:1px solid #dfdfdf; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#000; padding:7px 0 7px 5px;">' . $fieldvalue . '</td>
            </tr>';
        }

        $message = '<html>
            <head>
                <title>HTML email</title>
            </head>
            <body>
                <table width="50%" border="0" align="center" cellpadding="0" cellspacing="0">
                <tr>
                <td colspan="2" align="center" valign="top"><img style="margin-top: 15px;" src="http://www.groww.cz/images/logo.png" ></td>
                </tr>
                <tr>
                <td width="50%" align="right">&nbsp;</td>
                <td align="left">&nbsp;</td>
                </tr>
                ' . implode('', $response) . '
                </table>
            </body>
            </html>';

        // --- Mailchimp Integration (only if checkbox is checked) ---
        if ($enable_mailchimp == 'yes') {
            $data_center = substr($api_key, strpos($api_key, '-') + 1);
            $url = "https://{$data_center}.api.mailchimp.com/3.0/lists/{$list_id}/members/" . md5(strtolower($from));

            // Set status based on checkbox
            $status = (isset($_POST['subscribe']) && $_POST['subscribe'] === 'yes') ? 'subscribed' : 'unsubscribed';

            $mc_data = array(
                'email_address' => $from,
                'status_if_new' => $status,
                'merge_fields' => array(
                    'FNAME'   => $name,
                    'SERVICE' => isset($_POST['select']) ? $_POST['select'] : '',
                    'TEMPLATE'=> isset($_POST['sablona']) ? $_POST['sablona'] : '',
                    'MESSAGE' => isset($_POST['comment']) ? mb_substr($_POST['comment'], 0, 255) : ''
                )
            );
            $json_mc_data = json_encode($mc_data);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $api_key);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_mc_data);
            $mc_response = curl_exec($ch);
            $mc_httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $mc_result = !empty($mc_response) ? json_decode($mc_response) : '';
            if ($mc_httpCode != 200) {
                // Optionally log or handle Mailchimp errors
                // $error_message = !empty($mc_result->detail) ? $mc_result->detail : "Mailchimp error";
            }
        }

        $sheetdb_url = 'https://sheetdb.io/api/v1/l0r8v5gi9g726';
$data = [
    'data' => [
        'name' => $name,
        'email' => $from,
        'service' => $_POST['select'],
        'template' => $_POST['sablona'],
        'message' => $_POST['comment'],
        'timestamp' => date('Y-m-d H:i:s')
    ]
];
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents($sheetdb_url, false, $context);


        // --- Email Sending ---
        if ($enable_smtp == 'no') { // Simple Email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: ' . $fields['Name'] . ' <' . $fields['Email'] . '>' . "\r\n";
            if (mail($receiver_email, $subject, $message, $headers)) {
                $redirect_page_url = !empty($_POST['redirect']) ? $_POST['redirect'] : '';
                if (!empty($redirect_page_url)) {
                    header("Location: " . $redirect_page_url);
                    exit();
                }
                echo '{ "alert": "alert alert-success alert-dismissable", "message": "Paráda! Vaše zpráva byla odeslána!" }';
            } else {
                echo '{ "alert": "alert alert-danger alert-dismissable", "message": "Bohužel, zprávu se nepodařilo odeslat :(" }';
            }
        } else { // SMTP
            $toemailaddresses = array();
            $toemailaddresses[] = array(
                'email' => $receiver_email,
                'name' => $receiver_name
            );
            require 'phpmailer/Exception.php';
            require 'phpmailer/PHPMailer.php';
            require 'phpmailer/SMTP.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'mail.webglobe.cz';
            $mail->SMTPAuth = true;
            $mail->Username = 'info@groww.cz';
            $mail->Password = 'G0cfOwjP';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
            $mail->setFrom($fields['Email'], $fields['Name']);
            foreach ($toemailaddresses as $toemailaddress) {
                $mail->AddAddress($toemailaddress['email'], $toemailaddress['name']);
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $message;
            if ($mail->send()) {
                $redirect_page_url = !empty($_POST['redirect']) ? $_POST['redirect'] : '';
                if (!empty($redirect_page_url)) {
                    header("Location: " . $redirect_page_url);
                    exit();
                }
                echo '{ "alert": "alert alert-success alert-dismissable", "message": "Paráda! Vaše zpráva byla odeslána!" }';
            } else {
                echo '{ "alert": "alert alert-danger alert-dismissable", "message": "Bohužel, zprávu se nepodařilo odeslat :(" }';
            }
        }
    }
} else {
    echo '{ "alert": "alert alert-danger alert-dismissable", "message": "Prosím zadejte svou emailovou adresu." }';
}
?>

