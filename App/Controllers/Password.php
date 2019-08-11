<?php

    namespace App\Controllers;

    use \Core\View;
    use \App\Models\User;

    /**
     * Password Controller
     */
    class Password extends \Core\Controller
    {
        /**
         * Show the forgotten password page
         * URL: /password/forgot
         *
         * @return void
         */
        public function forgotAction()
        {
            /* Load template */
            View::renderTemplate('Password/forgot.twig');
        }


        /**
         * Send the password reset link to the supplied email
         * and redirect to /password/request-reset
         *
         * @return void
         */
        public function requestResetAction()
        {
            /* Sending reset password link email if user exists */
            User::sendPasswordReset($_POST['email']);

            /* Getting email provider */
            if (strpos($_POST['email'], '@gmail.') !== false) {
                $email_provider['name'] = 'GMail';
                $email_provider['link'] = 'https://mail.google.com/mail/ca';
            } else if (strpos($_POST['email'], '@aol.') !== false) {
                $email_provider['name'] = 'AOL';
                $email_provider['link'] = 'https://mail.aol.com';
            } else if (strpos($_POST['email'], '@yandex.') !== false) {
                $email_provider['name'] = 'Yandex';
                $email_provider['link'] = 'https://mail.yandex.com';
            } else if (strpos($_POST['email'], '@yahoo.') !== false) {
                $email_provider['name'] = 'Yahoo';
                $email_provider['link'] = 'https://login.yahoo.com/';
            } else if (strpos($_POST['email'], '@mail.com') !== false) {
                $email_provider['name'] = 'Mail.com';
                $email_provider['link'] = 'https://mail.com';
            } else if (strpos($_POST['email'], '@outlook.') !== false) {
                $email_provider['name'] = 'Outlook.com';
                $email_provider['link'] = 'https://outlook.com';
            } else if (strpos($_POST['email'], '@proton') !== false) {
                $email_provider['name'] = 'ProtonMail';
                $email_provider['link']= 'https://protonmail.com';
            } else {
                $explode = explode("@", $_POST['email']);
                $email_provider['name'] = $explode[1];
                $email_provider['link'] = 'http://'.$explode[1];
            }

            /* Load view template */
            View::renderTemplate('Password/reset_requested.twig', [
                'email' => $_POST['email'],
                'email_provider' => $email_provider
            ]);
        }


        /**
         * Reset the user's password function
         *
         * @return void
         */
        public function resetPasswordAction()
        {
            /* Getting token */
            $token = $_POST['token'];

            /* Checking if password token exists or redirect to old token page */
            $user = $this->getUserOrExit($token);

            /* Trying to reset password */
            if ($user->resetPassword($_POST['password'])) {

                /* Load success reset template */
                View::renderTemplate('Password/reset_success.twig');

            } else {

                /* Load error reset template */
                View::renderTemplate('Password/reset.twig', [
                    'token' => $token,
                    'user' => $user
                ]);
            }
        }


        /**
         * Show the reset password form
         * when is clicked link from reset password email
         * which looks like /password/reset/{{ token }}
         *
         * @return void
         */
        public function resetAction()
        {
            /* Getting token from reset password link */
            $token = $this->route_params['token'];

            /* Checking if password token exists or redirect to old token page */
            $user = $this->getUserOrExit($token);

            /* Load template for creating new password */
            View::renderTemplate('Password/reset.twig', [
                'token' => $token
            ]);
        }


        /**
         * Find the user's model associated with the password reset token, or end the request with a message
         *
         * @param string $token Password reset token sent to user
         *
         * @return mixed User object if found and the token hasn't expired, null otherwise
         */
        protected function getUserOrExit($token)
        {
            /* Searching for user with token */
            $user = User::getByPasswordReset($token);

            /* If user exists - return it, otherwise display view about expired token and exit */
            if ($user) {

                return $user;

            } else {

                /* Load temlate */
                View::renderTemplate('Password/token_expired.twig');
                exit;
            }
        }
    }