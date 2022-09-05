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


class Bus
{
	
	/**
	 * Returns curl
	 */
	static function curl($url, $data)
	{
		$time = time();
		$bus_key = app()->settings("bus_key");
		$arr = array_keys($data); sort($arr);
		array_push($arr, $time);
		$text = implode("|", $arr);
		$sign = hash_hmac("SHA512", $text, $bus_key);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_TIMEOUT, 5);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 10);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode
		(
			[
				"data" => $data,
				"time" => $time,
				"sign" => $sign,
				"alg" => "sha512",
			]
		));
		
		$out = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		$code = (int)$code;
		
		return [$code, $out];
	}
	
	
	
	/**
	 * Send api request
	 */
	static function send_api_request($url, $data)
	{
		$response = null;
		list($code, $out) = static::curl($url, $data);
		
		$response = @json_decode($out, true);
		
		if ($response == null)
		{
			$response = [
				"result" => null,
				"error" => [
					"code" => ERROR_GATEWAY_API,
					"name" => "default",
					"str" => "Gateway error",
				]
			];
		}
		
		$result = new \TinyPHP\ApiResult();
		$result->setApiResponse( $response );
		$result->url = $url;
		$result->res_content = $out;
		$result->ob_content = Utils::attr($response, "ob_content", "");
		$result->status_code = $code;
		return $result;
	}
	
	
	
	/**
	 * Call api
	 */
	static function call($url, $data)
	{
		$url = preg_replace("/^\/+/", "", $url);
		$arr = explode("/", $url, 2);
		
		$project = isset($arr[0]) ? $arr[0] : "";
		$relative_url = isset($arr[1]) ? $arr[1] : "";
		$relative_url = preg_replace("/^\/+/", "", $relative_url);
		
		/* Get gateway url */
		$res = call_chain("bus_gateway", ["project"=>$project, "gateway"=>""]);
		$gateway = $res->gateway;
		$gateway = preg_replace("/\/+$/", "", $gateway);
		
		if ($gateway == "")
		{
			$result = new \TinyPHP\ApiResult();
			$result->error(
				null, 
				"Gateway url for project '${project}' is empty",
				ERROR_GATEWAY_API
			);
			return $result;
		}
		
		return static::send_api_request($gateway . "/" . $relative_url, $data);
	}
	
}