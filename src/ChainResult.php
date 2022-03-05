<?php

/*!
 * Tiny ORM Framework
 * 
 * MIT License
 * 
 * Copyright (c) 2020 - 2021 "Ildar Bikmamatov" <support@bayrell.org>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace TinyPHP;


class ChainResult implements \ArrayAccess
{
	public $__data = [];
	
	
	/**
	 * Getter and Setter
	 */
	public function get($key, $value = null)
	{
		return ($this->__data && isset($this->__data[$key])) ?
			$this->__data[$key] : $value
		;
	}
	public function set($key, $value)
	{
		if (!$this->__data)
		{
			$this->__data = [];
		}
		$this->__data[$key] = $value;
	}
	public function exists($key)
	{
		return $this->__data && isset($this->__data[$key]);
	}
	public function unset($key)
	{
		if ($this->__data && isset($this->__data[$key]))
		{
			unset($this->__data[$key]);
		}
	}
	
	
	
	/**
	 * Array methods
	 */
	public function offsetExists($key)
	{
		return $this->exists($key);
	}
	public function offsetUnset($offset)
	{
		$this->unset($key);
	}
	public function offsetGet($key)
	{
		return $this->get($key);
	}
	public function offsetSet($key, $value)
	{
		$this->set($key, $value);
	}
	
	
	
	/**
	 * Magic methods
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}
	public function __get($key)
	{
		return $this->get($key);
	}
	public function __isset($key)
	{
		return $this->exists($key);
	}
	public function __unset($key)
	{
		$this->unset($key);
	}
	
}
	