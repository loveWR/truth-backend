<?php
namespace app\api\exception;

/**
* api统一抛错类
*/
class ApiException extends BaseException
{
		
	public $code='400';
	public $msg='获取token失败';
	public $errorcode='401';
	
}