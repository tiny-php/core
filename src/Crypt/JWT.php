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

use TinyPHP\Utils;


class JWT
{
	var $jwt;
	var $is_valid;
	
	
	/**
	 * Token is valid
	 */
	function isValid()
	{
		return $this->is_valid;
	}
	
	
	
    /**
	 * Returns method
	 */
	static function getMethod($algo)
	{
		if ($algo == 'HS256') return "hash";
		if ($algo == 'HS384') return "hash";
		if ($algo == 'HS512') return "hash";
		if ($algo == 'RS256') return "rsa";
		if ($algo == 'RS384') return "rsa";
		if ($algo == 'RS512') return "rsa";
		return "";
	}
	
	
	
	/**
	 * Returns method
	 */
	static function getAlgo($algo)
	{
		if ($algo == 'HS256') return "SHA256";
		if ($algo == 'HS384') return "SHA384";
		if ($algo == 'HS512') return "SHA512";
		if ($algo == 'RS256') return "SHA256";
		if ($algo == 'RS384') return "SHA384";
		if ($algo == 'RS512') return "SHA512";
		return "";
	}
	
	
	
	/**
	 * Create jwt sign
	 */
	static function createSign($head_b64, $data_b64, $key, $algo)
	{
		$m = static::getMethod($algo);
		$a = static::getAlgo($algo);
		$text = $head_b64 . '.' . $data_b64;
		$sign = "";
		if ($m == "rsa") $sign = RSA::sign($text, $key, $a);
		else if ($m == "hash") $sign = HASH::hash($text, $key, $a);
		$sign = Utils::base64_encode_url($sign);
		return $sign;
	}
	
	
	
	/**
	 * Validate jwt sign
	 */
	static function validateSign($head_b64, $data_b64, $sign, $key, $algo)
	{
		$m = static::getMethod($algo);
		$a = static::getAlgo($algo);
		$text = $head_b64 . '.' . $data_b64;
		$flag = false;
		$sign = Utils::base64_decode_url($sign);
		if ($m == "rsa") $flag = RSA::verify($text, $sign, $key, $a);
		else if ($m == "hash") $flag = HASH::verify($text, $sign, $key, $a);
		return $flag;
	}
	
	
	
	/**
	 * Create jwt
	 */
	static function encode($d, $key, $algo)
	{
		$data_json = json_encode($d);
		$data_b64 = Utils::base64_encode_url($data_json);
		$head_b64 = Utils::base64_encode_url(json_encode(['alg'=>$algo,'typ'=>'JWT']));
		$sign = static::createSign($head_b64, $data_b64, $key, $algo);
		return $head_b64 . '.' . $data_b64 . '.' . $sign;
	}
	
	
	
	/**
	 * Decode jwt
	 */
	static function decode($token_str, $key, $algo = "", $check_sign = true)
	{
		$arr = explode(".", $token_str);
		$head_b64 = isset($arr[0]) ? $arr[0] : "";
		$data_b64 = isset($arr[1]) ? $arr[1] : "";
		$sign = isset($arr[2]) ? $arr[2] : "";
		
		/* Decode head */
		$head_json = base64_decode($head_b64);
		$head = json_decode($head_json, true);
		
		/* Decode data */
		$json = base64_decode($data_b64);
		if ($json == "") return null;
		$data = json_decode($json, true);
		
		if ($head == null)
			return
			[
				"head" => null,
				"data" => $data,
				"valid" => false,
			];
		
		$token_algo = isset($head["alg"]) ? $head["alg"] : "";
		if ($token_algo == "")
			return
			[
				"head" => $head,
				"data" => $data,
				"valid" => false,
			];
		
		if ($token_algo != $algo && $algo != "")
		{
			return
			[
				"head" => $head,
				"data" => $data,
				"valid" => false,
			];
		}
		
		/* Validate sign */
		if ($check_sign)
		{
			$flag = static::validateSign($head_b64, $data_b64, $sign, $key, $token_algo);
			if ($flag)
			{
				return
				[
					"head" => $head,
					"data" => $data,
					"valid" => true,
				];
			}
		}
		
		return
		[
			"head" => $head,
			"data" => $data,
			"valid" => false,
		];
	}
    
	
	
	/**
	 * Check expired
	 */
	function isExpired()
	{
		return true;
	}
	
	
	
	/**
	 * Set data
	 */
	function setData($data)
	{
	}
	
	
	
	/**
	 * Get data
	 */
	function getData()
	{
		return [];
	}
	
	
	
	/**
	 * To array
	 */
	function toArray()
	{
		return [];
	}
	
	
	
	/**
	 * Get private key
	 */
	function getPrivateKey()
	{
		return "";
	}
	
	
	
	/**
	 * Get public key
	 */
	function getPublicKey()
	{
		return "";
	}
	
	
	
	/**
	 * Get type
	 */
	function getType()
	{
		return "RS512";
	}
	
	
	
	/**
	 * Get jwt content
	 */
	function getJWT()
	{
		$data = $this->getData();
		$key = $this->getPrivateKey();
		$type = $this->getType();
		return static::encode($data, $key, $type);
	}
	
	
	
	/**
	 * Build jwt
	 */
	function buildJWT()
	{
		$this->jwt = $this->getJWT();
	}
	
	
	
	/**
	 * Create jwt
	 */
	static function create($token_str, $check_sign = true)
	{
		$class_name = static::class;
		$res = new $class_name();
		
		$key = $res->getPublicKey();
		$type = $res->getType();
		$decode = static::decode($token_str, $key, $type, $check_sign);
		
		if ($decode["data"] == null) return null;
		
		$res->jwt = $token_str;
		$res->setData($decode["data"]);
		$res->is_valid = $decode["valid"];
		return $res;
	}
}