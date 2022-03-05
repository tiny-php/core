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
	var $chains = [];
	var $routes = [];
	var $models = [];
	var $commands = [];
	var $modules = [];
	var $di_container = null;
	
	
	/**
	 * Return instance
	 */
	function get($name)
	{
		return $this->di_container->get($name);
	}
	
	
	
	/**
	 * Make instance
	 */
	function make($name, $params = [])
	{
		return $this->di_container->make($name, $params);
	}
	
	
	
	/**
	 * Returns enviroment variable
	 */
	function env($key)
	{
		return getenv($key);
	}
	
	
	
	/**
	 * Add chain
	 */
	function add_chain($chain_name, $class_name, $method_name, $priority = 0)
	{
		if (!isset($this->chains[$chain_name])) $this->chains[$chain_name] = [];
		if (!isset($this->chains[$chain_name][$priority]))
			$this->chains[$chain_name][$priority] = [];
		$this->chains[$chain_name][$priority] = [ $class_name, $method_name ];
	}
	
	
	
	/**
	 * Call chain
	 */
	function call_chain($chain_name, $params = [])
	{
		$res = new ChainResult();
		foreach ($params as $key => $value)
		{
			$res[$key] = $value;
		}
		
		if (isset($this->chains[$chain_name]))
		{
			$chain = $this->chains[$chain_name];
			$chain_keys = array_keys($chain);
			sort($chain_keys);
			foreach ($chain_keys as $key)
			{
				$list_callbacks = $chain[$key];
				foreach ($list_callbacks as $callback)
				{
					call_user_func_array($callback, [$res]);
				}
			}
		}
		
		return $res;
	}
	
	
	
	/**
	 * Get DI container
	 */
	function get_di_defs()
	{
		return [
			"twig" => DI\create(\TinyPHP\Twig::class),
			
			/* App settings */
			"settings" => function()
			{
				return [
				];
			},

			/* Other classes */
			\FastRoute\RouteParser::class => DI\create(\FastRoute\RouteParser\Std::class),
			\FastRoute\DataGenerator::class => DI\create(
				\FastRoute\DataGenerator\GroupCountBased::class
			),
			\FastRoute\RouteCollector::class => DI\autowire(\FastRoute\RouteCollector::class),
			\FastRoute\Dispatcher::class =>
				function (\Psr\Container\ContainerInterface $c)
				{
					$router = $c->get(\FastRoute\RouteCollector::class);
					return new \FastRoute\Dispatcher\GroupCountBased( $router->getData() );
				},

			\TinyPHP\ApiResult::class => DI\create(\TinyPHP\ApiResult::class),
			\TinyPHP\RenderContainer::class => DI\create(\TinyPHP\RenderContainer::class),
			\TinyPHP\FatalError::class => DI\create(\TinyPHP\FatalError::class),
		];
	}
	
	
	
	/**
	 * Build DI container
	 */
	function build_di_container()
	{
		$defs = $this->get_di_defs();
		
		/* Extend di container defs */
		$res = chain("init_di_defs", ["defs"=>$defs]);
		$defs = $res->defs;
		
		/* Create DI container */
		$container_builder = new \DI\ContainerBuilder();
		$container_builder->addDefinitions($defs);
		$this->di_container = $container_builder->build();
	}
	
	
	
	/**
	 * Init app
	 */
	function init()
	{
		$this->add_modules();
		
		/* Register modules hooks */
		foreach ($this->modules as $module_class_name)
		{
			call_user_func([ $module_class_name, "register_hooks" ]);
		}
		
		/* Register hooks */
		$this->add_chain("init_routes", $this, "init_routes");
		$this->register_hooks();
		$this->call_chain("register_hooks");
		
		/* Build DI container */
		$this->build_di_container();
		
		/* Init routes */
		$this->call_chain("init_routes");
		
		/* Init app */
		$this->call_chain("init_app");
	}
	
	
	
	/**
	 * Add modules
	 */
	function add_modules()
	{
	}
	
	
	
	/**
	 * Register hooks
	 */
	function register_hooks()
	{
	}
	
	
	
	/**
	 * Add module
	 */
	function addModule($module_class_name)
	{
		$this->modules[] = $module_class_name;
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
		$res = $this->call_chain("create_render_container", [
			"container" => $container,
		]);
		$container = $res->container;
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
	 * 404 error
	 */
	function actionNotFound($container)
	{
		$container->response = make(\TinyPHP\FatalError::class)
			->handle_error(new \TinyPHP\Exception\Http404Exception("Page"), $container)
		;
		$res = $this->call_chain("method_not_found", [
			"container" => $container,
		]);
		$container = $res->container;
		return $container;
	}
	
	
	
	/**
	 * Method not allowed
	 */
	function actionNotAllowed($container)
	{
		$container->response = make(\TinyPHP\FatalError::class)
			->handle_error(new \TinyPHP\Exception\Http405Exception(), $container)
		;
		$res = $this->call_chain("method_not_allowed", [
			"container" => $container,
		]);
		$container = $res->container;
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
		
		/* Request before */
		$this->call_chain("request_before", [
			"container" => $container,
		]);
		
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
		
		/* Request after */
		$this->call_chain("request_after", [
			"container" => $container,
		]);
		
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
	
	
}
