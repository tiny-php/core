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

use TinyORM\Model;
use TinyPHP\Exception\ItemNotFoundException;


class ApiCrudRoute extends ApiRoute
{
	var $filter = null;
	var $start = 0;
	var $limit = 1000;
	var $pages = 1000;
	var $item = null;
	var $old_data = null;
	var $new_data = null;
	var $update_data = null;
	var $rules = null;
	
	
	
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
	function routes(RouteList $routes)
	{
		if ($this->api_name != "")
		{
			$routes->addRoute([
				"url" => "/api/" . $this->api_name . "/crud/search/",
				"name" => "api:" . $this->api_name . ":crud:search",
				"method" => [$this, "actionSearch"],
			]);
			
			$routes->addRoute([
				"url" => "/api/" . $this->api_name . "/crud/item/",
				"name" => "api:" . $this->api_name . ":crud:getById",
				"method" => [$this, "actionGetById"],
			]);
			
			$routes->addRoute([
				"methods" => [ "POST" ],
				"url" => "/api/" . $this->api_name . "/crud/create/",
				"name" => "api:" . $this->api_name . ":crud:create",
				"method" => [$this, "actionCreate"],
			]);
			
			$routes->addRoute([
				"methods" => [ "POST" ],
				"url" => "/api/" . $this->api_name . "/crud/update/",
				"name" => "api:" . $this->api_name . ":crud:update",
				"method" => [$this, "actionUpdate"],
			]);
			
			$routes->addRoute([
				"methods" => [ "POST" ],
				"url" => "/api/" . $this->api_name . "/crud/delete/",
				"name" => "api:" . $this->api_name . ":delete",
				"method" => [$this, "actionDelete"],
			]);
		}
	}
	
	
	
	/**
	 * Init
	 */
	function init($action)
	{
		parent::init($action);
		
		/* Get rules */
		$this->rules = $this->getRules();
		
		/* Init rules */
		foreach ($this->rules as $rule)
		{
			$rule->init($this, $action);
		}
	}
	
	
	
	/**
	 * After request
	 */
	public function after($action)
	{
		/* After request rules */
		foreach ($this->rules as $rule)
		{
			$rule->after($action);
		}
	}
	
	
	
	/**
	 * From database
	 */
	function fromDatabase($action, $item)
	{
		if ($item instanceof \TinyORM\Model)
		{
			$item = $item->toArray();
		}
		
		$old_item = $item;
		foreach ($this->rules as $rule)
		{
			$item = $rule->fromDatabase($action, $item, $old_item);
		}
		
		return $item;
	}
	
	
	
	/**
	 * To database
	 */
	function toDatabase($action, $item)
	{
		$old_item = $item;
		foreach ($this->rules as $rule)
		{
			$item = $rule->toDatabase($action, $item, $old_item);
		}
		return $item;
	}
	
	
	
	/**
	 * Allow filter fields
	 */
	public function allowFilter($field_name, $op, $value)
	{
		return false;
	}
	
	
	
	/**
	 * Build filter
	 */
	public function buildSearchFilter()
	{
		/* Build filter */
		$filter = $this->container->post("filter", null);
		if ($filter != null && gettype($filter) == "array")
		{
			$filter = array_map
			(
				function ($obj)
				{
					if (gettype($obj) != "array") return null;
					if (count($obj) != 3) return null;
					if (!$this->allowFilter($obj[0], $obj[1], $obj[2])) return null;
					return [$obj[0], $obj[1], $obj[2]];
				},
				$filter
			);
			$filter = array_filter($filter, function ($item){ return $item !== null; } );
		}
		else
		{
			$filter = [];
		}
		
		/* call chain */
		$res = call_chain("api_crud_build_search_filter", [
			"route" => $this,
			"filter" => $filter,
		]);
		$filter = $res->filter;
		
		return $filter;
	}
	
	
	
	/**
	 * Serch query
	 */
	public function buildSearchQuery($action, $query)
	{
		$res = call_chain("api_crud_build_search_query", [
			"route" => $this,
			"query" => $query,
			"action" => $action
		]);
		$query = $res["query"];
		return $query;
	}
	
	
	
	/**
	 * Default query
	 */
	public function buildDefaultQuery($action, $query)
	{
		$res = call_chain("api_crud_build_default_query", [
			"route" => $this,
			"query" => $query,
			"action" => $action
		]);
		$query = $res["query"];
		return $query;
	}
	
	
	
	/**
	 * Get item
	 */
	public function getItem($data)
	{
		$class_name = $this->class_name;
		
		/* Get primary key */
		$pk = $class_name::getPrimaryData($data);
		$pk = array_map(
			function($key, $value)
			{
				return ["t.".$key, "=", $value];
			},
			array_keys($pk), array_values($pk)
		);
		
		/* Get query */
		$query = $class_name::selectQuery()
			//->debug(true)
			->where($pk)
			->limit(1)
		;
		
		/* Search query */
		$query = $this->buildSearchQuery("actionGetById", $query);
		$item = $query->one();
		
		return $item;
	}
	
	
	
	/**
	 * Find item
	 */
	public function findItem()
	{
		$class_name = $this->class_name;
		
		$pk_data = [];
		$pk_post = $this->container->post("pk");
		
		$this->item = $this->getItem($pk_post);
		if ($this->item == null)
		{
			throw new ItemNotFoundException();
		}
		
		$this->old_data = $this->item->toArray();
	}
	
	
	
	/**
	 * Refresh item
	 */
	public function refreshItem()
	{
		$pk = $this->item->getPk();
		$this->item = $this->getItem( $pk );
		if ($this->item == null)
		{
			throw new \Exception("Refresh item is failed");
		}
	}
	
	
	
	/**
	 * Init search
	 */
	function initSearch()
	{
		$max_limit = $this->getMaxLimit();
		$start = (int)$this->container->post("start", -1);
		$page = (int)$this->container->post("page", -1);
		$limit = (int)$this->container->post("limit", 50);
		if ($start < 0) $start = ($page - 1) * $limit;
		if ($start < 0) $start = 0;
		if ($limit < 0) $limit = 0;
		if ($limit > $max_limit) $limit = $max_limit;
		
		$filter = $this->buildSearchFilter();
		
		$this->start = $start;
		$this->limit = $limit;
		$this->filter = $filter;
	}
	
	
	
	/**
	 * Do search
	 */
	function doSearch()
	{
		$class_name = $this->class_name;
		
		/* Get query */
		$query = $class_name::selectQuery();
		
		/* Limit */
		$query
			->where($this->filter)
			->offset($this->start)
			->limit($this->limit)
		;
		
		/* Search query */
		$query = $this->buildSearchQuery("actionSearch", $query);
		$query = $this->buildDefaultQuery("actionSearch", $query);
		$items = $query->all();
		
		/* Result */
		$this->items = $items;
		$this->total = $query->count(); 
		$this->page = $query->getPage(); 
		$this->pages = $query->getPages(); 
		
		$this->processAfter( "actionSearch" );
		
		/* Convert to Array */
		$items = $this->items;
		$this->items = [];
		foreach ($items as $item)
		{
			$this->items[] = $item->toArray();
		}
	}
	
	
	
	/**
	 * Init update data
	 */
	function initUpdateData($action)
	{
		$post = $this->container->post();
		if ($post == null || (gettype($post) == "array" && count($post) == 0))
		{
			throw new \Exception("Post is null");
		}
		
		$update_data = Utils::attr($post, "item");
		if ($update_data === null)
		{
			throw new \Exception("Post item is empty");
		}
		
		$this->post = $post;
		$this->update_data = $update_data;
		// $update_data = $this->toDatabase("actionCreate", $this->update_data);
	}
	
	
	
	/**
	 * Validate
	 */
	public function validate($action)
	{
		if ($action == "actionCreate")
		{
		}
		
		else if ($action == "actionUpdate")
		{
		}
		
		else if ($action == "actionDelete")
		{
		}
	}
	
	
	
	/**
	 * Process item before query
	 */
	function processBefore($action)
	{
		foreach ($this->rules as $rule)
		{
			$rule->processBefore($action);
		}
	}
	
	
	
	/**
	 * Process after query
	 */
	function processAfter($action)
	{
		foreach ($this->rules as $rule)
		{
			$rule->processAfter($action);
		}
	}
	
	
	
	/**
	 * Do create
	 */
	function doCreateItem()
	{
		$class_name = $this->class_name;
		$this->item = new $class_name();
	}
	
	
	
	/**
	 * Do create
	 */
	function doCreate()
	{
		/* Create item */
		$this->doCreateItem();
		
		/* Set data */
		if ($this->update_data != null)
		{
			$update_data = $this->toDatabase("actionCreate", $this->update_data);
			foreach ($update_data as $key => $value)
			{
				$this->item->$key = $value;
			}
		}
		
		/* Process before */
		$this->processBefore( "actionCreate" );
		
		/* Save and refresh */
		$this->item->save();
		
		/* Refresh item */
		$this->refreshItem();
		
		/* Process after */
		$this->processAfter( "actionCreate" );
		
		/* To array */
		$this->new_data = $this->item->toArray();
	}
	
	
	
	/**
	 * Do update
	 */
	function doUpdate()
	{
		/* Set data */
		if ($this->update_data != null)
		{
			$update_data = $this->toDatabase("actionUpdate", $this->update_data);
			foreach ($update_data as $key => $value)
			{
				$this->item->$key = $value;
			}
		}
		
		/* Process before */
		$this->processBefore( "actionUpdate" );
		
		/* Save and refresh */
		$this->item->save();
		
		/* Refresh item */
		$this->refreshItem();
		
		/* Process after */
		$this->processAfter( "actionUpdate" );
		
		/* To array */
		$this->new_data = $this->item->toArray();
	}
	
	
	
	/**
	 * Do delete
	 */
	function doDelete()
	{
		$this->item->delete();
	}
	
	
	
	/**
	 * Add dictionary
	 */
	public function addDictionary($api_name, $items)
	{
		if ($api_name == null) return;
		if (!isset($this->api_result->result["dictionary"]))
		{
			$this->api_result->result["dictionary"] = [];
		}
		$this->api_result->result["dictionary"][$api_name] = $items;
	}
	
	
	
	/**
	 * Build search response
	 */
	function buildResponse($action)
	{
		if ($action == "actionSearch")
		{
			$result = $this->api_result->result;
			
			$result["items"] = [];
			$result["filter"] = $this->filter;
			$result["start"] = (int)$this->start;
			$result["limit"] = (int)$this->limit;
			$result["total"] = (int)$this->total;
			$result["pages"] = (int)$this->pages;
			$result["page"] = (int)$this->page;
			
			/* Set items */
			foreach ($this->items as $item)
			{
				$item = $this->fromDatabase($action, $item);
				$result["items"][] = $item;
			}
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
		
		else if ($action == "actionGetById")
		{
			$item = $this->fromDatabase($action, $this->item);
			
			$result = $this->api_result->result;
			$result["item"] = $item;
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
		
		else if ($action == "actionCreate")
		{
			$new_data = $this->fromDatabase($action, $this->new_data);
			
			$result = $this->api_result->result;
			$result["new_data"] = $new_data;
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
		
		else if ($action == "actionUpdate")
		{
			$old_data = $this->fromDatabase($action, $this->old_data);
			$new_data = $this->fromDatabase($action, $this->new_data);
			
			$result = $this->api_result->result;
			$result["old_data"] = $old_data;
			$result["new_data"] = $new_data;
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
		
		else if ($action == "actionDelete")
		{
			$old_data = $this->fromDatabase($action, $this->old_data);
			
			$result = $this->api_result->result;
			$result["old_data"] = $old_data;
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
		
		/* Build response */
		foreach ($this->rules as $rule)
		{
			$rule->buildResponse($action);
		}
	}
	
	
	
	/**
	 * Search action
	 */
	function actionSearch()
	{
		$this->initSearch();
		$this->validate("actionSearch");
		$this->doSearch();
		$this->buildResponse("actionSearch");
	}
	
	
	
	/**
	 * Get by id
	 */
	function actionGetById()
	{
		$this->findItem();
		$this->validate("actionGetById");
		$this->processAfter( "actionGetById" );
		$this->buildResponse("actionGetById");
	}
	
	
	
	/**
	 * Action create
	 */
	function actionCreate()
	{
		$this->initUpdateData("actionCreate");
		$this->validate("actionCreate");
		$this->doCreate();
		$this->buildResponse("actionCreate");
	}
	
	
	
	/**
	 * Action update
	 */
	function actionUpdate()
	{
		$this->initUpdateData("actionUpdate");
		$this->findItem();
		$this->validate("actionUpdate");
		$this->doUpdate();
		$this->buildResponse("actionUpdate");
	}
	
	
	
	/**
	 * Action delete
	 */
	function actionDelete()
	{
		try
		{
			$this->findItem();
		}
		catch (ItemNotFoundException $e)
		{
			$this->api_result->success( null, "Ok" );
			return;
		}
		
		$this->validate("actionDelete");
		$this->doDelete();
		$this->buildResponse("actionDelete");
	}
}