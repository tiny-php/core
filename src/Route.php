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
use TinyPHP\Exception\ItemNotFoundException;


class Route
{
	var $action = null;
	var $container = null;
	
	
	/**
	 * Constructor
	 */
	function __construct()
	{
	}
	
	
	
	/**
	 * Declare routes
	 */
	function routes(RouteContainer $route_container)
	{
	}
	
	
	
	/**
	 * Request before
	 */
	function request_before(RenderContainer $container)
	{
		$this->container = $container;
		$this->action = $container->action;
		
		/* Init action */
		$this->init($container->action);
	}
	
	
	
	/**
	 * Request after
	 */
	function request_after()
	{
		$this->after($this->container->action);
	}
	
	
	
	/**
	 * Init
	 */
	public function init($action)
	{
	}
	
	
	
	/**
	 * After
	 */
	public function after($action)
	{
	}
	
	
	
	/**
	 * Return request
	 */
	public function getRequest()
	{
		return $this->container->request;
	}
	
	
	 
	/**
	 * Render
	 */
	public function render($template, $data = null)
	{
		$this->container->render($template, $data);
	}
	
	
	
	/**
	 * Set content
	 */
	public function setContent($content)
	{
		$this->container->setContent($content);
	}
	
	
	
	/**
	 * Redirect
	 */
	public function redirect($url)
	{
		$this->container->redirect($url);
	}
	
	
	
	/**
	 * Set context
	 */
	public function setContext($key, $value)
	{
		$this->container->setContext($key, $value);
	}
	
	
	
	/**
	 * Make url
	 */
	static function url($route_name, $params = [])
	{
		$app = app();
		$route_container = app(\TinyPHP\RouteContainer::class);
		$url = $route_container->url($route_name, $params);
		$url = $app->render_container->base_url . $url;
		$url = preg_replace("/\/+/", "/", $url);
		return $url;
	}
	
	
	
	/**
	 * Url get add
	 */
	static function url_get_add($url, $params = [])
	{
		$url_arr = explode("?", $url);
		$url = isset($url_arr[0]) ? $url_arr[0] : "";
		$url_query = isset($url_arr[1]) ? $url_arr[1] : "";
		
		$url_query_arr_new = [];
		$url_query_arr = explode("&", $url_query);
		
		foreach ($url_query_arr as $url_query_value)
		{
			$url_query_value_arr = explode("=", $url_query_value);
			$query_key = isset($url_query_value_arr[0]) ? $url_query_value_arr[0] : "";
			$query_value = isset($url_query_value_arr[1]) ? $url_query_value_arr[1] : null;
			$url_query_arr_new[$query_key] = $query_value;
		}
		
		foreach ($params as $key => $value)
		{
			$url_query_arr_new[$key] = $value;
		}
		
		$url_query_arr_new = array_map
		(
			function($key, $value)
			{
				if ($value == "")
				{
					return null;
				}
				return $key . "=" . urlencode($value);
			},
			array_keys($url_query_arr_new),
			array_values($url_query_arr_new)
		);
		
		$url_query_arr_new = array_filter
		(
			$url_query_arr_new,
			function($item)
			{
				return $item != null;
			}
		);
		
		$url_query = implode("&", $url_query_arr_new);
		
		return strlen($url_query) > 0 ? $url . "?" . $url_query : $url;
	}
}