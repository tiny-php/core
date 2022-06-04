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

 define ("CHAIN_BEFORE", -500);
 define ("CHAIN_AFTER", 500);
 define ("CHAIN_LAST", 1000);
 

/**
 * Create app instance
 */
function create_app_instance()
{
	global $app;
	if ($app == null)
	{
		$app = new App\Instance();
	}
	return $app;
}



/**
 * Get instance
 */
function app($name = "")
{
	global $app;
	if ($name == "") return $app;
	return $app->get($name);
}



/**
 * Add chain
 */
function add_chain($chain_name, $class_name, $method_name, $priority = 0)
{
	global $app;
	return $app->add_chain($chain_name, $class_name, $method_name, $priority);
}



/**
 * Call chain
 */
function call_chain($name = "", $params = [])
{
	global $app;
	return $app->call_chain($name, $params);
}


/**
 * Make instance
 */
function make($name, $params = [])
{
	global $app;
	return $app->make($name, $params);
}



/**
 * Enviroment
 */
function env($key)
{
	global $app;
	return $app->env($key);
}



/**
 * Fatal error
 */
function tiny_php_fatal_error($e)
{
	global $app;
	
	$error = make(\TinyPHP\FatalError::class);
	if ($error && $app != null && $app->render_container != null)
	{
		$container = $app->render_container;
		$container->response = $error->handle_error($e, $container);
		$res = $app->call_chain("before_response", [
			"render_container" => $container
		]);
		$res->render_container->sendResponse();
	}
	else
	{
		http_response_code(502);
		throw $e;
	}
}
set_exception_handler("tiny_php_fatal_error");


/**
 * Make url
 */
function url($route_name, $params = [])
{
	$app = app();
	$url = $app->route_container->url($route_name, $params);
	return $app->render_container->base_url . $url;
}