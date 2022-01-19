<?php

/*!
 *  Tiny PHP Framework
 *
 *  MIT License
 *
 *  Copyright (c) 2020 - 2021 "Ildar Bikmamatov" <support@bayrell.org>
 * 
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 * 
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 * 
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE.
 */

namespace TinyPHP;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class App
{
	var $routes = [];
	var $models = [];
	var $commands = [];

	const ERROR_OK = 1;
	const ERROR_ITEM_NOT_FOUND = -2;
	const ERROR_HTTP_NOT_FOUND = -404;
	const ERROR_HTTP_METHOD_NOT_ALLOWED = -405;


	/**
	 * Get instance
	 */
	function get($name)
	{
		return Core::$di_container->get($name);
	}



	/**
	 * Init app
	 */
	function init()
	{
		/* Connect to database */
		app("connectToDatabase");
		
		/* Init twig */
		app("twig");
	}
	
	
	
	/**
	 * Web app run
	 */
	function run()
	{
		/* Fetch method and URI from somewhere */
		$method = $_SERVER['REQUEST_METHOD'];
		$uri = $_SERVER['REQUEST_URI'];
		
		$this->dispatchUri($method, $uri);
	}
	
	
	
	/**
	 * Add routes from class
	 */
	function addRoute($class_name, $file_name = "")
	{
		if ($file_name != "")
		{
			require_once $file_name;
		}

		$router = app(\FastRoute\RouteCollector::class);
		$obj = new $class_name();
		$obj->app = $this;
		$obj->routes($router);
		$this->routes[] = $class_name;
	}
	
	
	
	/**
	 * Add console command
	 */
	function addModel($class_name)
	{
		$this->models[] = $class_name;
	}
	
	
	
	/**
	 * Add console command
	 */
	function addConsoleCommand($class_name)
	{
		$this->commands[] = $class_name;
	}
	
	
	
	/**
	 * Create render container
	 */
	function createRenderContainer()
	{
		$container = make(RenderContainer::class);
		$container->request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
		return $container;
	}
	
	
	
	/**
	 * Action error
	 */
	function actionError($container, $e)
	{
		$container->response = make(\TinyPHP\FatalError::class)->handle_error($e, $container);
		return $container;
	}
	
	
	
	/**
	 * Action error
	 */
	function actionNotFound($container)
	{
		$container->response = make(ApiResult::class)
			->error( "HTTP 404 Not Found", -1 )
			->getResponse()
			->setStatusCode(Response::HTTP_NOT_FOUND)
		;
		return $container;
	}
	
	
	
	/**
	 * Method not allowed
	 */
	function actionNotAllowed($container)
	{
		$container->response = make(ApiResult::class)
			->error( "HTTP 405 Method Not Allowed", -1 )
			->getResponse()
			->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED)
		;
		return $container;
	}
	
	
	
	/**
	 * Method not found
	 */
	function methodNotFound($routeInfo)
	{
		$container = $this->createRenderContainer();
		$this->actionNotFound($container)->sendResponse();
	}
	
	
	
	/**
	 * Method not allowed
	 */
	function methodNotAllowed($routeInfo)
	{
		$container = $this->createRenderContainer();
		$this->actionNotAllowed($container)->sendResponse();
	}
	
	
	
	/**
	 * Method found
	 */
	function methodFound($routeInfo)
	{
		$handler = $routeInfo[1];
		$args = $routeInfo[2];
		
		$container = $this->createRenderContainer();
		$container->response = null;
		$container->handler = $handler;
		$container->args = $args;
		
		try
		{
			if ($handler instanceof \Closure)
			{
				$container = $handler($container);
			}
			else if (is_array( $handler ))
			{
				$container->action = $handler[1];
				
				$obj = $handler[0];
				if (is_object($obj) && $obj instanceof Route)
				{
					$container->route = $obj;
					$container = $obj->request_before($container);
					if ($container->response == null)
					{
						call_user_func_array($handler, [$container]);
					}
					$container = $obj->request_after($container);
				}
				else
				{
					call_user_func_array($handler, [$container]);
				}
			}
		}
		catch (\Exception $e)
		{
			$container = $this->actionError($container, $e);
		}
		
		$container->sendResponse();
	}
	
	
	
	/**
	 * Run dispatcher
	 */
	function dispatchUri($method, $uri)
	{
		/* Create dispatcher */
		$route_collector = app(\FastRoute\RouteCollector::class);
		$dispatcher = app(\FastRoute\Dispatcher::class);

		/* Strip query string (?foo=bar) and decode URI */
		if (false !== $pos = strpos($uri, '?'))
		{
			$uri = substr($uri, 0, $pos);
		}

		/* Dispatch page */
		$routeInfo = $dispatcher->dispatch($method, $uri);
		switch ($routeInfo[0])
		{
			// ... 404 Not Found
			case \FastRoute\Dispatcher::NOT_FOUND:
				$this->methodNotFound($routeInfo);
				break;

			// ... 405 Method Not Allowed
			case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				$this->methodNotAllowed($routeInfo);				
				break;

			// Found method
			case \FastRoute\Dispatcher::FOUND:
				$this->methodFound($routeInfo);
				break;
		}
		
	}
	
	
	
	/**
	 * Console app created
	 */
	function consoleAppCreated()
	{
	}
	
	
	
	/**
	 * Create console app
	 */
	function createConsoleApp()
	{
		$this->console = new \Symfony\Component\Console\Application();
		
		/* Register console commands */
		foreach ($this->commands as $class_name)
		{
			$this->console->add( new $class_name() );
		}
		
		$this->consoleAppCreated();
		
		return $this->console;
	}
	
	
	
	/**
	 * Connect to database
	 */
	static function connectToDatabase()
	{
		$capsule = new Capsule;
		
		/*
		$capsule->addConnection
		([
			'driver'    => 'mysql',
			'host'      => env("MYSQL_HOST"),
			'port'      => env("MYSQL_PORT"),
			'database'  => env("MYSQL_DATABASE"),
			'username'  => env("MYSQL_USERNAME"),
			'password'  => env("MYSQL_PASSWORD"),
			'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix'    => '',
		]);
		*/
		
		// Set event dispatcher
		$capsule->setEventDispatcher( app(\Illuminate\Events\Dispatcher::class) );

		// Set the cache manager instance used by connections... (optional)
		//$capsule->setCacheManager();

		// Make this Capsule instance available globally via static methods... (optional)
		$capsule->setAsGlobal();

		// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
		$capsule->bootEloquent();
		
		return $capsule;
	}
}
