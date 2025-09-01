<?php
if (!empty($_POST['email'])) {

    // Enable / Disable Mailchimp
    $enable_mailchimp = 'yes'; // yes OR no

    // Enable / Disable SMTP
    $enable_smtp = 'no'; // yes OR no

    // Email Receiver Address
    $receiver_email = 'info@yourdomain.com';

    // Email Receiver Name for SMTP Email
    $receiver_name = 'Your Name';

    // Email Subject
    $subject = 'Subscribe Newsletter form details';

    $email = $_POST['email'];

    if ($enable_mailchimp == 'no') { // Simple / SMTP Email

        $name = isset($_POST['name']) ? $_POST['name'] : '';

        $message = '
        <html>
        <head>
        <title>HTML email</title>
        </head>
        <body>
        <table width="50%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
        <td colspan="2" align="center" valign="top"><img style=" margin-top: 15px; " src="http://www.yourdomain.com/images/logo-email.png" ></td>
        </tr>
        <tr>
        <td width="50%" align="right">&nbsp;</td>
        <td align="left">&nbsp;</td>
        </tr>';
        if (!empty($name)) {
            $message .= '<tr>
            <td align="right" valign="top" style="border-top:1px solid #dfdfdf; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#000; padding:7px 5px 7px 0;">Name:</td>
            <td align="left" valign="top" style="border-top:1px solid #dfdfdf; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#000; padding:7px 0 7px 5px;">' . htmlspecialchars($name) . '</td>
            </tr>';
        }
        $message .= '<tr>
        <td align="right" valign="top" style="border-top:1px solid #dfdfdf; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#000; padding:7px 5px 7px 0;">Email:</td>
        <td align="left" valign="top" style="border-top:1px solid #dfdfdf; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#000; padding:7px 0 7px 5px;">' . htmlspecialchars($email) . '</td>
        </tr>
        </table>
        </body>
        </html>
        ';

        if ($enable_smtp == 'no') { // Simple Email

            // Always set content-type when sending HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            // More headers
            $headers .= 'From: <' . $email . '>' . "\r\n";
            if (mail($receiver_email, $subject, $message, $headers)) {

                // Redirect to success page
                $redirect_page_url = !empty($_POST['redirect']) ? $_POST['redirect'] : '';
                if (!empty($redirect_page_url)) {
                    header("Location: " . $redirect_page_url);
                    exit();
                }

                //Success Message
                echo '{ "alert": "alert-success", "message": "Your message has been sent and you have been successfully subscribed to our email list!" }';
            } else {
                //Fail Message
                echo '{ "alert": "alert-danger", "message": "Your message could not be sent!" }';
            }

        } else { // SMTP

            // Email Receiver Addresses
            $toemailaddresses = array();
            $toemailaddresses[] = array(
                'email' => $receiver_email, // Your Email Address
                'name' => $receiver_name // Your Name
            );

            require 'phpmailer/Exception.php';
            require 'phpmailer/PHPMailer.php';
            require 'phpmailer/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer();

            $mail->isSMTP();
            $mail->Host = 'YOUR_SMTP_HOST'; // Your SMTP Host
            $mail->SMTPAuth = true;
            $mail->Username = 'YOUR_SMTP_USERNAME'; // Your Username
            $mail->Password = 'YOUR_SMTP_PASSWORD'; // Your Password
            $mail->SMTPSecure = 'ssl'; // Your Secure Connection
            $mail->Port = 465; // Your Port
            $mail->setFrom($email, $name);

            foreach ($toemailaddresses as $toemailaddress) {
                $mail->AddAddress($toemailaddress['email'], $toemailaddress['name']);
            }

            $mail->Subject = $subject;
            $mail->isHTML(true);

            $mail->Body = $message;

            if ($mail->send()) {

                // Redirect to success page
                $redirect_page_url = !empty($_POST['redirect']) ? $_POST['redirect'] : '';
                if (!empty($redirect_page_url)) {
                    header("Location: " . $redirect_page_url);
                    exit();
                }

                //Success Message
                echo '{ "alert": "alert-success", "message": "Your message has been sent and you have been successfully subscribed to our email list!" }';
            } else {
                //Fail Message
                echo '{ "alert": "alert-danger", "message": "Your message could not be sent!" }';
            }
        }

    } else { // Mailchimp

        $api_key = '6b670257682753672ef1374f58bebc94-us17'; // Your MailChimp API Key
        $list_id = 'e67d5c9e49'; // Your MailChimp List ID
        $status = 'subscribed';
        $f_name = !empty($_POST['name']) ? $_POST['name'] : substr($email, 0, strpos($email, '@'));

        // Mailchimp API endpoint
        $data_center = substr($api_key, strpos($api_key, '-') + 1);
        $url = "https://{$data_center}.api.mailchimp.com/3.0/lists/{$list_id}/members/" . md5(strtolower($email));

        // Prepare data according to Mailchimp v3.0 API
        $data = array(
            'email_address' => $email,
            'status_if_new' => $status,
            'status' => $status,
            'merge_fields' => array('FNAME' => $f_name)
        );

        $json_data = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $api_key);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = !empty($response) ? json_decode($response) : '';

        if ($httpCode == 200 && !empty($result->status) && $result->status == 'subscribed') {

            // Redirect to success page
            $redirect_page_url = !empty($_POST['redirect']) ? $_POST['redirect'] : '';
            if (!empty($redirect_page_url)) {
                header("Location: " . $redirect_page_url);
                exit();
            }

            //Success Message
            echo '{ "alert": "alert-success", "message": "You have been successfully subscribed to our email list!" }';
        } else {
            //Fail Message
            $error_message = !empty($result->detail) ? $result->detail : "Your message could not be sent!";
            echo '{ "alert": "alert-danger", "message": "' . htmlspecialchars($error_message) . '" }';
        }
    }
} else {
    //Empty Email Message
    echo '{ "alert": "alert-danger", "message": "Please add an email address!" }';
}
?>
