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


class Utils
{

	/**
	 * Intersect object
	 */
	function object_intersect($item, $keys)
	{
		$res = [];
		if (gettype($item) == 'array')
		{
			foreach ($item as $key => $val)
			{
				if (in_array($key, $keys))
				{
					$res[$key] = $val;
				}
			}
		}
		return $res;
	}
	
	

	/**
	 * Intersect object
	 */
	function object_intersect_curry($keys)
	{
		return function ($item) use ($keys)
		{
			return static::object_intersect($item, $keys);
		};
	}
	
	
	
	/**
	 * Array find index
	 */
	static function array_find_index(&$arr, $f)
	{
		foreach ($arr as $index => $value)
		{
			if (call_user_func_array($f, [$value, $index]))
			{
				return $index;
			}
		}
		return null;
	}
	
	
	
	/**
	 * Filter parse functions
	 */
	static function parseFilter($filter, $allow_filter_field_callback)
	{
		if (gettype($filter) != "array") return [];
		$filter = array_map
		(
			function ($obj) use ($allow_filter_field_callback)
			{
				$obj = json_decode($obj, true);
				if (gettype($obj) != "array") return null;
				if (count($obj) != 3) return null;
				if (!$allow_filter_field_callback($obj[0], $obj[1], $obj[2])) return null;
				return [$obj[0], $obj[1], $obj[2]];
			},
			$filter
		);
		$filter = array_filter($filter, function ($item){ return $item !== null; } );
		return $filter;
	}
	
	
	
	/**
	 * Create function
	 */
	static function method($obj, $method_name)
	{
		return function () use ($obj, $method_name)
		{
			return call_user_func_array([$obj, $method_name], func_get_args());
		};
	}
	
	
	
	/**
	 * List to array
	 */
	static function listToArray($items)
	{
		return array_map
		(
			function ($model){
				return ($model instanceof \TinyORM\Model) ? $model->toArray() : $model;
			},
			$items
		);
	}
	static function toArray($items){ return static::listToArray($items); }
	static function to_array($items){ return static::listToArray($items); }
	
	
	/**
	 * To datetime
	 */
	static function to_datetime($date, $tz = 'UTC', $format = 'Y-m-d H:i:s')
	{
		$tz = $tz instanceof \DateTimeZone ? $tz : new \DateTimeZone($tz);
		$dt = \DateTime::createFromFormat($format, $date, $tz);
		return $dt;
	}
	
	
	
	/**
	 * To timestamp
	 */
	static function to_timestamp($date, $tz = 'UTC', $format = 'Y-m-d H:i:s')
	{
		$dt = static::to_datetime($date, $tz, $format);
		if ($dt) return $dt->getTimestamp();
		return -1;
	}
	
	
	
	/**
	 * To date
	 */
	static function to_date($timestamp, $tz = 'UTC', $format = 'Y-m-d H:i:s')
	{
		$tz = $tz instanceof \DateTimeZone ? $tz : new \DateTimeZone($tz);
		$dt = new \DateTime();
		$dt->setTimestamp($timestamp);
		$dt->setTimezone($tz);
		return $dt->format($format);
	}
	
	
	
	/**
	 * To datetime
	 */
	static function dbtime($time, $tz = 'UTC', $format = 'Y-m-d H:i:s')
	{
		return static::to_date($time, $tz, $format);
	}
	
	
	
	/**
	 * Attr
	 */
	static function attr($obj, $keys, $default_value = null)
	{
		if (!$obj) return $default_value;
		if (gettype($obj) != "array") return $default_value;
		
		if (gettype($keys) != "array")
		{
			if (isset($obj[$keys])) return $obj[$keys];
			return $default_value;
		}
		
		$keys = array_values($keys);
		while (count($keys) != 0)
		{
			$key = $keys[0];
			if (!isset($obj[$key])) return $default_value;
			$obj = $obj[$key];
			array_shift($keys);
		}
		
		return $obj;
	}
	
	
	
	/**
	 * Encode base64 url
	 */
	static function base64_encode_url($s)
	{
		$s = base64_encode($s);
		$s = str_replace('+', '-', $s);
		$s = str_replace('/', '_', $s);
		$s = str_replace('=', '', $s);
		return $s;
	}
	
	
	
	/**
	 * Decode base64 url
	 */
	static function base64_decode_url($s)
	{
		$c = 4 - strlen($s) % 4;
		if ($c < 4 && $c > 0) $s .= str_repeat('=', $c);
		$s = str_replace('-', '+', $s);
		$s = str_replace('_', '/', $s);
		return base64_decode($s);
	}
}