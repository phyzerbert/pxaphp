<?php
	
	namespace App\Controllers;

    use Core\View;
    use App\Models\User;
    use App\Auth;

    /**
     * Sign Up Controller
     */
    class Signup extends \Core\Controller
    {
        /**
         * Show the signup page
         * URL: /signup
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking is user is not signed in */
            $this->requireSignout();

            /* Displaying sign up template */
            View::renderTemplate('Signup/signup.twig');
        }


        /**
         * Show the success signup page
         * URL: /signup/success
         *
         * @return void
         */
        public function successAction()
        {
            /* Checking is user is not signed in */
            $this->requireSignout();

            /* Displaying view template */
            View::renderTemplate('Signup/success.twig');
        }


        /**
         * Sign up a new user
         *
         * @return void
         */
        public function newAction()
        {
            /* Creating new user */
            $user = new User($_POST);

            /* Trying to save new user into database */
            if ($user->signup()) {

                /* Checking if is required email activation */
                if ($this->config->email_activation) {

                    /* Sending confirmation email */
                    $user->sendActivationEmail();

                    /* Displaying template about success sign up */
                    $this->redirect('/signup/success');

                } else {

                    /* If activation is disabled - sign in by new email */
                    $user = User::getByEmail($user->email);

                    /* Auth through email */
                    Auth::signin($user);

                    /* Redirecting to index page */
                    $this->redirect('/');
                }

            } else {

                /* Displaying view template with errors during sign up */
                View::renderTemplate('Signup/signup.twig', [
                    'user' => $user
                ]);
            }
        }


        /**
         * Activate a new account and sign in
         * URL: signup/activate/{{ token }}
         *
         * @return void
         */
        public function activateAction()
        {
            /* Checking is user is not signed in */
            $this->requireSignout();
            
            /* Activating account using token */
            User::activate($this->route_params['token']);

            /* Redirecting to activated account page */
            $this->redirect('/signup/activated');       
        }


        /**
         * Show the success signup page
         * URL: /signup/activated
         *
         * @return void
         */
        public function activatedAction()
        {
            /* Checking is user is not signed in */
            $this->requireSignout();

            /* Displaying activated account template */
            View::renderTemplate('Signup/activated.twig');
        }
    }