<?php

/*!
 *  Tiny PHP Framework
 *
 *  MIT License
 *
 *  Copyright (c) 2020 - 2022 "Ildar Bikmamatov" <support@bayrell.org>
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

use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class App
{
	var $chains = [];
	var $entities = [];
	var $modules = [];
	var $di_container = null;
	var $render_container = null;
	
	/* Errors */
	const ERROR_OK = 1;
	const ERROR_UNKNOWN = -1;
	const ERROR_ITEM_NOT_FOUND = -4;
	
	
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
		return ($this->di_container) ? $this->di_container->make($name, $params) : null;
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
		$this->chains[$chain_name][$priority][] = [ $class_name, $method_name ];
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
			"twig" => \DI\create(\TinyPHP\Twig::class),
			
			/* App settings */
			"settings" => function()
			{
				return [
				];
			},
			
			/* Other classes */
			\TinyPHP\Auth::class => \DI\create(\TinyPHP\Auth::class),
			\TinyPHP\ApiResult::class => \DI\create(\TinyPHP\ApiResult::class),
			\TinyPHP\RenderContainer::class => \DI\create(\TinyPHP\RenderContainer::class),
			\TinyPHP\RouteContainer::class => \DI\create(\TinyPHP\RouteContainer::class),
			\TinyPHP\FatalError::class => \DI\create(\TinyPHP\FatalError::class),
		];
	}
	
	
	
	/**
	 * Build DI container
	 */
	function build_di_container()
	{
		$defs = $this->get_di_defs();
		
		/* Extend di container defs */
		$res = call_chain("init_di_defs", ["defs"=>$defs]);
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
		/* Add modules */
		$this->add_modules();
		
		/* Add TinyPHP module */
		$this->addModule(\TinyPHP\Module::class);
		
		/* Register modules hooks */
		foreach ($this->modules as $module_class_name)
		{
			if (!class_exists($module_class_name)) continue;
			if (!method_exists($module_class_name, "register_hooks")) continue;
			
			call_user_func([ $module_class_name, "register_hooks" ]);
		}
		
		/* Register hooks */
		$this->register_hooks();
		$this->call_chain("register_hooks");
		
		/* Build DI container */
		$this->build_di_container();
		
		/* Init entities */
		$this->call_chain("register_entities");
		
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
		if (!in_array($module_class_name, $this->modules))
		{
			$this->modules[] = $module_class_name;
		}
	}
	
	
	
	/**
	 * Add entity
	 */
	function addEntity($class_name, $file_name = "")
	{
		if ($file_name != "")
		{
			require_once $file_name;
		}
		
		$class_parents = class_parents($class_name);
		foreach ($class_parents as $parent_class_name)
		{
			if (!isset($this->entities[$parent_class_name]))
			{
				$this->entities[$parent_class_name] = [];
			}
			$this->entities[$parent_class_name][] = $class_name;
		}
		
		if (!isset($this->entities[$class_name]))
		{
			$this->entities[$class_name] = [];
		}
		$this->entities[$class_name][] = $class_name;
	}
	
	
	
	/**
	 * Return entitie
	 */
	function getEntities($class_name)
	{
		if (isset($this->entities[$class_name]))
		{
			return $this->entities[$class_name];
		}
		return [];
	}
	
	
	
	/**
	 * Action error
	 */
	function actionError($container, $e)
	{
		$container->response = make(\TinyPHP\FatalError::class)
			->handle_error($e, $container)
		;
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
		$res = $this->call_chain
		(
			"page_not_found",
			[
				"container" => $container,
			]
		);
		$container = $res->container;
		return $container;
	}
	
	
	
	/**
	 * Make response
	 */
	function makeResponse($render_container)
	{
		$route_info = $render_container->route_info;
		
		/* Request before */
		$res = $this->call_chain
		(
			"request_before",
			[
				"container" => $render_container,
				"render_container" => $render_container,
			]
		);
		$render_container = $res->container;
		
		if ($render_container->response == null)
		{
			
			/* Route not found */
			if ($route_info == null)
			{
				$render_container = $this->actionNotFound($render_container);
			}
			
			/* Route found */
			else
			{
				$method = $route_info["method"];
				
				if ($method instanceof \Closure)
				{
					$method($render_container);
				}
				else if (is_array( $method ))
				{
					$render_container->action = $method[1];
					
					$obj = $method[0];
					if (is_object($obj) && $obj instanceof Route)
					{
						$render_container->route = $obj;
						$obj->request_before($render_container);
						if ($render_container->response == null)
						{
							if (count($method) >= 2)
							{
								if (!method_exists($method[0], $method[1]))
								{
									throw new \Exception("Method does not exist");
								}
							}
							call_user_func($method);
						}
						$obj->request_after();
					}
					else
					{
						call_user_func_array($method, [$render_container]);
					}
				}
				
			}
		}
		
		/* Request after */
		$res = $this->call_chain
		(
			"request_after",
			[
				"container" => $render_container,
			]
		);
		$render_container = $res->container;
		
		return $render_container;
	}
	
	
	
	/**
	 * Create render container
	 */
	function createRenderContainer()
	{
		$container = make(\TinyPHP\RenderContainer::class);
		$container->request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
		
		/* Parse post is application/json */
		$content_type = $container->header('Content-Type');
		if (substr($content_type, 0, strlen('application/json')) == 'application/json')
		{
			$json = $container->request->getContent();
			$json = @json_decode($json, true);
			if ($json)
			{
				$container->request->request = new InputBag($json);
			}
		}
		
		$res = $this->call_chain
		(
			"create_render_container",
			[
				"container" => $container,
			]
		);
		$container = $res->container;
		return $container;
	}
	
	
	
	/**
	 * Web app run
	 */
	function runWebApp()
	{
		$this->render_container = $this->createRenderContainer();
		$route_container = app(\TinyPHP\RouteContainer::class);
		
		try
		{
			/* Get base url */
			$res = call_chain("base_url", [
				"base_url" => "",
				"request" => $this->render_container->request
			]);
			
			/* Remove last slash */
			$base_url = preg_replace("/\/+$/", "", $res["base_url"]);
			$this->render_container->base_url = $base_url;
			
			/* Add routes */
			$routes_class_names = $this->getEntities(\TinyPHP\Route::class);
			$route_container->addRoutesFromClass($routes_class_names);
			$res = $this->call_chain
			(
				"routes",
				[
					"route_container" => $route_container,
					"render_container" => $this->render_container,
					"container" => $this->render_container,
				]
			);
			$this->render_container = $res["render_container"];
			
			/* Find route */
			$this->render_container = $route_container->findRoute
			(
				$this->render_container
			);
			$res = $this->call_chain
			(
				"find_route",
				[
					"route_container" => $route_container,
					"render_container" => $this->render_container,
					"container" => $this->render_container,
				]
			);
			$this->render_container = $res["render_container"];
			
			/* Setup global context */
			$this->render_container->context["global"]["route_info"] =
				$this->render_container->route_info
			;
			
			/* Call web app middleware chain */
			$res = $this->call_chain
			(
				"web_app_middleware",
				[
					"container" => $this->render_container
				]
			);
			$this->render_container = $res["container"];
			
			/* Send response */
			if (!$this->render_container->response)
			{
				/* Make response */
				$this->render_container = $this->makeResponse($this->render_container);
				$res = $this->call_chain
				(
					"make_response",
					[
						"container" => $this->render_container
					]
				);
				$this->render_container = $res["container"];
			}
		}
		
		catch (\Exception $e)
		{
			$this->render_container = $this->actionError($this->render_container, $e);
		}
		
		/* Before response */
		$res = $this->call_chain
		(
			"before_response",
			[
				"render_container" => $this->render_container
			]
		);
		$this->render_container = $res["render_container"];
		
		/* Send response */
		$this->render_container->sendResponse();
	}
	
	
	
	/**
	 * Run console app
	 */
	function runConsoleApp()
	{
		$this->console = new \Symfony\Component\Console\Application();
		
		/* Get console commands */
		$commands = $this->getEntities(\Symfony\Component\Console\Command\Command::class);
		$res = $this->call_chain
		(
			"console_commands",
			[
				"commands" => $commands
			]
		);
		$commands = $res["commands"];
		
		/* Add console commands */
		foreach ($commands as $class_name)
		{
			$this->console->add( new $class_name() );
		}
		
		/* Run console */
		$this->console->run();
	}
	
}
