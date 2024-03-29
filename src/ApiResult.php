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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Api Result
 */
class ApiResult
{
	var $result = [];
	var $exception = null;
	var $error_code = 0;
	var $error_name = "";
	var $error_str = "";
	var $error_file = "";
	var $error_line = "";
	var $error_trace = [];
	var $api_response = null;
	var $url = null;
	var $ob_content = null;
	var $res_content = null;
	var $status_code = Response::HTTP_OK;
	
	
	/**
	 * Show
	 */
	function debug($show_content = false)
	{
		$res = "";
		
		if ($this->ob_content)
		{
			$res .= $this->ob_content . "\n";
		}
		if ($this->error_code < 0 && count($this->error_trace) > 0)
		{
			$res .= "[" . $this->error_code . "] " . $this->error_str . " in ";
			$res .= $this->error_file . ": " . $this->error_line . "\n";
			$res .= "<b>Trace:</b>\n";
			foreach ($this->error_trace as $key => $trace)
			{
				if (isset($trace["file"]) && isset($trace["line"]))
				{
					$msg = $trace["file"] . ": " . $trace["line"];
					$res .= "${key}. ${msg}";
					$res .= "\n";
				}
			}
			$res .= "\n";
		}
		if ($show_content)
		{
			$res .= $this->url . "\n";
			$res .= "Status code: " . $this->status_code . "\n";
			$res .= $this->res_content . "\n";
		}
		
		if ($res)
		{
			echo "<pre>";
			echo $res;
			echo "</pre>";
		}
	}
	
	
	
	/**
	 * Set api response
	 */
	function setApiResult($api_result)
	{
		$this->result = $api_result->result;
		$this->error_str = $api_result->error_str;
		$this->error_code = $api_result->error_code;
		$this->error_name = $api_result->error_name;
		$this->error_file = $api_result->error_file;
		$this->error_line = $api_result->error_line;
		$this->error_trace = $api_result->error_trace;
	}
	
	
	
	/**
	 * Get error
	 */
	function getError()
	{
		return [
			"str" => $this->error_str,
			"code" => $this->error_code,
			"name" => $this->error_name,
		];
	}
	
	
	
	/**
	 * Is success
	 */
	function isSuccess()
	{
		return $this->error_code == 1;
	}
	
	
	
	/**
	 * Success
	 */
	function success($result = null, $error_str = "", $error_code = 1)
	{
		$this->clearError();
		if ($result) $this->result = $result;
		$this->error_str = $error_str;
		$this->error_code = $error_code;
		return $this;
	}
	
	
	
	/**
	 * Error
	 */
	function error($result = null, $error_str = "", $error_code = -1)
	{
		$this->clearError();
		if ($result) $this->result = $result;
		$this->error_str = $error_str;
		$this->error_code = $error_code;
		return $this;
	}
	
	
	
	/**
	 * Exception
	 */
	function exception($e)
	{
		$this->clearError();
		$this->exception = $e;
		$this->error_str = $e->getMessage();
		$this->error_code = $e->getCode();
		$this->error_name = str_replace("\\", ".", get_class($e));
		$this->error_file = $e->getFile();
		$this->error_line = $e->getLine();
		$this->error_trace = $e->getTrace();
		if ($this->error_code >= 0)
		{
			$this->error_code = -1;
		}
		
		if (get_class($this->exception) == "Error")
		{
			$this->error_str = $e->getMessage() . " in " . $this->error_file .
				" on line " . $this->error_line;
		}
		
		return $this;
	}


	
	/**
	 * Exception
	 */
	function internalError()
	{
		$this->status_code = Response::HTTP_INTERNAL_SERVER_ERROR;
		return $this;
	}



	/**
	 * Clear error
	 */
	function clearError()
	{
		$this->error_code = 0;
		$this->error_file = "";
		$this->error_line = "";
		$this->error_name = "";
		$this->error_str = "";
		return $this;
	}



	/**
	 * Returns response
	 */
	function getResponse()
	{
		$res =
		[
			"result" => $this->result,
			"error" =>
			[
				"code" => $this->error_code,
				"name" => $this->error_name,
				"str" => $this->error_str,
			],
		];
		
		// $is_debug = env("APP_DEBUG");
		if ($this->error_name != "" && $this->error_code < 0)
		{
			$res["error"]["file"] = $this->error_file;
			$res["error"]["line"] = $this->error_line;
			$res["error"]["trace"] = $this->error_trace;
			if ($this->error_file != "" && $this->error_line != "")
			{
				$res["error"]["str"] = $this->error_str;
			}
		}
		
		return new Response
		(
			json_encode($res) . "\n",
			$this->status_code,
			['content-type' => 'application/json']
		);
	}
}