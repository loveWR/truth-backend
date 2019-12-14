<?php  
namespace app\api\controller;
use think\Controller;
use app\api\exception\ApiException;
use think\Request;
use think\Db;
use think\Cache;
/**
* 所有涉及用户信息的控制器继承此类
*/
class ApiBase extends Controller
{
	
	function __construct()
	{
		$token=Request::instance()->header('token');
		$safeSource=Request::instance()->header('isajax');
		if (!Cache::get($token) ) {
			throw new ApiException(['errorcode'=>'401','msg'=>'Token过期']);
		}
		if (!$safeSource) {
			throw new ApiException(['errorcode'=>'403','msg'=>'访问不安全']);
		}
		
		$is_ym=$this->getScope();
		if (!$is_ym) {
			throw new ApiException(['errorcode'=>'400','msg'=>'非一秒员工！']);
			return false;
		}

		$uid=$this->getCurrentUid();
		session('uid',$uid);
		Db::name('users')->where('id',$uid)->update(['lastlogin'=>date('Y-m-d H:i:s')]);
				
	}
		//根据参数返回相应缓存中token对应的的参数值
	public function getvar($key='')
	{	
		//获取header提交过来的token
		$token=Request::instance()->header('token');
		$vars=cache($token);
		if (empty($vars)) {
			throw new ApiException(['errorcode'=>'401','msg'=>'Token过期']);
		}
		else{
			if (!is_array($vars)) {
					$vars=json_decode($vars,true);
				}
			if (array_key_exists($key, $vars)) {
				return $vars[$key];
				}
			else{
				throw new Exception("参数:".$key."不存在");
				
			}
		}
	}
	/**
	 * 获取当前用户id
	 * @return [type] [description]
	 */
	public function getCurrentUid()
	{
		$uid=$this->getvar('uid');
		return $uid;
	}
	//todo:
	//用户权限验证
	public function getScope()
	{
		$uid=$this->getCurrentUid();
		$is_ym=Db::name('users')->where('id',$uid)->value('is_ym');
		return $is_ym;
	}
}