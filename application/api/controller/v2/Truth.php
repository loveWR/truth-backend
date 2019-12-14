<?php 

namespace app\api\controller\v2;
use app\api\controller\ApiBase;
use think\Request;
use think\Db;
use think\Controller;
use app\api\service\Tokenservice;
/**
* 
*/
class Truth extends Controller
{
	
	public function questions()
	{
		$words=Db::name('questions')->where('type=1')->select();
		$crazy=Db::name('questions')->where('type=2')->select();
		$like=Db::name('like')->select();
		$like_type=Db::name('sysconfig')->where(['configname'=>'like_type'])->value('configvalue');
		$app=Db::name('applist')->where('type=2')->select();
		$wordsNum=count($words);
		$crazyNum=count($crazy);
		$likesNum=count($like);
		return json([
			'code'=>1,
			'response'=>[
				'words'=>$words,
				'crazy'=>$crazy,
				'likesNum'=>$likesNum,
				'wordsNum'=>$wordsNum,
				'crazyNum'=>$crazyNum,
				'like_type'=>$like_type,
				'app'=>$app,
				]
		]);
	}
	public function add()
	{
		$data=input('post.');
		Db::startTrans();
		try {
			Db::name('questions')->insert($data);
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		}
		return json(['code'=>1]);
	}

	public function addlike()
	{
		$data=input('post.');
		Db::startTrans();
		try {
			Db::name('like')->insert($data);
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    return json([
				'code'=>0
			]);
		}
		
		return json([
			'code'=>1
		]);
	}

	public function applist()
	{
		$not=input('not');
		$data=Db::name('applist')->where("type=1 and appid != '$not'")->select();
		return json([
			'code'=>1,
			'response'=>[
				'data'=>$data,
				
				]
		]);
	}

	public function adlist()
	{
		$appid=input('appid');
		$data=Db::name('adlist')->where(['appid'=>$appid])->select();
		return json([
			'code'=>1,
			'response'=>[
				'data'=>$data,
				
				]
		]);
	}
}