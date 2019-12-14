<?php
namespace app\admin\controller;
use app\common\model\Adminusers as UserModel;
use think\Controller;
use think\Validate;
use think\Db;
use think\Session;
use think\Cookie;
use think\Cache;
use think\Log;
class Login extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 登录操作
     */
    public function login()
    {
    	//字段验证
    	$rule = [
            ['captcha','captcha|require','验证码错误|请填写验证码'],
    	    ['loginid','require','请填写账号'],
    	    ['password','require','请填写密码'],
    	];
    	$validate = new Validate($rule);
    	$data = input('post.');
    	$result = $validate->check($data);
    	if(!$result){
    	    $this->error($validate->getError());
    	}
        
    	//账号是否存在
    	$model =new UserModel;
    	$result=$model->check_login(input('loginid'),input('password'));

    	//账号密码是否正确
    	if(false === $result){
    	    // 验证失败 输出错误信息
    	    $this->error($model->getError());
    	}
    	else
    	{
            //验证成功session记录用户id
    		session('admin_id',$result['id']);
            //当前用户对象
            session('admin',$result);
            //session记录用户权限ID数组
            $ids = Db::name('user_role a')->join('role_action b', 'a.roleid=b.roleid')
                ->where('a.userid', $result['id'])->column("b.actionid");
            session('admin_action_ids', array_unique($ids));


            //sseion记录用户权限代码，数组
            $acts = Db::name('user_role a')->join('role_action b', 'a.roleid=b.roleid')
                ->join('action c','b.actionid=c.id')->where('a.userid', $result['id'])->column("c.actcode");
            session('admin_action_codes', array_unique($acts));
            session('logintime',date('Y-m-d H:i:s',time()));
            Db::name('adminusers')->where('id',session('admin_id'))->update(['lastlogin'=>session('logintime')]);
    		//跳转登录
    		$this->redirect(url('admin/index/index'));
    	}
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        //Db::name('adminusers')->where('id',session('admin_id'))->update(['lastlogin'=>session('logintime')]);
        Session::clear();
        Cookie::clear();
        Cache::clear();
        $this->redirect(url('index'));
    }
}
