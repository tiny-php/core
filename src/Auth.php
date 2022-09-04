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


class Auth
{
	protected $jwt = null;
	protected $initialized = false;
	
	
	/**
	 * Check is auth
	 */
	function isAuth()
	{
		if (!$this->jwt) return false;
		if (!$this->jwt->isValid()) return false;
		return true;
	}
	
	
	
	/**
	 * Check permission
	 */
	function permission($name)
	{
		if (!$this->isAuth()) return false;
		return false;
	}
	
	
	
	/**
	 * Init auth
	 */
	function init($params = [])
	{
		if ($this->initialized) return;
		if (isset($params["jwt"])) $this->jwt = $params["jwt"];
		$this->initialized = true;
	}
	
	
	
	/**
	 * Returns JWT
	 */
	function getJWT()
	{
		return $this->jwt;
	}
	
	
	
	/**
	 * Returns JWT string
	 */
	function getJWTString()
	{
		return $this->jwt ? $this->jwt->getJWT() : "";
	}
	
	
	
	/**
	 * Call args
	 */
	public function __call($name, $arguments)
	{
		if ($this->jwt)
		{
			return call_user_func_array([$this->jwt, $name], $arguments);
		}
		return null;
    }
}