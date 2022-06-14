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


class RSA
{
	
	static function getAlgo($algo)
	{
		if ($algo == "SHA1") return OPENSSL_ALGO_SHA1;
		if ($algo == "SHA256") return OPENSSL_ALGO_SHA256;
		if ($algo == "SHA384") return OPENSSL_ALGO_SHA384;
		if ($algo == "SHA512") return OPENSSL_ALGO_SHA512;
		if ($algo == "MD5") return OPENSSL_ALGO_MD5;
		return 0;
	}
	
	
	
	/**
	 * Sign message by private key
	 */
	static function sign($text, $private_key, $algo)
	{
		$algo_num = static::getAlgo($algo);
		$pk = @openssl_get_privatekey($private_key);
		$out = ''; @openssl_sign($text, $out, $pk, $algo_num);
		/*
		var_dump($pk);
		var_dump($algo_num);
		var_dump($private_key);
		var_dump($out);
		var_dump( openssl_error_string() );
		*/
		return $out;
	}
	
	
	
	/**
	 * Sign message by private key
	 */
	static function verify($text, $sign, $public_key, $algo)
	{
		$algo_num = static::getAlgo($algo);
		$pk = @openssl_get_publickey($public_key);
		$res = @openssl_verify($text, $sign, $pk, $algo_num);
		return $res;
	}
	
	
	
	/**
	 * Verify password
	 */
	static function encode($str, $private_key)
	{
		$pk = @openssl_get_privatekey($private_key);
		$r = str_split($str, 32);
		$r = array_map(function ($s) use ($pk){
			$out='';
			@openssl_private_encrypt($s, $out, $pk);
			return $out;
		}, $r);
		$s = implode("", $r);
		return base64_encode($s);
	}
	
	
	
	/**
	 * Create password hash
	 */
	static function decode($str, $public_key)
	{
		$pk = @openssl_get_publickey($public_key);
		$str = @base64_decode($str);
		$r = str_split($str, 64);
		$r = array_map(function ($s) use ($pk){
			$out='';
			@openssl_public_decrypt($s, $out, $pk);
			return $out;
		}, $r);
		$s = implode("", $r);
		return $s;
	}    
	
}