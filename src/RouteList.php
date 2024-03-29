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


class RouteList
{
	var $routes = [];
	var $routes_objects = [];
	
	
	/**
	 * Add routes
	 */
	function addRoutesFromClass($routes_class_names)
	{
		if (gettype($routes_class_names) == "array")
		{
			foreach ($routes_class_names as $class_name)
			{
				$route = new $class_name();
				$this->routes_objects[] = $route;
				$route->routes($this);
			}
		}
		else if (gettype($routes_class_names) == "string")
		{
			$route = new $class_name();
			$this->routes_objects[] = $route;
			$route->routes($this);
		}
	}
	
	
	
	/**
	 * Add route
	 */
	function addRoute($params)
	{
		$url = isset($params["url"]) ? $params["url"] : null;
		$route_name = isset($params["name"]) ? $params["name"] : null;
		
		if (!isset($params["match"]))
		{
			$arr = [];
			$f = preg_match_all("/{(.*?)}/i", $url, $arr);
			if ($f)
			{
				foreach ($arr[1] as $name)
				{
					$url = preg_replace
					(
						"/{" . $name . "}/i",
						"(?<" . $name . ">[^/]*)" ,
						$url
					);
				}
			}
			$params["match"] = $url;
		}
		
		if ($route_name)
		{
			$this->routes[$route_name] = $params;
		}
	}
	
	
	
	/**
	 * Make url
	 */
	function url($route_name, $params = [])
	{
		if (isset($this->routes[$route_name]))
		{
			$route_info = $this->routes[$route_name];
			$url = isset($route_info["url"]) ? $route_info["url"] : "";
			foreach ($params as $key => $value)
			{
				$url = str_replace("{".$key."}", urlencode($value), $url);
			}
			return $url;
		}
		return "";
	}
	
	
	
	/**
	 * Find route
	 */
	function findRoute($render_container)
	{
		$host = $render_container->request->getHost();
		$uri = $render_container->request->getRequestUri();
		$arr = parse_url($uri);
		$request_uri = isset($arr["path"]) ? $arr["path"] : "";
		
		/* Base url */
		if ($render_container->base_url != "")
		{
			if (strpos($request_uri, $render_container->base_url) === 0)
			{
				$request_uri = substr($request_uri, strlen($render_container->base_url));
			}
		}
		
		if ($request_uri == "") $request_uri = "/";
		
		/* Find route */
		foreach ($this->routes as $route)
		{
			$res = call_chain("find_route_item", [
				"flag" => null,
				"matches" => null,
				"uri" => $uri,
				"host" => $host,
				"route" => $route,
				"request_uri" => $request_uri,
			]);
			
			$flag = $res["flag"];
			$matches = $res["matches"];
			
			if ($flag === null)
			{
				$method = $render_container->request->getMethod();
				if (isset($route["methods"]))
				{
					if (!in_array($method, $route["methods"]))
					{
						continue;
					}
				}
				
				if (isset($route["domains"]))
				{
					if (!in_array($host, $route["domains"]))
					{
						continue;
					}
				}
				
				$match = $route["match"];
				$match = str_replace("/", "\\/", $match);
				$match = "/^" . $match . "$/i";
				$flag = preg_match_all($match, $request_uri, $matches);
				foreach ($matches as $key => $val)
				{
					if (isset($val[0]))
					{
						$matches[$key] = $val[0];
					}
				}
			}
			
			if ($flag)
			{
				$render_container->args = $matches;
				$render_container->route_info = $route;
				break;
			}
		}
		
		return $render_container;
	}
	
}