<?php

	namespace Core;

	/**
	 * Router Class
	 * The Router class determines which controller needs to be loaded, 
	 * based on the request passed to it, and the routes defined.
	 */
	class Router 
	{
		/* Array with all routes in application (Routing table) */
		protected $routes = [];

		/* Array with all params for the matched route */
		protected $params = [];


		/**
		 * Add route function
		 * Function will add new route into route table
		 *
		 * @param string $route - the route URL
		 * @param array $params - parameters for route, like: controller, action or extra params.
		 * 
		 * @return void
		 */
		public function add(string $route, array $params = [])
		{
			/* Convert the route to a regular expression: escape forward slashes */
	        $route = preg_replace('/\//', '\\/', $route);

	        /* Convert variables e.g. {controller} {action} */
	        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z-]+)', $route);

	        /* Convert variables with custom regular expressions e.g. {id:\d+} */
	        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

	        /* Add start and end delimiters, and case insensitive flag */
	        $route = '/^' . $route . '$/i';

			$this->routes[$route] = $params;
		}


		/**
		 * Get routes function
		 * Function will return all routes from route table
		 * 
		 * @return array
		 */
		public function get()
		{
			return $this->routes;
		}


		/**
		 * Match route function
		 * Function will check if current route match any route from routes table. 
		 * Will set it parameters if will find it
		 *
		 * @param string $url - The route URL
		 * 
		 * @return boolean - True if route was found or False if was not found
		 */
		public function match(string $url)
		{
			foreach ($this->routes as $route => $params) {
	            if (preg_match($route, $url, $matches)) {
	                foreach ($matches as $key => $match) {
	                    if (is_string($key)) {
	                        $params[$key] = $match;
	                    }
	                }

	                $this->params = $params;
	                return true;
	            }
	        }

	        return false;
		}


		/**
		 * Get route's parameters function
		 * Function will return parameters of currently matched route
		 * 
		 * @return array with params
		 */
		public function getParams()
		{
			return $this->params;
		}


		/**
	     * Dispatch function
	     * Dispatch the route, creating the controller object and running the action method
	     *
	     * @param string $url - The route URL
	     *
	     * @return void
	     */
	    public function dispatch(string $url)
	    {
	    	$url = $this->removeQueryStringVariables($url);

	        if ($this->match($url)) {
	            $controller = $this->params['controller'];
	            $controller = $this->convertToStudlyCaps($controller);
	            $controller = $this->getNamespace() . $controller;

	            if (class_exists($controller)) {
	                $controller_object = new $controller($this->params);

	                $action = $this->params['action'];
	                $action = $this->convertToCamelCase($action);

	                if (preg_match('/action$/i', $action) == 0) {
	                    if (is_callable([$controller_object, $action])) {
		                    $controller_object->$action();

		                } else {
		                    throw new \Exception("Method $action (in controller $controller) not found");
		                }

	                } else {
	                    throw new \Exception("Method $action in controller $controller cannot be called directly - remove the Action suffix to call this method");
	                }
	            } else {
	                throw new \Exception("Controller class $controller not found", 404);
	            }
	        } else {
	            throw new \Exception('No route matched.', 404);
	        }
	    }


	    /**
	     * Convert the string with hyphens to StudlyCaps,
	     * e.g. post-authors => PostAuthors
	     *
	     * @param string $string - The string to convert
	     *
	     * @return string
	     */
	    protected function convertToStudlyCaps(string $string)
	    {
	        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
	    }


	    /**
	     * Convert the string with hyphens to camelCase,
	     * e.g. add-new => addNew
	     *
	     * @param string $string - The string to convert
	     *
	     * @return string
	     */
	    protected function convertToCamelCase(string $string)
	    {
	        return lcfirst($this->convertToStudlyCaps($string));
	    }


	    /**
	     * Remove the query string variables from the URL (if any). As the full
	     * query string is used for the route, any variables at the end will need
	     * to be removed before the route is matched to the routing table. For
	     * example:
	     *
	     *   URL                           $_SERVER['QUERY_STRING']  Route
	     *   -------------------------------------------------------------------
	     *   localhost                     ''                        ''
	     *   localhost/?                   ''                        ''
	     *   localhost/?page=1             page=1                    ''
	     *   localhost/posts?page=1        posts&page=1              posts
	     *   localhost/posts/index         posts/index               posts/index
	     *   localhost/posts/index?page=1  posts/index&page=1        posts/index
	     *
	     * A URL of the format localhost/?page (one variable name, no value) won't
	     * work however. (NB. The .htaccess file converts the first ? to a & when
	     * it's passed through to the $_SERVER variable).
	     *
	     * @param string $url - The full URL
	     *
	     * @return string - The URL with the query string variables removed
	     */
	    protected function removeQueryStringVariables(string $url)
	    {
	        if ($url != '') {
	            $parts = explode('&', $url, 2);
	            $url = (strpos($parts[0], '=') === false) ? $parts[0] : '';
	        }

	        return $url;
	    }


	    /**
	     * Get the namespace for the controller class. The namespace defined in the
	     * route parameters is added if present.
	     *
	     * @return string - The request URL
	     */
	    protected function getNamespace()
	    {
	        $namespace = 'App\Controllers\\';

	        if (array_key_exists('namespace', $this->params)) {
	            $namespace .= $this->params['namespace'] . '\\';
	        }

	        return $namespace;
	    }
	}