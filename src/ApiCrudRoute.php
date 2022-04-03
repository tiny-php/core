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


class ApiCrudRoute extends ApiRoute
{
	var $filter = null;
	var $start = 0;
	var $limit = 1000;
	var $item = null;
	var $old_data = null;
	var $new_data = null;
	var $update_data = null;
	
	
	
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
	function routes(RouteContainer $route_container)
	{
		if ($this->api_path != "")
		{
			$route_container->addRoute([
				"url" => "/" . $this->api_path . "/crud/search/",
				"name" => $this->api_name . "search",
				"method" => [$this, "actionSearch"],
			]);
			
			$route_container->addRoute([
				"url" => "/" . $this->api_path . "/crud/item/{id}/",
				"name" => $this->api_name . "getById",
				"method" => [$this, "actionGetById"],
			]);
			
			$route_container->addRoute([
				"methods" => [ "POST" ],
				"url" => "/" . $this->api_path . "/crud/create/",
				"name" => $this->api_name . "create",
				"method" => [$this, "actionCreate"],
			]);
			
			$route_container->addRoute([
				"methods" => [ "POST" ],
				"url" => "/" . $this->api_path . "/crud/edit/{id}/",
				"name" => $this->api_name . "edit",
				"method" => [$this, "actionEdit"],
			]);
			
			$route_container->addRoute([
				"methods" => [ "POST" ],
				"url" => "/" . $this->api_path . "/crud/delete/{id}/",
				"name" => $this->api_name . "delete",
				"method" => [$this, "actionDelete"],
			]);
		}
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
	 * Allow filter fields
	 */
	public function allowFilter($field_name, $op, $value)
	{
		return false;
	}
	
	
	
	/**
	 * Build filter
	 */
	public function buildSearchFilter($query, $action)
	{
		/* Build filter */
		$filter = $this->render_container->get("filter", null);
		if ($filter != null && gettype($filter) == "array")
		{
			$filter = array_map
			(
				function ($obj)
				{
					$obj = json_decode($obj, true);
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
	public function buildSearchQuery($query, $action)
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
	 * Get item
	 */
	public function getItem($id)
	{
		$class_name = $this->class_name;
		
		/* Get primary key */
		$pk = $class_name::firstPk();
		
		/* Get query */
		$query = $class_name::select()
			->addFilter($pk, "=", $id)
			->limit(1)
		;
		
		/* Search query */
		$query = $this->buildSearchQuery($query, "actionGetById");
		$data = $query->one();
		
		/* Create item */
		$item = $class_name::InstanceFromDatabase($data);
		return $item;
	}
	
	
	
	/**
	 * Find item
	 */
	public function findItem()
	{
		$id = $this->render_container->arg("id");
		$this->item = $this->getItem($id);
		if ($this->item == null)
		{
			throw new ItemNotFoundException();
		}
		
		$this->old_data = $this->item->toArray();
	}
	
	
	
	/**
	 * Init search
	 */
	function initSearch()
	{
		$max_limit = $this->getMaxLimit();
		$start = (int)$this->get("start", 0);
		$limit = (int)$this->get("limit", 0);
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
		$query = $class_name::select();
		
		/* Limit */
		$query
			->where($this->filter)
			->offset($this->start)
			->limit($this->limit)
		;
		
		/* Search query */
		$query = $this->buildSearchQuery($query, "actionSearch");
		
		/* Result */
		$this->items = $query->all();
		$this->total = $query->count(); 
	}
	
	
	
	/**
	 * Init update data
	 */
	function initUpdateData()
	{
		$content_type = $this->render_container->header('Content-Type');
		if (substr($content_type, 0, strlen('application/json')) != 'application/json')
		{
			throw new \Exception("Content type must be application/json");
		}
		
		$post = json_decode($this->render_container->request->getContent(), true);
		if ($post == null)
		{
			throw new \Exception("Post is null");
		}
		
		$update_data = Utils::attr($post, "item");
		if ($update_data === null)
		{
			throw new \Exception("Post item is empty");
		}
		
		$this->update_data = $this->toDatabase($update_data);
	}
	
	
	
	/**
	 * Validate
	 */
	public function validate($action)
	{
		if ($action == "actionCreate")
		{
		}
		
		else if ($action == "actionEdit")
		{
		}
		
		else if ($action == "actionDelete")
		{
		}
	}
	
	
	
	/**
	 * Process item
	 */
	function processItem($action, $item)
	{
		
	}
	
	
	
	/**
	 * Process after
	 */
	function processAfter($action)
	{
		
	}
	
	
	
	/**
	 * Do create
	 */
	function doCreate()
	{
		/* Create item */
		$class_name = $this->class_name;
		$this->item = new $class_name();
		
		/* Set data */
		if ($this->update_data != null)
		{
			foreach ($this->update_data as $key => $value)
			{
				$this->item->$key = $value;
			}
		}
		
		$this->processItem( "doCreate", $this->item );
		
		$this->item->save();
		$this->item->refresh();
		
		$this->new_data = $this->item->toArray();
		
		$this->processAfter( "doCreate" );
	}
	
	
	
	/**
	 * Do edit
	 */
	function doEdit()
	{
		/* Set data */
		if ($this->update_data != null)
		{
			foreach ($this->update_data as $key => $value)
			{
				$this->item->$key = $value;
			}
		}
		
		$this->processItem( "doEdit", $this->item );
		
		$this->item->save();
		$this->item->refresh();
		
		$this->new_data = $this->item->toArray();
		
		$this->processAfter( "doEdit" );
	}
	
	
	
	/**
	 * Do delete
	 */
	function doDelete()
	{
		$this->item->delete();
	}
	
	
	
	/**
	 * Build search response
	 */
	function buildResponse($action)
	{
		if ($action == "actionSearch")
		{
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
		
		else if ($action == "actionGetById")
		{
			$item = $this->fromDatabase($this->item);
			
			$result =
			[
				"item" => $item,
			];
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
		
		else if ($action == "actionCreate")
		{
			$new_data = $this->fromDatabase($this->new_data);
			
			$result =
			[
				"new_data" => $new_data,
			];
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
		
		else if ($action == "actionEdit")
		{
			$old_data = $this->fromDatabase($this->old_data);
			$new_data = $this->fromDatabase($this->new_data);
			
			$result =
			[
				"old_data" => $old_data,
				"new_data" => $new_data,
			];
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
		
		else if ($action == "actionDelete")
		{
			$old_data = $this->fromDatabase($this->old_data);
			
			$result =
			[
				"old_data" => $old_data,
			];
			
			/* Set result */
			$this->api_result->success( $result, "Ok" );
		}
	}
	
	
	
	/**
	 * Search action
	 */
	function actionSearch(RenderContainer $render_container)
	{
		$this->initSearch();
		$this->doSearch();
		$this->buildResponse("actionSearch");
		return $render_container;
	}
	
	
	
	/**
	 * Get by id
	 */
	function actionGetById(RenderContainer $render_container)
	{
		$this->findItem();
		$this->buildResponse("actionGetById");
		return $render_container;
	}
	
	
	
	/**
	 * Action create
	 */
	function actionCreate(RenderContainer $render_container)
	{
		$this->initUpdateData();
		$this->findItem();
		$this->validate("actionCreate");
		$this->doCreate();
		$this->buildResponse("actionCreate");
		return $render_container;
	}
	
	
	
	/**
	 * Action edit
	 */
	function actionEdit(RenderContainer $render_container)
	{
		$this->initUpdateData();
		$this->findItem();
		$this->validate("actionEdit");
		$this->doEdit();
		$this->buildResponse("actionEdit");
		return $render_container;
	}
	
	
	
	/**
	 * Action delete
	 */
	function actionDelete(RenderContainer $render_container)
	{
		$this->findItem();
		$this->validate("actionDelete");
		$this->doDelete();
		$this->buildResponse("actionDelete");
		return $render_container;
	}
}