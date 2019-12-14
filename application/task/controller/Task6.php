<?php
namespace app\task\controller;
use \think\Db;
use think\Controller;
use app\admin\controller\Task;
class Task6 extends Task
{
	public function index()
	  {
	    ini_set('disable_functions',' ');
	  	$id='6';
	  	$data=Db::name('tasklist')->where('id',$id)->find();
	  	$log='';
	  	$state=true;
	  	$interval=$data['intervaltimes'];//每$interval毫秒执行一次
	    ignore_user_abort(true);
	    set_time_limit(0);
	    date_default_timezone_set('PRC'); // 切换到中国的时间
	    // 定时任务第一次执行的时间
	    $start_time =strtotime($data['start_time']); 
	    if($data['isrunning']==1) exit(); // 如果isrunning，说明已经在执行过程中了，该任务就不能再激活
	    $isinfinite=empty($data['times'])?true:$data['times'];
	    do {
	    
	      if($isinfinite!==true) {$isinfinite--;};
	      
	      if($isinfinite < 1){ $isinfinite=false;};
	      if (!$state) continue;//标志位，检查上次是否完成，未完成则此次不进行
	      if(!file_exists(PUBLIC_PATH.'task'.$data['id'].'.txt')) break; // 如果不存在这个文件，就停止执行，这是一个开关的作用
	      $now_time = microtime(true); // 当前的运行时间，精确到0.0001秒
	      $loop =  isset($loop) && $loop ? $loop :$start_time - $now_time; // 这里处理是为了确定还要等多久才开始第一次执行任务，$loop就是要等多久才执行的时间间隔
	      //开始时间是过去时，任务停止 $loop = $loop > 0 ? $loop : 0;
	      $loop = $loop > 0 ? $loop : 0;//debug 
	      $end=strtotime($data['end_time'])?strtotime($data['end_time']) - $now_time:true;
	      $end = $end > 0 ? $end : 0;
	      if(!$end) break ;//到结束时间，任务停止
	      if(!$loop) break; // 如果循环的间隔为零，则停止
	      sleep($loop); 
	      $state=false;
	      	
	      $state=$this->task1();
	    
	      $log['task']=$data['id'];
	      $log['runningtime']=date('Y-m-d H:i:s',time());
	      Db::name('tasklog')->insert($log);
	      if(file_exists(PUBLIC_PATH.'task'.$data['id'].'.txt')){
	      Db::name('tasklist')->where('id',$data['id'])->update(['isrunning'=>1]);} // 这里就是告诉程序，这个定时任务已经在执行过程中，不能再执行一个新的同样的任务
	      $loop = $interval;
	    } while($isinfinite!=false);

	    //任务结束，重置数据，关闭任务
	    	 $putpath=APP_PATH.'task/controller/';
	    	 @unlink(PUBLIC_PATH.'task'.$data['id'].'.txt');
			 @unlink($putpath.'Task'.$data['id'].'.php');
			 Db::name('tasklist')->where('id',$data['id'])->update(['isrunning'=>0]);
			 Db::name('tasklist')->where('id',$data['id'])->update(['isrun'=>0]);
	    
	  }


}