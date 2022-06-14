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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;


class RenderContainer
{
	var $action = "";
	var $breadcrumbs = [];
	var $request = null;
	var $response = null;
	var $handler = null;
	var $args = null;
	var $context = [ "global" => [], ];
	var $route = null;
	var $route_info = null;
	var $base_url = "";
	var $is_api = false;
	var $error = false;
	var $new_cookie = [];
	
	
	/**
	 * Add breadcrumbs
	 */
	function add_breadcrumb($url, $label)
	{
		$this->breadcrumbs[] = [
			"url" => $url,
			"label" => $label,
		];
	}
	
	
	
	/**
	 * Set cookie
	 */
	function setCookie($params)
	{
		$name = isset($params["name"]) ? $params["name"] : "";
		if ($name == "") return;
		$this->new_cookie[$name] = $params;
	}
	
	
	
	/**
	 * Get arg
	 */
	function arg($key, $value = "")
	{
		return ($this->args != null && isset($this->args[$key])) ?
			$this->args[$key] : $value
		;
	}
	
	
	
	/**
	 * Get
	 */
	function get($key, $value = "")
	{
		return $this->request->query->has($key) ?
			$this->request->query->get($key) : $value;
	}
	
	
	
	/**
	 * Post
	 */
	function post($key = null, $value = "")
	{
		if (func_num_args() == 0)
		{
			return $this->request->request->all();
		}
		return $this->request->request->has($key) ?
			$this->request->request->get($key) : $value;
	}
	
	
	
	/**
	 * Header
	 */
	function header($key, $value = "")
	{
		return $this->request->headers->has($key) ?
			$this->request->headers->get($key) : $value;
	}
	
	
	
	/**
	 * Server
	 */
	function server($key, $value = "")
	{
		return $this->request->server->has($key) ?
			$this->request->server->get($key) : $value;
	}
	
	
	
	/**
	 * Is api
	 */
	function isApi()
	{
		return $this->is_api;
	}
	
	
	
	/**
	 * Is post
	 */
	function isPost()
	{
		return $this->request->isMethod('POST');
	}
	
	
	
	/**
	 * Get request method
	 */
	public function getMethod()
	{
		return $this->request->getMethod();
	}
	
	
	
	/**
	 * Set response
	 */
	function setResponse(Response $response)
	{
		$this->response = $response;
		return $this;    
	}
	
	
	
	/**
	 * Set context
	 */
	function setContext($key, $value)
	{
		$this->context[$key] = $value;
	}
	
	
	
	/**
	 * Add context
	 */
	function addContext($arr)
	{
		foreach ($arr as $key => $value)
		{
			$this->context[$key] = $value;
		}
	}
	
	
	
	/**
	 * Set content
	 */
	function setContent($content)
	{
		if ($this->response == null)
		{
			$this->response = new Response(
				"", Response::HTTP_OK, ['content-type' => 'text/html']
			);
		}
		$this->response->setContent($content);
		return $this;
	}
	
	
	
	/**
	 * Send response
	 */
	function sendResponse()
	{
		/* Setup cookie */
		foreach ($this->new_cookie as $params)
		{
			$name = isset($params["name"]) ? $params["name"] : "";
			$value = isset($params["value"]) ? $params["value"] : "";
			
			if ($name == "") continue;
			
			$settings = [];
			if (isset($params["path"])) $settings["path"] = $params["path"];
			if (isset($params["domain"])) $settings["domain"] = $params["domain"];
			if (isset($params["secure"])) $settings["secure"] = $params["secure"];
			if (isset($params["httponly"])) $settings["httponly"] = $params["httponly"];
			if (isset($params["expires"])) $settings["expires"] = $params["expires"];
			
			setcookie($name, $value, $settings);
		}
		
		if ($this->response) $this->response->send();
		else if ($this->error)
		{
			if ($this->error instanceof \TinyPHP\Exception\Http404Exception)
			{
				http_response_code(404);
				echo $this->error->getMessage();
			}
			else
			{
				$http_code = 502;
				if (property_exists($this->error, "http_code"))
				{
					$http_code = $this->error->http_code;
				}
				http_response_code($http_code);
				throw $this->error;
			}
		}
	}
	
	
	
	/**
	 * Render template
	 */
	function render($template, $data = null)
	{
		$context = $this->context;
		if ($data != null) $context = array_merge($context, $data);
		$twig = app("twig");
		
		/* Setup context */
		$context["route"] = $this->route;
		$context["route_info"] = $this->route_info;
		$context["base_url"] = $this->base_url;
		$context["container"] = $this;
		
		$content = $twig->render($template, $context);
		$this->response = new Response
		(
			$content,
			Response::HTTP_OK,
			['content-type' => 'text/html']
		);
		return $this;
	}
	
	
	
	/**
	 * Redirect
	 */
	function redirect($url)
	{
		$this->response = new RedirectResponse($url);
	}
}