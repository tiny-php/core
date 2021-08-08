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
	var $item = null;
	var $items = null;


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
        if ($this->api_path != "")
        {
            $routes->addRoute
            (
                'GET',
                '/' . $this->api_path . '/',
                [$this, "actionList"]
            );
            $routes->addRoute
            (
                'GET',
                '/' . $this->api_path . '/{id:\d+}/',
                [$this, "actionGetById"]
            );
            $routes->addRoute
            (
                'POST',
                '/' . $this->api_path . '/create/',
                [$this, "actionCreate"]
            );
            $routes->addRoute
            (
                'POST',
                '/' . $this->api_path . '/{id:\d+}/edit/',
                [$this, "actionEdit"]
            );
            $routes->addRoute
            (
                'POST',
                '/' . $this->api_path . '/{id:\d+}/delete/',
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
	 * Find items
	 */
	function findItems(Request $request, $vars)
	{
		$class_name = $this->class_name;
		$targets = $class_name::all();
		return $targets;
	}



	/**
	 * Find by id
	 */
	function findById(Request $request, $vars)
	{
		$class_name = $this->class_name;
		if (!isset($vars["id"]))
		{
			return null;
		}
		$id = (int)($vars["id"]);
		$item = $class_name::find($id);
		return $item;
	}



    /**
	 * List action
	 */
	function actionList(Request $request, ?Response $response, $vars)
	{
		$api_result = new ApiResult();
		$this->action = "list";

		try
		{
			/* Find items */
			$targets = $this->findItems($request, $vars);

			/* Set items */
			$this->items = [];
			foreach ($targets->all() as $item)
			{
				$item = $this->fromDatabase($item);
				$this->items[] = $item;
			}

			/* Set result */
			$api_result->success( $this->items );
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
		$this->action = "getById";

		/* Find item */
		$this->item = $this->findById($request, $vars);
		if ($this->item != null)
		{
			$result = $this->fromDatabase($this->item->getAttributes());
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
				$this->item = new $class_name();
				foreach ($data as $key => $value)
				{
					$this->item->$key = $value;
				}

				/* Save item */
				$this->item->save();

				/* Set result */
				$result = $this->fromDatabase($this->item->getAttributes());
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
				$this->item = $this->findById($request, $vars);
				if ($this->item == null)
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
						$this->item->$key = $value;
					}
					$this->item->save();
					
					/* Set result */
					$result = $this->fromDatabase($this->item->getAttributes());
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

		$this->item = $this->findById($request, $vars);
		if ($this->item != null)
		{
			/* Delete item */
			$this->item->delete();
			
			/* Set result */
			$result = $this->fromDatabase($this->item->getAttributes());
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