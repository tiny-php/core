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


class ApiRoute
{
	var $action = "";
    var $class_name = "";
    var $api_path = "";



	/**
	 * Get rules
	 */
	function rules()
	{
		return [];
	}


	
	/**
	 * Declare routes
	 */
	function routes(RouteCollector $routes)
	{
        if ($this->$api_path != "")
        {
            $routes->addRoute
            (
                'GET',
                '/' . $this->$api_path . '/',
                [$this, "actionList"]
            );
            $routes->addRoute
            (
                'GET',
                '/' . $this->$api_path . '/{id:\d+}/',
                [$this, "actionGetById"]
            );
            $routes->addRoute
            (
                'POST',
                '/' . $this->$api_path . '/create/',
                [$this, "actionCreate"]
            );
            $routes->addRoute
            (
                'POST',
                '/' . $this->$api_path . '/{id:\d+}/edit/',
                [$this, "actionEdit"]
            );
            $routes->addRoute
            (
                'POST',
                '/' . $this->$api_path . '/{id:\d+}/delete/',
                [$this, "actionDelete"]
            );
        }
	}



    /**
	 * Request before
	 */
	function request_before(Request $request, ?Response $response, $vars)
	{
		return [$request, $response, $vars];
	}



	/**
	 * Request after
	 */
	function request_after(Request $request, ?Response $response, $vars)
	{
		return [$request, $response, $vars];
	}



	/**
	 * From database
	 */
	function fromDatabase($item)
	{
		$rules = $this->rules();
		$old_item = $item;
		foreach ($rules as $rule)
		{
			$item = $rule->fromDatabase($this, $item, $old_item);
		}
		return $item;
	}



	/**
	 * To database
	 */
	function toDatabase($item)
	{
		$rules = $this->rules();
		$old_item = $item;
		foreach ($rules as $rule)
		{
			$item = $rule->toDatabase($this, $item, $old_item);
		}
		return $item;
	}



    /**
	 * List action
	 */
	function actionList(Request $request, ?Response $response, $vars)
	{
		$api_result = new ApiResult();
        $class_name = $this->class_name;
		$this->action = "list";

		try
		{
			$targets = $class_name::all();
			$result = [];
			foreach ($targets->all() as $item)
			{
				$item = $this->fromDatabase($item);
				$result[] = $item;
			}
			$api_result->success( $result );
		}
		catch (\Exception $e)
		{
			$api_result->exception( $e );
		}

		return [
			$request, 
			$api_result->getResponse(), 
			$vars
		];
	}



	/**
	 * Get by id action
	 */
	function actionGetById(Request $request, ?Response $response, $vars)
	{
		$api_result = new ApiResult();
        $class_name = $this->class_name;
		$id = isset($vars["id"]) ? $vars["id"] : "";
		$this->action = "getById";

		$item = $class_name::find($id);
		if ($item != null)
		{
			$result = $this->fromDatabase($item->getAttributes());
			$api_result->success( $result );
		}
		else
		{
			$api_result->exception( new \Helper\Exception\ItemNotFoundException() );
		}
		
		return
		[
			$request, 
			$api_result->getResponse(), 
			$vars
		];
	}



	/**
	 * Create action
	 */
	function actionCreate(Request $request, ?Response $response, $vars)
	{
		$api_result = new ApiResult();
        $class_name = $this->class_name;
		$this->action = "create";

		if (0 !== strpos($request->headers->get('Content-Type'), 'application/json'))
		{
			$api_result->exception( new \Exception("Content type must be application/json") );
		}
		else
		{
			$post = json_decode($request->getContent(), true);
			if ($post == null)
			{
				$api_result->exception( new \Exception("Post is null") );
			}
			else
			{
				$data = isset($post["data"]) ? $post["data"] : [];
				$data = $this->toDatabase($data);
				
				/* Create item */
				$item = new $class_name();
				foreach ($data as $key => $value)
				{
					$item->$key = $value;
				}

				/* Save item */
				$item->save();

				/* Set result */
				$result = $this->fromDatabase($item->getAttributes());
				$api_result->success( $result );
			}
		}

		return
		[
			$request, 
			$api_result->getResponse(), 
			$vars
		];
	}



	/**
	 * Edit action
	 */
	function actionEdit(Request $request, ?Response $response, $vars)
	{
		$api_result = new ApiResult();
		$class_name = $this->class_name;
		$this->action = "edit";

		if (0 !== strpos($request->headers->get('Content-Type'), 'application/json'))
		{
			$api_result->exception( new \Exception("Content type must be application/json") );
		}
		else
		{
			$post = json_decode($request->getContent(), true);
			if ($post == null)
			{
				$api_result->exception( new \Exception("Post is null") );
			}
			else
			{
				$id = isset($vars["id"]) ? $vars["id"] : "";
				
				$item = $class_name::find($id);
				if ($item == null)
				{
					$api_result->exception( new \Helper\Exception\NotFoundException() );
				}
				else
				{
					$data = isset($post["data"]) ? $post["data"] : [];
					$data = $this->toDatabase($data);

					/* Edit item */
					foreach ($data as $key => $value)
					{
						$item->$key = $value;
					}
					$item->save();
					
					/* Set result */
					$result = $this->fromDatabase($item->getAttributes());
					$api_result->success( $result );
				}
				
			}
		
		}

		return
		[
			$request, 
			$api_result->getResponse(), 
			$vars
		];
	}



	/**
	 * Delete action
	 */
	function actionDelete(Request $request, ?Response $response, $vars)
	{
		$api_result = new ApiResult();
		$class_name = $this->class_name;
		$this->action = "delete";

		$id = isset($vars["id"]) ? $vars["id"] : "";
		$item = $class_name::find($id);
		if ($item != null)
		{
			/* Delete item */
			$item->delete();
			
			/* Set result */
			$result = $this->fromDatabase($item->getAttributes());
			$api_result->success( $result );
		}
		else
		{
			$api_result->exception( new \Helper\Exception\ItemNotFoundException() );
		}

		return
		[
			$request, 
			$api_result->getResponse(), 
			$vars
		];
	}
}