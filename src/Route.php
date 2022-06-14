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
	 * Is post
	 */
	function isPost()
	{
		return $this->container->isPost();
	}
	
	
	
	/**
	 * Get request method
	 */
	public function getMethod()
	{
		return $this->container->getMethod();
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
	 * Set context
	 */
	public function setContext($key, $value)
	{
		$this->container->setContext($key, $value);
	}
	
	
	
	/**
	 * Add breadcrumb
	 */
	public function add_breadcrumb($name, $title)
	{
		$this->container->add_breadcrumb($name, $title);
	}
	
	
	
	/**
	 * Make url
	 */
	function url($route_name, $params = [])
	{
		$app = app();
		return $app->url($route_name, $params);
	}
}