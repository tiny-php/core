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


class Module
{
	
	static function twig_module_name()
	{
		return "tiny-php";
	}
	
	
	/**
	 * Register hooks
	 */
	static function register_hooks()
	{
		add_chain("request_before", static::class, "init_auth");
		add_chain("before_response", static::class, "create_response_if_does_not_exists", CHAIN_LAST - 1);
		add_chain("before_response", static::class, "add_ob_content", CHAIN_LAST);
	}
	
	
	/**
	 * Init auth
	 */
	static function init_auth($res)
	{
		$app = app();
		
		$jwt = make(\TinyPHP\Crypt\JWT::class);
		$jwt_cookie_key = $app->settings("jwt_cookie_key");
		
		/* Parse JWT */
		if ($jwt_cookie_key)
		{
			$jwt_string = $res->container->cookie($jwt_cookie_key);
			$jwt = $jwt::create($jwt_string);
		}
		else
		{
			$jwt = null;
		}
		
		/* Setup Auth */
		$auth = app(\TinyPHP\Auth::class);
		$auth->init([
			"jwt" => $jwt,
		]);
		
		/* Setup context */
		$res->container->setContext("auth", $auth);
		$res->container->setContext("jwt", $jwt);
	}
	
	
	
	/**
	 * Create response if does not exists
	 */
	static function create_response_if_does_not_exists($res)
	{
		if ($res->container->response == null)
		{
			$res->container->response = new Response
			(
				"",
				200,
				['content-type' => 'text/html']
			);
		}
	}
	
	
	
	/**
	 * Add ob content
	 */
	static function add_ob_content($res)
	{
		$ob_content = "";
		if (ob_get_level() > 0)
		{
			$ob_content = ob_get_contents();
			ob_end_clean();
			ob_start();
		}
		
		if ($res->container->response && $ob_content != "")
		{
			if ($res->container->isApi())
			{
				$json = $res->container->response->getContent();
				$json = @json_decode($json, true);
				if ($json)
				{
					$json["ob_content"] = $ob_content;
					$res->container->response->setContent( json_encode($json) . "\n" );
				}
			}
			else
			{
				$res->container->response->setContent(
					$ob_content . $res->container->response->getContent()
				);
			}
		}
	}
}