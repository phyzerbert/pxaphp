<?php
	
	namespace App\Controllers;

    use Core\View;
    use App\Models\User;
    use App\Auth;

    /**
     * Sign In Controller
     */
    class Signin extends \Core\Controller
    {
        /**
         * Show the login page
         * URL: /signin
         *
         * @return void
         */
        public function indexAction()
        {
            /* Checking if user is not already signed in */
            $this->requireSignout();

            /* Display sign in template */
            View::renderTemplate('Signin/signin.twig');
        }


        /**
         * Log in a user
         *
         * @return void
         */
        public function newAction()
        {
            /* Trying to sign in user by email and password */
            $user = User::authenticate($_POST['email'], $_POST['password']);

            /* Remember me option */
            $remember_me = isset($_POST['remember_me']);

            /* Checking if user was found */
            if ($user) {

                /* Sign in function (add sessions) */
                Auth::signin($user, $remember_me);

                /* Redirect to requested page or index otherwise */
                $this->redirect(Auth::getRequestedPage());

            } else {

                /* Returning sign in template with errors */
                View::renderTemplate('Signin/signin.twig', [
                    'email' => $_POST['email'],
                    'remember_me' => $remember_me,
                    'error' => 1
                ]);
            }
        }


        /**
         * Log out a user
         * URL: /signout
         *
         * @return void
         */
        public function destroyAction()
        {
            /* Sign out user */
            Auth::signout();

            /* Redirect to index page */
            $this->redirect('/');
        }
    }