<?php
namespace app\common\model;
use think\Model;
class Adminusers extends Model
{
    public function check_login($loginid,$password)
    {
    	if(!($user=$this->where('loginid',$loginid)->find()))
    	{
    		$this->error='用户名不存在';
    		return false;
    	}
    	else
    	{
    		if($user['password']!=md5($password)) //md5($password)
    		{
    			$this->error='密码错误';
    			return false;
    		}
    		else
    		{
    			return $user;
    		}
    	}

    }

}