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

namespace TinyPHP\Crypt;


class JWT extends \TinyPHP\Crypt\JWTCore
{
	
	protected $login = "";
	protected $expires = 0;
	
	
	/**
	 * Returns login
	 */
	function getLogin()
	{
		if (!$this->isValid()) return "";
		return $this->login;
	}
	
	
	
	/**
	 * Returns expires
	 */
	function getExpires()
	{
		if (!$this->isValid()) return false;
		return $this->expires;
	}
	
	
	
	/**
	 * Token is valid
	 */
	function isValid()
	{
		if ($this->login == "") return false;
		if ($this->expires < time()) return false;
		return parent::isValid();
	}
	
	
	
	/**
	 * Check expired
	 */
	function isExpired()
	{
		return $this->expires < time();
	}
	
	
	
	/**
	 * Set data
	 */
	function setData($data)
	{
		$this->login = isset($data["l"]) ? $data["l"] : "";
		$this->expires = isset($data["e"]) ? $data["e"] : 0;
	}
	
	
	
	/**
	 * Get data
	 */
	function getData()
	{
		return [
			"l" => $this->login,
			"e" => $this->expires,
		];
	}
	
	
	
	/**
	 * To array
	 */
	function toArray()
	{
		return [
			"login" => $this->login,
			"expires" => $this->expires,
		];
	}
	
	
	
	/**
	 * Get private key
	 */
	function getPrivateKey()
	{
		$private_key = env("JWT_PRIVATE_KEY");
		$res = json_decode( $private_key );
		if ($res) return $res;
		return $private_key;
	}
	
	
	
	/**
	 * Get public key
	 */
	function getPublicKey()
	{
		$private_key = env("JWT_PUBLIC_KEY");
		$res = json_decode( $private_key );
		if ($res) return $res;
		return $private_key;
	}
	
	
	
	/**
	 * Get type
	 */
	function getType()
	{
		return "RS256";
	}
	
	
}