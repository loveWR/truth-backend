<?php
namespace app\api\controller\v1;
use app\api\exception\ApiException;
use app\api\service\Tokenservice;
use think\Exception;
use think\Request;
/**
* 客户端初始化获取和验证token
*/
class Token 
{

	public function getToken()
	{
		$code=input('code');
		if (empty($code)) {
			throw new ApiException(['errorcode'=>'400','msg'=>'没有code']);
		}
		$ts=new Tokenservice($code);
		$token=$ts->get();
		return json(['token'=>$token]);
	}

	public function verifyToken()
	{	
		$token=input('token');
		if (cache($token)) {
			return json(['isValid'=>true]);
		}
		else{
			return json(['isValid'=>false]);
		}
	}


}