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


class ManyToMany extends AbstractRule
{
	var $api_name = "";
	var $foreign_key = "";
	var $join_key = "";
	var $second_key = "";
	var $fromDatabase = null;
	var $buildSearchQuery = null;
	var $fields = null;
	var $actions = ["actionSearch", "actionGetById"];
	
	
	
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
		
		else if ($action == "actionEdit")
		{
			$result = [ $this->route->item->getFirstPk() ];
		}
		
		return $result;
	}
	
	
	
	/**
	 * After query
	 */
	function processAfter($action)
	{
		if ($this->api_name == null) return;
		if ($this->foreign_key == null) return;
		if ($this->join_key == null) return;
		
		if (in_array($action, $this->actions))
		{
			$result = [];
			
			/* Get foreign ids */
			$foreign_ids = $this->getForeignIds($action);
			
			/* Get query */
			if ($this->buildSearchQuery)
			{
				$query = call_user_func_array(
					$this->buildSearchQuery,
					[$this, $action, $foreign_ids]
				);
			}
			
			/* Get items */
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
				
				foreach ($this->route->items as $item)
				{
					$item_id = isset($item["id"]) ? $item["id"] : "";
					$data = array_filter
					(
						$result,
						function($item) use ($item_id)
						{
							return $item[$this->join_key] == $item_id;
						},
					);
					$data = array_values($data);
					$item[$this->api_name] = $data;
				}
			}
			
			else if ($action == "actionEdit")
			{
				$update_data = isset($this->route->update_data[$this->api_name]) ?
					$this->route->update_data[$this->api_name] : null;
					
				$update_data = array_values($update_data);
				
				if ($update_data !== null)
				{
					$copy_result = $result;
					$index = count($copy_result) - 1;
					
					/* Delete */
					while ($index >= 0)
					{
						$item = $copy_result[$index];
						$item_second_key_id = $item[$this->second_key];
						$find = false;
						foreach ($update_data as $new_item)
						{
							$new_item_second_key_id = $new_item[$this->second_key];
							if ($new_item_second_key_id == $item_second_key_id)
							{
								$find = true;
								break;
							}
						}
						if (!$find)
						{
							unset($result[$index]);
							if ($this->deleteQuery)
							{
								$query = call_user_func_array(
									$this->deleteQuery,
									[$this, $action, $item]
								);
							}
						}
						$index--;
					}
					
					/* Add */
					$index = 0;
					$count_update_data = count($update_data);
					
					while ($index < $count_update_data)
					{
						$new_item = $update_data[$index];
						$new_item_second_key_id = $new_item[$this->second_key];
						$find = false;
						
						foreach ($result as $item)
						{
							$item_second_key_id = $item[$this->second_key];
							if ($new_item_second_key_id == $item_second_key_id)
							{
								$find = true;
								break;
							}
						}
						
						if (!$find)
						{
							$result[] = $new_item;
							if ($this->addQuery)
							{
								$query = call_user_func_array(
									$this->addQuery,
									[$this, $action, $new_item]
								);
							}
						}
						
						$index++;
					}
					
				}
				
				$this->route->new_data[$this->api_name] = $result;
			}
		}
		
	}
	
	
}