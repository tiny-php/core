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


class ApiCrudRoute
{
	var $action = "";
	var $class_name = "";
	var $api_path = "";
	var $filter = null;
	var $start = 0;
	var $limit = 1000;
	var $total = 0;
	var $items = null;
	var $item = null;


	/**
	 * Get rules
	 */
	function getRules()
	{
		return [];
	}
	
	
	
	/**
	 * Returns max limit
	 */
	public function getMaxLimit()
	{
		return 1000;
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
				'/' . $this->api_path . '/crud/search/',
				[$this, "actionSearch"]
			);
			$routes->addRoute
			(
				'GET',
				'/' . $this->api_path . '/crud/item/{id}/',
				[$this, "actionGetById"]
			);
			$routes->addRoute
			(
				'POST',
				'/' . $this->api_path . '/crud/create/',
				[$this, "actionCreate"]
			);
			$routes->addRoute
			(
				'POST',
				'/' . $this->api_path . '/crud/edit/{id}/',
				[$this, "actionEdit"]
			);
			$routes->addRoute
			(
				'DELETE',
				'/' . $this->api_path . '/crud/delete/{id}/',
				[$this, "actionDelete"]
			);
			$routes->addRoute
			(
				'POST',
				'/' . $this->api_path . '/crud/update/',
				[$this, "actionUpdate"]
			);
		}
	}
	
	
	
	/**
	 * Request before
	 */
	function request_before(RenderContainer $container)
	{
		$this->api_result = make(ApiResult::class);
		$this->container = $container;
		$this->rules = $this->getRules();
		
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
		return $container;
	}
	
	
	
	/**
	 * Init action
	 */
	public function init()
	{
		/* Search action */
		if ($this->container->action == "actionSearch")
		{
			$max_limit = $this->getMaxLimit();
			if ($this->container->request->query->has("start"))
			{
				$this->start = (int)($this->container->request->query->get("start"));
			}
			if ($this->container->request->query->has("limit"))
			{
				$this->limit = (int)($this->container->request->query->get("limit"));
			}
			if ($this->limit > $max_limit) $this->limit = $max_limit;
			$this->initFilter();
		}
		
		if (
			$this->container->action == "actionCreate" ||
			$this->container->action == "actionEdit"
		)
		{
			$content_type = $this->container->request->headers->get('Content-Type');
			if (substr($content_type, 0, strlen('application/json')) != 'application/json')
			{
				throw new \Exception("Content type must be application/json");
			}
		}
	}
	
	
	
	/**
	 * Returns filter by request
	 */
	public function initFilter()
	{
		$this->filter = [];
		if ($this->container->request->query->has("filter"))
		{
			$this->filter = Utils::parseFilter
			(
				$this->container->request->query->get("filter"),
				Utils::method($this, "allowFilterField")
			);
		}
	}
	
	
	
	/**
	 * Allow filter fields
	 */
	public function allowFilterField($field_name, $op, $value)
	{
		return false;
	}
	
	
	
	/**
	 * Validate request
	 */
	public function validate()
	{
	}
	
	
	
	/**
	 * From database
	 */
	function fromDatabase($item)
	{
		$old_item = $item;
		foreach ($this->rules as $rule)
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
		$old_item = $item;
		foreach ($this->rules as $rule)
		{
			$item = $rule->toDatabase($this, $item, $old_item);
		}
		return $item;
	}
	
	
	
	/**
	 * Find query
	 */
	public function findQuery($query)
	{
		return $query;
	}
	
	
	
	/**
	 * Find items
	 */
	function findItems()
	{
		$class_name = $this->class_name;
		
		/* Get query */
		$query = $class_name::query();
		
		/* Limit */
		$query
			->where($this->filter)
			->offset($this->start)
			->limit($this->limit)
		;
		
		/* Filter query */
		$query = $this->findQuery($query);
		
		/* Result */
		$this->items = $query->get();
		$this->total = $query->count(); 
	}
	
	
	
	/**
	 * Find item
	 */
	public function findItem()
	{
		$class_name = $this->class_name;
		$instance = new $class_name();
		
		/* Get query */
		$id = urldecode($this->container->vars["id"]);
		$query = $class_name::query()->where($instance->getKeyName(), "=", $id);
		
		/* Filter query */
		$query = $this->findQuery($query);
		
		$this->item = $query->first();
	}
	
	
	
	/**
	 * Create item
	 */
	public function createItem($data)
	{
		$class_name = $this->class_name;
		$this->item = new $class_name();
		foreach ($data as $key => $value) $this->item->$key = $value;
		$this->item->save();
		$this->item->refresh();
	}
	
	
	
	/**
	 * Update item
	 */
	public function updateItem($data)
	{
		$class_name = $this->model_name;
		foreach ($data as $key => $value) $this->item->$key = $value;
		if ($this->item)
		{
			$this->item->save();
			$this->item->refresh();
		}
	}
	
	
	
	/**
	 * Delete item
	 */
	public function deleteItem()
	{
		if ($this->item) $this->item->delete();
	}
	
	
	
	/**
	 * List action
	 */
	function actionSearch(RenderContainer $container)
	{
		/* Find items */
		$this->findItems();
		
		$result =
		[
			"items" => [],
			"filter" => $this->filter,
			"start" => $this->start,
			"limit" => $this->limit,
			"total" => $this->total,
		];
		
		/* Set items */
		foreach ($this->items as $item)
		{
			$item = $this->fromDatabase($item);
			$result["items"][] = $item;
		}
		
		/* Set result */
		return $container
			->setResponse
			(
				$this->api_result
					->success( $result, "Ok" )
					->getResponse()
			)
		;
	}
	
	
	
	/**
	 * Get by id action
	 */
	function actionGetById(RenderContainer $container)
	{
		/* Find item */
		$this->findItem();
		
		if ($this->item != null)
		{
			$item = $this->fromDatabase( $this->item->getAttributes() );
			$this->api_result->success(["item" => $item ]);
		}
		else
		{
			throw new ItemNotFoundException();
		}
		
		/* Set result */
		return $container->setResponse( $this->api_result->getResponse() );
	}
	
	
	
	/**
	 * Create action
	 */
	function actionCreate(RenderContainer $container)
	{
		$post = json_decode($container->request->getContent(), true);
		if ($post == null)
		{
			throw new \Exception("Post is null");
		}
		
		$data = Utils::attr($post, "item");
		if ($data === null)
		{
			throw new \Exception("Field item is empty");
		}
		
		/* Convert to database*/
        $data = $this->toDatabase($data);
        
        /* Create item */
        $this->createItem($data);
		
		/* From database */
		$item = $this->fromDatabase($this->item);
		
		/* Set result */
		return $container->setResponse
		(
			$this
				->api_result
				->success(["item"=>$item], "Ok")
				->getResponse()
		);
	}
	
	
	
	/**
	 * Edit action
	 */
	function actionEdit(RenderContainer $container)
	{
		$post = json_decode($container->request->getContent(), true);
		if ($post == null)
		{
			throw new \Exception("Post is null");
		}
		
		$data = Utils::attr($post, "item");
		if ($data === null)
		{
			throw new \Exception("Field item is empty");
		}
		
		/* Find item */
		$this->findItem();
		
		if ($this->item == null)
        {
            throw new ItemNotFoundException();
        }
		
		/* To database */
		$data = $this->toDatabase($data);
        
		/* Update item */
		$this->updateItem($data);
		
		/* From database */
		$item = $this->fromDatabase($this->item);

		/* Set result */
		return $container->setResponse
		(
			$this
				->api_result
				->success(["item"=>$item], "Ok")
				->getResponse()
		);
	}
	
	
	
	/**
	 * Delete action
	 */
	function actionDelete(RenderContainer $container)
	{
		/* Find item */
		$this->findItem();
		
		if ($this->item == null)
        {
            throw new ItemNotFoundException();
        }
		
		/* Delete item */
		$this->deleteItem();
		
		/* From database */
		$item = $this->fromDatabase($this->item);

		/* Set result */
		return $container->setResponse
		(
			$this
				->api_result
				->success(["item"=>$item], "Ok")
				->getResponse()
		);
	}
}