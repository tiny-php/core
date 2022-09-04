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

use TinyPHP\ApiResult;
use TinyPHP\ApiRoute;
use TinyPHP\RenderContainer;
use TinyPHP\RouteContainer;
use TinyPHP\Utils;


class BusApiRoute extends ApiRoute
{
	
	/**
	 * Request before
	 */
	function request_before(RenderContainer $container)
	{
		parent::request_before($container);
		
		/* Get post */
		$post = json_decode($container->request->getContent(), true);
		$data = Utils::attr($post, ["data"], []);
		$time = Utils::attr($post, ["time"], "");
		$sign = Utils::attr($post, ["sign"], "");
		
		/* Check sign */
		$bus_key = app()->settings("bus_key");
		$arr = array_keys($data); sort($arr);
		array_unshift($arr, $time);
		$text = implode("|", $arr);
		$sign2 = hash_hmac("SHA512", $text, $bus_key);
		
		if ($sign != $sign2)
		{
			throw new \Exception("Bus sign error");
		}
	}
	
	
	
	/**
	 * Request after
	 */
	function request_after()
	{
		parent::request_after();
	}
	
}