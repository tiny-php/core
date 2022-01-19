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
	var $old_data = null;
	var $new_data = null;

	
	function __construct()
	{
		parent::__construct();
		$this->rules = $this->getRules();
	}
	
	

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
		$this->action = $container->action;
		$this->api_result = make(ApiResult::class);
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
		
		/* Set result */
		$container
			->setResponse
			(
				$this->api_result->getResponse()
			)
		;
		
		return $container;
	}
	
	
	
	/**
	 * Init action
	 */
	public function initAction($action)
	{
		/* Search action */
		if ($action == "actionSearch")
		{
			$this->initSearch();
		}
		
		/* Action create or edit */
		if ($action == "actionCreate" || $action == "actionEdit")
		{
			$this->initOldData();
		}
	}
	
	
	
	/**
	 * Init action search
	 */
	public function initSearch()
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
	
	
	
	/**
	 * Read old data from post
	 */
	public function initOldData()
	{
		$content_type = $this->container->request->headers->get('Content-Type');
		if (substr($content_type, 0, strlen('application/json')) != 'application/json')
		{
			throw new \Exception("Content type must be application/json");
		}
		
		$post = json_decode($this->container->request->getContent(), true);
		if ($post == null)
		{
			throw new \Exception("Post is null");
		}
		
		$this->old_data = Utils::attr($post, "item");
		if ($this->old_data === null)
		{
			throw new \Exception("Field item is empty");
		}
	}
	
	
	
	/**
	 * After action
	 */
	function after()
	{
	}
	
	
	
	/**
	 * Validate request
	 */
	public function validate()
	{
	}
	
	
	
	/**
	 * Can query
	 */
	function canQuery()
	{
		return true;
	}
	
	
	
	/**
	 * Before query
	 */
	function beforeQuery()
	{
		foreach ($this->rules as $rule)
		{
			$rule->beforeQuery($this);
		}
	}
	
	
	
	/**
	 * After query
	 */
	function afterQuery()
	{
		foreach ($this->rules as $rule)
		{
			$rule->afterQuery($this);
		}
	}
	
	
	
	/**
	 * Create response
	 */
	function createResponse()
	{
		/* Create response */
		foreach ($this->rules as $rule)
		{
			$rule->createResponse($this);
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
	 * Returns find item id
	 */
	public function getFindItemId()
	{
		return $this->container->vars["id"];
	}
	
	
	
	/**
	 * Find item
	 */
	public function findItem()
	{
		$class_name = $this->class_name;
		$instance = new $class_name();
		
		/* Get query */
		$id = urldecode( $this->getFindItemId() );
		$query = $class_name::query()->where($instance->getKeyName(), "=", $id);
		
		/* Filter query */
		$query = $this->findQuery($query);
		
		$this->item = $query->first();
		
		if ($this->item == null)
		{
			throw new ItemNotFoundException();
		}
	}
	
	
	
	/**
	 * Do action search
	 */
	function doActionSearch()
	{
		/* Find items */
		$this->findItems();
			
		/* Set response */
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
		$this->api_result->success( $result, "Ok" );
	}
	
	
	
	/**
	 * Do action get by id
	 */
	function doActionGetById()
	{
		/* Find items */
		$this->findItem();
		
		/* From database */
		$item = $this->fromDatabase( $this->item->getAttributes() );
		
		/* Set result */
		$this->api_result->success(["item" => $item ]);
	}
	
	
	
	/**
	 * Do action create
	 */
	function doActionCreate()
	{
		/* Convert to database */
		$old_data = $this->toDatabase($this->old_data);
			
		/* Update in database */
		$class_name = $this->class_name;
		$this->item = new $class_name();
		foreach ($old_data as $key => $value) $this->item->$key = $value;
		$this->item->save();
		$this->item->refresh();
		
		/* From database */
		$this->new_data = $this->fromDatabase($this->item);
		
		/* Set result */
		$this->api_result->success(["item"=>$this->new_data], "Ok");
	}
	
	
	
	/**
	 * Do action edit
	 */
	function doActionEdit()
	{
		/* Find item */
		$this->findItem();
			
		/* Convert to database*/
		$old_data = $this->toDatabase($this->old_data);
		
		/* Update in database */
		$class_name = $this->model_name;
		foreach ($old_data as $key => $value) $this->item->$key = $value;
		if ($this->item)
		{
			$this->item->save();
			$this->item->refresh();
		}
		
		/* From database */
		$this->new_data = $this->fromDatabase($this->item);
		
		/* Set result */
		$this->api_result->success(["item"=>$this->new_data], "Ok");
	}
	
	
	
	/**
	 * Do action delete
	 */
	function doActionDelete()
	{
		/* Find item */
		$this->findItem();
		
		/* Delete from database */
		if ($this->item)
		{
			$this->item->delete();
		}
		
		/* From database */
		$this->new_data = $this->fromDatabase($this->item);
		
		/* Set result */
		$this->api_result->success(["item"=>$this->new_data], "Ok");
	}
	
	
	
	/**
	 * Do action
	 */
	function doAction($name, $arguments)
	{
		throw new \Exception("Unknown action " . $name);
	}
	
	
	
	/**
	 * Action
	 */
	public function __call($name, $arguments)
	{
		$container = $arguments[0];
		
		/* Can query */
		$can_query = $this->canQuery();
		if ($can_query)
		{
			/* Before query */
			$this->beforeQuery();
			
			/* Find items */
			$method_name = "do" . ucfirst($name);
			if (method_exists($this, $method_name))
			{
				call_user_func([$this, $method_name]);
			}
			else
			{
				$this->doAction($name, $arguments);
			}
			
			/* After query */
			$this->afterQuery();
		}
		
		/* Create response */
		$this->createResponse();
		
		return $container;
    }
	
}