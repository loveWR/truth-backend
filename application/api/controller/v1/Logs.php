<?php  
namespace app\api\controller\v1;

use app\api\controller\ApiBase;
use think\Request;
use think\Db;
use app\api\service\Tokenservice as TS;
/**
* 
*/
class Logs extends ApiBase
{
	// public function index()
	// {
	// 	$w=' where 1=1 ';
	// 	$sql='select a.*,b.pic,b.nickname,b.realname,if(c.project_name="","",c.project_name) project_name from worklogs a left join users b on b.id= a.userid inner join project c on a.pid=c.id ';
	// 	if (empty(input('param.'))) {
	// 		$w=' WHERE workdate >  date_sub(NOW(), interval 3 day) ';//筛选3天内的信息
	// 	}
	// 	if (input('userid')) {
	// 		$w.=' and a.userid='.input('userid');
	// 	}
	// 	if (input('date')) {
	// 		$w.=" and  date_format(workdate,'%Y-%m-%d') ='".input('date')."'";
	// 	}
	// 	$order=' order by a.id desc';
	// 	$list=Db::query($sql.$w.$order);
	// 	$users=Db::name('users')->field('id,pic,nickname,realname')->select();
	// 	$yestorday=date("Y-m-d",strtotime("-1 day"));
	// 	return json(
	// 		[
	// 		'list'=>$list,
	// 		'users'=>$users,
	// 		]
	// 	);

	// }
	public function day_logs()
	{
		
		$w=' where pid=0 ';
		$sql='select a.*,b.pic,b.nickname,b.realname from worklogs a left join users b on b.id= a.userid left join project c on a.pid=c.id ';
		$day=input('day');
		if (empty($day)) {
			$day=date('Y-m-d');
		}
		$w.=" and workdate like '%$day%' ";
		if (input('date')) {
			$w.=" and  date_format(workdate,'%Y-%m-%d') ='".input('date')."'";
		}
		$order=' order by a.id desc';
		$list=Db::query($sql.$w.$order);
		$users=Db::name('users')->field('id,pic,nickname,realname')->where('is_ym',1)->select();
		$day_list=[date('m月d日',strtotime("-2 day"))=>date('Y-m-d',strtotime("-2 day")),date('m月d日',strtotime("-1 day"))=>date('Y-m-d',strtotime("-1 day")),date('m月d日')=>date('Y-m-d')];
		foreach ($list as $k => &$v) {
			$v['pname']=Db::name('project')->where('id',$v['pid'])->value('project_name');
			if (empty($v['pname'])) {
				$v['pname']='';
			}
		}
		return json(
			[
			'day_list'=>$day_list,
			'today'=>date('Y-m-d'),
			'list'=>$list,
			'users'=>$users,
			]
		);

	}
	public function detail()
	{
		$id=input('id');
		$detail=Db::name('worklogs a')->join('users b','a.userid=b.id')->join('project c','a.pid=c.id','left')->field('a.*,b.pic,b.nickname,b.realname,IFNULL(c.project_name,"") project_name')->where('a.id',$id)->find();

		return json(['detail'=>$detail]);
	}
	public function save()
	{
		$data=input('post.');
		if (input('pid')) {
			$data['pid']=input('pid');
		}else{
			$data['pid']=0;
		}
		
		if (empty($data['plan'])) {
			return json(['errorcode'=>'工作内容不能为空','state'=>'error']);
		}
		if ($data['pid']&&empty($data['problems'])) {
			return json(['errorcode'=>'工作问题不能为空','state'=>'error']);
		}
		if ($data['pid']&&empty($data['proceeding'])) {
			return json(['errorcode'=>'工作进度不能为空','state'=>'error']);
		}
		$data['finishrange']=$data['proceeding'];
		$data['userid']=session('uid');
		$data['workdate']=$data['date']?$data['date']:date('Y-m-d');
		$data['brief']=mb_substr($data['plan'], 0,10,'utf8');

		$date=$data['workdate'];
		$today=date('Y-n-d');
		$clock=date('H');

		//return json(['state'=>'error','errorcode'=>'下午六点前不能提交当天日志'.$today.$date]);
		if (($clock<17) && ($date==$today)&&($data['pid']==0)) {
			return json(['state'=>'error','errorcode'=>'下午五点前不能提交当天日志']);
		}
		$todaylog=Db::name('worklogs')->where('userid',$data['userid'])->where("workdate = '$date'")->where('pid',$data['pid'])->find();
		if ($todaylog) {
			return json(['state'=>'success','msg'=>'新增成功']);
		}
		Db::name('worklogs')->insert($data);
		return json(['state'=>'success','msg'=>'新增成功']);
	}
	//项目列表
	public function project_list()
	{
		$w=' where 1=1 ';
		$list=Db::query("select a.*,(select realname from users where id =a.mid) realname from project a inner join users b on a.creator_id=b.id order by id desc");
		$day=input('day');
		if (empty($day)) {
			$day=date('Y-m-d');
		}
		foreach ($list as $k => &$v) {

            // $totalrange=0;
            // $pid=$v['id'];
            // $users=Db::name('users a')->join('worklogs b','a.id=b.userid')->where('b.pid',$pid)->where('a.is_ym',1)->column('a.id');

            // $users=array_unique($users);

            // $users_num=count($users);
            // foreach ($users as $k1 => $v1) {
            //     //选出每人该项目最新进度总和
            //     $sql="select * from worklogs a where a.pid =$pid and (a.workdate <= '$day' or a.workdate like '$day%' ) and a.userid=$v1 order by a.finishrange desc limit 1 ";

            //     $res=Db::query($sql);
            //     $totalrange+=$res[0]['finishrange'];

            // }
            // if ($totalrange && $users_num) {
            // 	$range=bcdiv( $totalrange, $users_num,2);
            // 	$v['range']=$range;
            // }
            // 项目最新日志
            $clog=Db::name('worklogs a')->field('a.workdate,b.realname,b.nickname')->join('users b','a.userid=b.id')->join('project c','c.id=a.pid')->where('pid',$v['id'])->where('b.is_ym',1)->order('a.id desc')->find();
  
            $v['cname']=$clog['realname']?$clog['realname']:$clog['nickname'];
            $v['cdate']=$clog['workdate'];
            
        }

		return json(
			[
			'list'=>$list,
			]
		);
	}
	// 指定项目下的日志
	public function project_logs()
	{
		$pid=input('pid');
		$list=Db::query("select a.brief,a.workdate,a.userid,a.id,b.pic,b.realname,a.finishrange from worklogs a inner join users b on a.userid=b.id where pid=$pid  order by a.id desc");

		$is_end=Db::name('project')->where('id',$pid)->value('is_end');
		return json(
			[
			'list'=>$list,
			'is_end'=>$is_end,
			]
		);
	}

	public function array_empty(array $arr)
	{
		foreach ($arr as $k => $v) {
			if (is_array($v)) {
				$this->array_empty($v);
			}
			if (!empty($v)) {
				return false;
			}
		}
		return true;
	}
}

