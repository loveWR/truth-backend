<?php  
namespace app\api\service;
use think\Exception;
use app\api\service\Tokenservice;
use app\api\exception\ApiException;
use think\Request;
use think\Controller;
use think\Db;
/**
* 
*/
class Tokenservice 
{
	protected $app_id;
	protected $app_secret;
	protected $token_url;
	public function linkW()
	{
		return $link=Db::connect(config('ww_config'));
	}
	public function __construct($code)
	{
		$wxconfig=config('wxsetting');
		$this->app_id=$wxconfig['app_id'];
		$this->app_secret=$wxconfig['app_secret'];
		$this->token_url=sprintf($wxconfig['token_url'],$wxconfig['app_id'],$wxconfig['app_secret'],$code);
	}
	/**
	 * 客户端获取令牌
	 * @return [type] [description]
	 */
	public function get()
	{
		$res=curl($this->token_url);
		$res=json_decode($res,true);
		cache('openid',$res['openid']);
		if (empty($res)) {
			throw new Exception("获取openid错误或者微信内部错误");
		}
		else if (isset($res['errcode'])) {
			throw new Exception($res['errmsg']);

		}
		else{
			return $this->grantToken($res);
		}

	}
	/**
	 * 分配用户令牌
	 * @param  [type] $res [description]
	 * @return [type]      [description]
	 */
	public function grantToken($res)
	{
		
		$user=Db::name('users')->where('openid',$res['openid'])->find();
		if (!$user) {
			$data=$this->insertUser($res);
			$uid=$data['uid'];
			

		}else{
			$uid=$user['id'];
			$this->updateUser($uid);
		}
		$token=createToken();
		$res['uid']=$uid;
		cache($token,json_encode($res),$res['expires_in']);
		return $token;
	}

	//新用户登陆时记录新用户openid
	public function insertUser($res)
	{
		$userinfo=json_decode(htmlspecialchars_decode(input('userinfo')),true);
		$data['openid']=$res['openid'];
		$data['nickname']=$userinfo['nickName'];
		$data['pic']=$userinfo['avatarUrl'];
		$data['createtime']=date('Y-m-d H:i:s');
		// $data['scope']=10;
		$uid=Db::name('users')->insertGetId($data);
		$data['uid']=$uid;
		return $data;
	}

	public function updateUser($uid)
	{
		$userinfo=json_decode(htmlspecialchars_decode(input('userinfo')),true);
		$data['nickname']=$userinfo['nickName'];
		$data['pic']=$userinfo['avatarUrl'];
		Db::name('users')->where('id',$uid)->update($data);
	}
}