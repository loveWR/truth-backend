<?php  
namespace app\api\controller\v1;
use app\api\controller\ApiBase;
use think\Request;
/**
* 
*/
class Order extends ApiBase
{
	
	public function placeOrder()
	{
		$token=Request::instance()->header('token');
		
		return json(['state' => 'error', 'message' =>'服务器错误','errorcode'=>'111']);
		
		
	}
}