<?php 

namespace app\api\controller\v1;
use app\api\controller\ApiBase;
use think\Request;
use think\Db;
use app\api\service\Tokenservice;
/**
* 
*/
class My extends ApiBase
{
	
	public function getUsers()
	{
		//$users=Db::name('users')->field('id,realname,nickname,pic')->where('is_ym',1)->select();
		$users=Db::query("select id,if(realname<>'',realname,nickname) realname,pic from users where is_ym='1' ");
		return $users;
	}
	public function index()
	{
		$uid=$this->getCurrentUid();
		$userinfo=Db::name('users')->where('id',$uid)->find();
		$worklogs=Db::name('worklogs')->where('userid',$userinfo['id'])->order('id desc')->select();
		foreach ($worklogs as $k => &$v) {
			$v['pname']=Db::name('project')->where('id',$v['pid'])->value('project_name');
			if (empty($v['pname'])) {
				$v['pname']='';
			}
		}
		return json(['userinfo'=>$userinfo,'worklogs'=>$worklogs]);
	}

	public function saveproject()
	{
		$data=input('post.');
		$data['creator_id']=session('uid');
		$data['mid']=session('uid');
		$data['add_time']=date('Y-m-d');
		if (empty($data['plan_time'])) {
			$data['plan_time']=date('Y-m-d');
		}
		Db::name('project')->insert($data);
		return json(['state'=>'success','msg'=>'新增成功']);
	}
	public function endedproject($pid)
	{
		$uid=session('uid');
		$res=Db::name('project')->where(" id=$pid  ")->where("(creator_id='$uid' or mid='$uid')")->find();
		if ($res['is_end']==1) {
			return json(['state'=>'error','msg'=>'项目已结案']);
		}
		if ($res) {
			//Db::name('project')->where('creator_id',$uid)->where('id',$pid)->update(['is_end'=>1]);
			return json(['state'=>'success','msg'=>'成功']);
		}else{
			return json(['state'=>'error','msg'=>'非项目创建人员无权修改']);
		}
		
	}
	public function editProject($pid)
	{
		$data=Db::name('project a')->join('users b','a.mid=b.id')->field('a.*,b.realname')->where('a.id',$pid)->find();
		$users=$this->getUsers();
		return json(['users'=>$users,'data'=>$data]);
	}
	public function updateProject($pid)
	{
		$data=input('post.');
		Db::name('project')->where('id',$pid)->update($data);
		return json(['state'=>'success','msg'=>'成功']);
	}
}