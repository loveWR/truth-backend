<?php
namespace app\admin\controller;
use \think\Db;
use think\Controller;
use think\Request;
class Task extends Controller
{
	
	public function checkSafe($pwd,$issafe)
	{
		//口令验证
		$adminpwd=Db::name('adminusers')->where('id',1)->value('password');
		//windows脚本验证
		//合法口令例子：php -f  C:\ws\TP5MySQL\src\public\index.php admin/task/main 123
		if (ARGV!='ARGV') {
			
			if (ARGV!=='123') {
				$this->redirect(url('admin/index/index'));
				exit();
			}
			
		}else{
		//后台、linux下curl 口令验证
		//linux 合法口令 ：curl 'http://ta.szyimiao.com/src/public/admin/Task/main.html?pwd=123&issafe=1' 
		//浏览器合法口令：http://ta.szyimiao.com/src/public/admin/Task/main.html?pwd=123&issafe=1
		//http://localhost/admin/task/main.html?pwd=123&issafe=1
			if (!$issafe) {
				return json(['state'=>'error','message'=>'任务执行失败']);
				exit();
			}
			if ($pwd!=$adminpwd) {
				return json(['state'=>'error','message'=>'任务执行失败']);
				exit();
			}
		}
		return true;
	}
	public function main($pwd='0',$issafe=false)
	{
		//前提：开启定时任务要先开启服务器sleep函数
		//口令验证
		@ini_set('disable_functions',' ');
		$res=$this->checkSafe($pwd,$issafe);
		
		if ($res!==true) {
			return $res;
			exit();
		}
		$data=Db::name('tasklist')->select();
		$getpath=APP_PATH.'task/Simple.html';
		$putpath=APP_PATH.'task/controller/';
		$content=file_get_contents($getpath);
		
		foreach ($data as $k => $v) {

			$url=substr($_SERVER['HTTP_REFERER'], 0,stripos($_SERVER['HTTP_REFERER'], 'admin')).'task/task'.$v['id'].'/index';	
			if ($_SERVER['HTTP_REFERER']=='') {
				if ($_SERVER['HTTP_HOST']!='') {
					$url=$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'/task/task'.$v['id'].'/index';
				}else{
					
					$url='http://localhost/task/Task'.$v['id'].'/index';//debug,根据项目地址而定
				}
			}		
			
			
			if ($v['isrun']==1) {
				file_put_contents(PUBLIC_PATH.'task'.$v['id'].'.txt', ' ');
				$this->putcontent($putpath,$content,$v);
				_sock($url);
			}else{
				@unlink(PUBLIC_PATH.'task'.$v['id'].'.txt');
				@unlink($putpath.'Task'.$v['id'].'.php');
				Db::name('tasklist')->where('id',$v['id'])->update(['isrunning'=>0]);
			}
			
			 
			
		}

	}
	/**
	 * 生成任务控制器
	 * @param  [type] $path    [控制器路径]
	 * @param  [type] $content [内容]
	 * @param  [type] $data    [任务参数]
	 * 
	 */
  	public function putcontent($path,$content,$data)
  	{
  		$id=$data['id'];
  		if (is_file($path.'Task'.$id.'.php')) {
  			@unlink($path.'Task'.$id.'.php');
  		}
  		$content=str_replace('{name}',$id,$content);
  		$content=str_replace('{task}','$this->task'.$data['taskid'].'()',$content);
  		$content=str_replace('{id}','\''.$id.'\'',$content);
  		file_put_contents($path.'Task'.$id.'.php',$content);
  		
  	}

	 public function task1()
  	{
  		exec('cd '.ROOT_PATH.' & php think clear --path '.ROOT_PATH.'runtime/temp/ ',$output,$state);
  		if ($state) {
  			      	return false;
  			      }	      
  		return true;
  	}  
  	public function task2()
  	{
  			Db::query('delete from tasklog');
	       return true;
  	}  	
  	public function task3()
  	{
  		  // $data=Db::name('test')->select();
  		  // $data1=Db::name('test1')->select();
  		  // if (count($data)!=count($data1)) {
  		  // 	Db::query('delete from test1');
  		  // 	foreach ($data as $key => $value) {
  		  // 		Db::name('test1')->insert($value);
  		  // 	}
  		  // }
	       return true;
  	} 


}