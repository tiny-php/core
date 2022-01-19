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


class RenderContainer
{
	var $action = "";
	var $request = null;
	var $response = null;
	var $handler = null;
	var $args = null;
	var $context = [];
	var $route = null;
	
	
	/**
	 * Get arg
	 */
	function arg($key, $value = "")
	{
		return isset($this->args[$key]) ? $this->args[$key] : $value;
	}
	
	
	
	/**
	 * Get
	 */
	function get($key, $value = "")
	{
		return $this->request->query->has($key) ?
			$this->request->query->get("filter") : $value;
	}
	
	
	
	/**
	 * Post
	 */
	function post($key, $value = "")
	{
		return $this->request->query->has($key) ?
			$this->request->query->get("filter") : $value;
	}
	
	
	
	/**
	 * Header
	 */
	function head($key, $value = "")
	{
		return $this->request->request->has($key) ?
			$this->request->request->get("filter") : $value;
	}
	
	
	
	/**
	 * Server
	 */
	function server($key, $value = "")
	{
		return "";
	}
	
	
	
	/**
	 * Is post
	 */
	function isPost()
	{
		return false;
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
	function setContext($arr)
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
			$this->response = new Response("", Response::HTTP_OK, ['content-type' => 'text/html']);
		}
		$this->response->setContent($content);
		return $this;
	}
	
	
	
	/**
	 * Send response
	 */
	function sendResponse()
	{
		if ($this->response) $this->response->send();
	}
	
	
	
	/**
	 * Render template
	 */
	function render($template)
	{
		$twig = app("twig");
		$content = $twig->render($template, $this->context);
		$this->response = new Response
		(
			$content,
			Response::HTTP_OK,
			['content-type' => 'text/html']
		);
		return $this;
	}
}