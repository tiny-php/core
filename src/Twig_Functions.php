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


class Twig_Functions
{
	
	/**
	 * Dump
	 */
	static function dump($v)
	{
		echo "<pre>";
		var_dump($v);
		echo "</pre>";
	}
	
	
	
	/**
	 * Implode
	 */
	static function implode($ch, $arr)
	{
		if (gettype($arr) == "array")
		{
			return implode($ch, $arr);
		}
		return "";
	}
	
	
	
	/**
	 * Output selected="selected"
	 */
	static function form_selected($value, $check_value)
	{
		if ($value == $check_value) return "selected='selected'";
		return "";
	}
	
	
	
	/**
	 * Output checked="checked"
	 */
	static function form_checked($value, $check_value)
	{
		if ($value == $check_value) return "checked='checked'";
		return "";
	}
	
	
	
	/**
	 * Url
	 */
	static function url($route_name, $params = [])
	{
		return url($route_name, $params);
	}
	
	
	
	/**
	 * Url get add
	 */
	static function url_get_add($route_name, $params = [])
	{
		return url_get_add($route_name, $params);
	}
}