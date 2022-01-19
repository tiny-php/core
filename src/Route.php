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


use FastRoute\RouteCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TinyPHP\Exception\ItemNotFoundException;


class Route
{
	
	public $app = null;
	
	
	function __construct()
	{
	}
	
	
	
	/**
	 * Request before
	 */
	function request_before(RenderContainer $container)
	{
		$this->action = $container->action;
		$this->container = $container;
		
		/* Init action */
		$this->init();
		
		/* Validate action */
		$this->validate();
		
		return $container;
	}
	
	
	
	
	/**
	 * Request after
	 */
	function request_after(RenderContainer $container)
	{
		/* Process after */
		$this->after();
		
		return $container;
	}
	
	
	
	/**
	 * Init
	 */
	public function init()
	{
		$this->initAction($this->action);
	}
	
	
	
	/**
	 * Validate
	 */
	public function validate()
	{
		$this->validateAction($this->action);
	}
	
	
	
	/**
	 * After
	 */
	public function after()
	{
		$this->afterAction($this->action);
	}
	
	
	
	/**
	 * Init action
	 */
	public function initAction($action){}
	
	
	
	/**
	 * Validate action
	 */
	public function validateAction($action){}
	
	
	/**
	 * After action
	 */
	public function afterAction($action){}
	
}