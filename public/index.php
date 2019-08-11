<?php

	/**
	 * QxA PHP
	 *
	 * Questions and Answers social platform
	 * 
	 * @package 	QxAPHP
	 * @author 		xandrCO
	 * @copyright  	2019, xandrCO
	 * @version 	1.0.0
	 * @link 		http://xandr.co/qxaphp
	 * PHP version 	7.0+
	 */


	/**
	 * Composer Autoloader
	 */
	require_once dirname(__DIR__) . '/vendor/autoload.php';


	/**
	 * Error and Exception handling. All errors reporting enabled
	 */
	error_reporting(E_ALL);
	set_error_handler('Core\Error::errorHandler');
	set_exception_handler('Core\Error::exceptionHandler');


	/**
	 * Starting Session
	 */
	session_start();


	/**
	 * Routing
	 * You can add new routes by adding {controller}, {action}, {any_other_parameter} parameters which will be extracted automatically
	 * Or can add in array parameters manually, like ['controller' => 'controller_name', 'namespace' => 'Admin']
	 * Example: 
	 * $router->add('{controller}/{id:\d+}/{action}'); with URL posts/1/edit - will load posts controller with parameter id=1 and function edit
	 */
	$router = new Core\Router();

	$router->add('', ['controller' => 'Questions', 'action' => 'index']);
	$router->add('signout', ['controller' => 'Signin', 'action' => 'destroy']);
	$router->add('password/reset/{token:[\da-f]+}', ['controller' => 'Password', 'action' => 'reset']);
	$router->add('signup/activate/{token:[\da-f]+}', ['controller' => 'Signup', 'action' => 'activate']);
	$router->add('topic/{url:[\d-a-z_A-Z]+}', ['controller' => 'Topics', 'action' => 'view']);
	$router->add('question/{url:[\d-a-z_A-Z]+}', ['controller' => 'Questions', 'action' => 'view']);
	$router->add('page/{url:[\d-a-z_A-Z]+}', ['controller' => 'Pages', 'action' => 'view']);
	$router->add('user/update', ['controller' => 'Users', 'action' => 'update']);
	$router->add('user/settings', ['controller' => 'Users', 'action' => 'settings']);
	$router->add('user/{username:[\d-a-z_A-Z]+}', ['controller' => 'Users', 'action' => 'view', 'tab' => 'topics']);
	$router->add('user/{username:[\d-a-z_A-Z]+}/{tab:[\d-a-zA-Z]+}', ['controller' => 'Users', 'action' => 'view']);
	$router->add('{controller}', ['action' => 'index']);
	$router->add('{controller}/{action}');
	$router->add('admin/{controller}/{action}', ['namespace' => 'Admin']);

	$router->dispatch($_SERVER['QUERY_STRING']);