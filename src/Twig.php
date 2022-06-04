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

class Twig
{
	var $twig = null;
	
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->create();
	}
	
	
	
	/**
	 * Create twig
	 */
	function create()
	{
		/* Restore twig */
		if ($this->twig != null)
		{
			return $this->twig;
		}
		
		$twig_opt = array
		(
			'autoescape'=>true,
			'charset'=>'utf-8',
			'optimizations'=>-1,
		);
		
		/* Twig cache */
		$twig_cache = true;
		if (defined("TWIG_CACHE"))
		{
			$twig_cache = TWIG_CACHE;
		}
		
		/* Enable cache */
		if ($twig_cache)
		{
			$twig_opt['cache'] = BASE_PATH . '/var/twig';
			$twig_opt['auto_reload'] = true;
		}
		
		$res = call_chain("twig_opt", ["twig_opt"=>$twig_opt]);
		$twig_opt = $res->twig_opt;
		
		/* Create twig loader */
		$twig_loader = new \Twig\Loader\FilesystemLoader();
		
		/* Add modules */
		$app = app();
		$modules = $app->modules;
		
		foreach ($modules as $module_class)
		{
			if (!class_exists($module_class)) continue;
			
			$c = new \ReflectionClass($module_class);
			$module_path = dirname($c->getFileName());
			$module_name = "";
			
			/* Get module name */
			if (method_exists($module_class, "twig_module_name"))
			{
				$module_name = $module_class::twig_module_name();
			}
			else
			{
				$arr = explode("\\", $module_class);
				array_pop($arr);
				$module_name = implode("-", $arr);
				$module_name = strtolower($module_name);
			}
			
			/* Call chain */
			$res = call_chain("twig_module_name", [
				"module_class" => $module_class,
				"module_path" => $module_path,
				"module_name" => $module_name,
			]);
			$module_name = $res["module_name"];
			
			$twig_loader->addPath($module_path . '/Templates', $module_name);
		}
		
		/* Create twig instance */
		$this->twig = new \Twig\Environment
		(
			$twig_loader,
			$twig_opt
		);
		
		/* Set strategy */
		$this->twig->getExtension(\Twig\Extension\EscaperExtension::class)
			->setDefaultStrategy('html');
		
		/* Undefined functions */
		$this->twig->registerUndefinedFunctionCallback([$this, "call_undefined_function"]);
		
		/* Undefined filter */
		$this->twig->registerUndefinedFilterCallback([$this, "call_undefined_function"]);
		
		/* Custom php function */
		$this->twig->addFunction( new \Twig\TwigFunction(
			'function', [$this, "call_php_function"]
		) );
		
		call_chain("twig", ["twig"=>$this->twig, "obj"=>$this ]);
		
		return $this->twig;
	}
	
	
	
	/**
	 * Call undefined function
	 */
	function call_undefined_function($name)
	{
		$app = app();
		
		/* Call redefined function */
		$res = call_chain("twig_undefined_function", [
			"name" => $name,
			"callback" => null,
		]);
		if ($res["callback"] != null)
		{
			return new \Twig\TwigFunction($name, $res["callback"]);
		}
		
		/* Get modules */
		$modules = $app->modules;
		
		/* Get namespaces */
		$modules = array_map
		(
			function($item){
				$arr = explode("\\", $item);
				array_pop($arr);
				$arr[] = "Twig_Functions";
				$item = implode("\\", $arr);
				return $item;
			},
			$modules
		);
		
		/* Find twig function in modules */
		foreach ($modules as $twig_functions_class)
		{
			/* Check if function is exists */
			if (!class_exists($twig_functions_class)) continue;
			if (!method_exists($twig_functions_class, $name)) continue;
			
			return new \Twig\TwigFunction($name, [
				$twig_functions_class, $name
			]);
		}
		
		return false;
	}
	
	
	
	/**
	 * Call php function
	 */
	function call_php_function($name)
	{
		/* Can call php function */
		$res = call_chain("twig_php_function", [
			"name" => $name,
			"allow" => 1,
		]);
		if ($res["allow"] == 0)
		{
			return false;
		}
		
		/* Call php function */
		$args = func_get_args();
		array_shift($args);
		return call_user_func_array($name, $args);
	}
	
	
	
	/**
	 * Render template
	 */
	function render($template, $context)
	{
		$res = call_chain("twig_context", ["context"=>$context]);
		$context = $res["context"];
		
		if (gettype($template) == 'array')
		{
			foreach ($template as $t)
			{
				try
				{
					$res = $this->twig->render($t, $context);
					return $res;
				}
				catch (\Twig\Error\LoaderError $err)
				{
				}
			}
		}
		else
		{
			return $this->twig->render($template, $context);
		}
		return "";
	}
	
}