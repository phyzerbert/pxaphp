<?php

	namespace App;

	use App\Models\User;
	use App\Models\RememberedLogin;

	/**
	 * Authentification Class
	 */
	class Auth
	{
		/**
		 * Signin the user
		 *
		 * @param User $user - The user model
		 * @param Boolean $remember_me - True for remember the login
		 *
		 * @return void
		 */
		public static function signin($user, $remember_me = false)
		{
			session_regenerate_id(true);

            $_SESSION['user_id'] = $user->id;

            if ($remember_me) {

            	if ($user->rememberSignin()) {

            		setcookie('remember_me', $user->remember_token, $user->expiry_timestamp, '/');
            	}
            }
		}


		/**
		 * Signout the user
		 *
		 * @return void
		 */
		public static function signout()
		{
			// Unset all of the session variables
            $_SESSION = [];

            // Delete the session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            // Finally destroy the session
            session_destroy();

            static::forgetSignin();
		}


		/**
		 * Function which remember page which was requested before sign in
		 *
		 * @return void
		 */
		public static function saveRequestedPage()
		{
			$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];
		}


		/**
		 * Function which return remembered page before redirecting to sign in page
		 *
		 * @return string - return to page from session 'return_to'
		 */
		public static function getRequestedPage()
		{
			return $_SESSION['return_to'] ?? '/';
		}


		/**
		 * Get the current - signed in user from the session or remember-me cookie
		 * If session is empty - check for "remember me" cookie and sign in
		 *
		 * If user is signed in - updating last visit timestamp
		 *
		 * @return mixed - The user model or Null if user is not signed in
		 */
		public static function getUser()
		{
			if (isset($_SESSION['user_id'])) {
				
				$user = User::getById($_SESSION['user_id']);

				if ($user) {

					User::setActiveNow($_SESSION['user_id']);
				}

				return $user;
			
			} else {
				 
				return static::loginFromRememberCookie();
			}
		}


		/**
	     * Login the user from a remembered login cookie
	     *
	     * @return mixed - The user model if login cookie found; Null otherwise
	     */
	    protected static function loginFromRememberCookie()
	    {
	        $cookie = $_COOKIE['remember_me'] ?? false;

	        if ($cookie) {

	            $remembered_login = RememberedLogin::getByToken($cookie);

	            if ($remembered_login && ! $remembered_login->hasExpired()) {

	                $user = $remembered_login->getUser();

	                static::signin($user, false);

	                return $user;
	            }
	        }
	    }


	    /**
	     * Forget the remembered login if it is present
	     *
	     * @return void
	     */
	    protected static function forgetSignin()
	    {
	    	$cookie = $_COOKIE['remember_me'] ?? false;

	    	if ($cookie) {

	    		$remembered_login = RememberedLogin::getByToken($cookie);

	    		if ($remembered_login) {

	    			$remembered_login->delete();
	    		}

	    		setcookie('remember_me', '', time() - 3600);
	    	}
	    }
	}