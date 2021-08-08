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

namespace TinyPHP;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Api Result
 */
class ApiResult
{
	var $data = null;
	var $error_code = 0;
	var $error_name = "";
	var $error_str = "";
	var $status_code = Response::HTTP_OK;


	
	/**
	 * Success
	 */
	function success($data)
	{
		$this->data = $data;
		$this->clearError();
		$this->error_code = 1;
		return $this;
	}



	/**
	 * Error
	 */
	function error($error_str = "", $error_code = -1)
	{
		$this->clearError();
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
		$this->error_str = $e->getMessage();
		$this->error_code = $e->getCode();
		$this->error_name = str_replace("\\", ".", get_class($e));
		if ($this->error_code >= 0)
		{
			$this->error_code = -1;
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
			"data" => $this->data,
			"error" =>
			[
				"code" => $this->error_code,
				"name" => $this->error_name,
				"str" => $this->error_str,
			],
		];
		return new Response
		(
			json_encode($res) . "\n",
			$this->status_code,
			['content-type' => 'application/json']
		);
	}
}