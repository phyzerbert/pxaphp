<?php

    namespace Core;

    use \App\Auth;
    use \App\Flash;
    use \App\Config;

    /**
     * Base Controller Class
     */
    abstract class Controller
    {
        /**
         * Error variable
         * @var string
         */
        public $errors = [];

        /**
         * Parameters from the matched route
         * @var array
         */
        protected $route_params = [];

        /**
         * Config parameters used by controllers
         * @var array
         */
        public $config = [];


        /**
         * Class constructor
         *
         * @param array $route_params - Parameters from the route
         *
         * @return void
         */
        public function __construct(array $route_params)
        {
            $this->route_params = $route_params;

            $this->loadConfig();
        }


        /**
         * Magic method called when a non-existent or inaccessible method is
         * called on an object of this class. Used to execute before and after
         * filter methods on action methods. Action methods need to be named
         * with an "Action" suffix, e.g. indexAction, showAction etc.
         *
         * @param string $name - Method name
         * @param array $args - Arguments passed to the method
         *
         * @return void
         */
        public function __call(string $name, array $args)
        {
            $method = $name . 'Action';

            if (method_exists($this, $method)) {
                if ($this->before() !== false) {
                    call_user_func_array([$this, $method], $args);
                    $this->after();
                }
            } else {
                throw new \Exception("Method $method not found in controller " . get_class($this));
            }
        }


        /**
         * Before filter - called before an action method.
         *
         * @return void
         */
        protected function before()
        {
        }


        /**
         * After filter - called after an action method.
         *
         * @return void
         */
        protected function after()
        {
        }


        /**
         * Function which load configs into $config array
         *
         * @return void
         */
        protected function loadConfig()
        {
            $this->config = Config::getValues();
        }


        /**
         * Redirect to a different page
         * @param string $url - The relative URL
         *
         * @return void
         */
        public function redirect(string $url)
        {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            header('Location: ' . $protocol . $_SERVER['HTTP_HOST'] . $url, true, 303);
            exit;
        }


        /**
         * Require user to be signed in to access requested page
         * Remember the page before for redirecting to if after sign in
         *
         * @return void
         */
        public function requireSignin()
        {
            if (! Auth::getUser()) {

                Flash::addMessage('Sign in to access that page', Flash::INFO);
                
                Auth::saveRequestedPage();

                $this->redirect('/signin');
            }
        }


        /**
         * Require user to be signed out to access requested page
         * Redirect to homepage
         *
         * @return void
         */
        public function requireSignout()
        {
            if (Auth::getUser()) {

                $this->redirect('/');
            }
        }
    }