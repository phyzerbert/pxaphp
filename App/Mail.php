<?php

    namespace App;

    use App\Config;
    use Mailgun\Mailgun;
    use PHPMailer\PHPMailer\PHPMailer;

    /**
     * Mail Class
     * Available php mail() / Mailgun / PHPMailer
     * Can be set in Config.php file
     */
    class Mail
    {
        /**
         * Send a message
         *
         * @param string $to - Recipient
         * @param string $subject - Subject
         * @param string $text - Text-only content of the message
         * @param string $html - HTML content of the message
         *
         * @return void
         */
        public static function send(string $to, string $subject, string $text, string $html)
        {
            if (Config::getValues('email_option') == 2) {

                /* PHPMailer */

                $mail = new PHPMailer;

                // $mail->SMTPDebug = 3;
                $mail->isSMTP();
                $mail->Host = Config::getValues('smtp_host');
                $mail->SMTPAuth = true;
                $mail->Username = Config::getValues('smtp_username');
                $mail->Password = Config::getValues('smtp_password');
                $mail->SMTPSecure = Config::getValues('smtp_port');;
                $mail->Port = Config::getValues('smtp_port');;

                $mail->From = Config::getValues('app_email');
                $mail->FromName = Config::getValues('app_email');

                $mail->addAddress($to);

                $mail->isHTML(true);

                $mail->Subject = $subject;
                $mail->Body = $html;
                $mail->AltBody = $text;

                $mail->send();

            } else if (Config::getValues('email_option') == 1) {

                /* Mailgun */

                $mg = new Mailgun(Config::getValues('mailgun_api_key'));
                $domain = Config::getValues('mailgun_domain');

                $mg->sendMessage($domain, ['from'    => Config::getValues('app_email'),
                                           'to'      => $to,
                                           'subject' => $subject,
                                           'text'    => $text,
                                           'html'    => $html]);
            } else {

                /* Default PHP mail() function */

                $headers =  'From: '.Config::getValues('app_email').'\r\n'.
                            'Reply-To: '.Config::getValues('app_email').'\r\n'.
                            'X-Mailer: PHP/' . phpversion().''.
                            'MIME-Version: 1.0\r\n'.
                            'Content-Type: text/html; charset=ISO-8859-1\r\n';
                mail($to, $subject, $html, $headers);
            }
        }
    }