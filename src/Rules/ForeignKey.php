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

namespace TinyPHP\Rules;

use TinyPHP\ApiCrudRoute;
use TinyPHP\Utils;


class ForeignKey extends AbstractRule
{
	var $api_name = "";
	var $class_name = "";
	var $foreign_key = "";
	var $join_key = "id";
	var $second_key = "";
	var $fromDatabase = null;
	var $buildSearchQuery = null;
	var $fields = null;
	var $actions = ["actionSearch", "actionGetById", "actionCreate", "actionUpdate"];
	var $convert = null;
	var $after = null;
	
	
	/**
	 * Get foreign ids
	 */
	function getForeignIds($action)
	{
		$result = [];
		
		if ($action == "actionSearch")
		{
			$result = array_map
			(
				function($item)
				{
					return isset($item[$this->foreign_key]) ?
						$item[$this->foreign_key] : ""
					;
				},
				$this->route->items
			);
		}
		
		else if (
			$action == "actionGetById" ||
			$action == "actionCreate" ||
			$action == "actionUpdate"
		)
		{
			if (isset($this->route->item[$this->foreign_key]))
			{
				$result = [ $this->route->item[$this->foreign_key] ];	
			}
		}
		
		return $result;
	}
	
	
	
	/**
	 * After query
	 */
	function processAfter($action)
	{
		if ($this->foreign_key == null) return;
		if ($this->join_key == null) return;
		
		if (in_array($action, $this->actions))
		{
			$result = [];
			
			/* Get foreign ids */
			$foreign_ids = $this->getForeignIds($action);
			
			/* Build query */
			$class_name = $this->class_name;
			$query = $class_name::selectQuery();
			$query->where($this->join_key, $foreign_ids);
				
			/* Get query */
			if ($this->buildSearchQuery)
			{
				$query = call_user_func_array(
					$this->buildSearchQuery,
					[$this, $action, $foreign_ids, $query]
				);
			}
			
			/* Select from database */
			$items = $query->all();
			foreach ($items as $item)
			{
				$item = $item->toArray();
				if ($this->fromDatabase)
				{
					$item = $this->fromDatabase($item);
				}
				if ($this->fields)
				{
					$item = Utils::object_intersect($item, $this->fields);
				}
				$result[] = $item;
			}
			
			/* Add result to items */
			if ($action == "actionSearch")
			{
				/* Add dictionary */
				$this->route->addDictionary($this->api_name, $result);
				
				foreach ($this->route->items as $index => $item)
				{
					$item_id = isset($item[$this->foreign_key]) ?
						$item[$this->foreign_key] : ""
					;
					$data = array_filter
					(
						$result,
						function ($data_item) use ($item_id)
						{
							return $data_item[$this->join_key] == $item_id;
						},
					);
					$data = array_values($data);
					
					if ($this->convert)
					{
						$this->route->items[$index] = call_user_func_array(
							$this->convert,
							[$this, $action, $item, $data, $index]
						);
					}
				}
			}
			
			else if (
				$action == "actionGetById" ||
				$action == "actionCreate" ||
				$action == "actionUpdate"
			)
			{
				if ($this->convert)
				{
					$this->route->item = call_user_func_array(
						$this->convert,
						[$this, $action, $this->route->item, $result, -1]
					);
				}
			}
			
		}
		
	}
	
	
}