<?php
namespace app\admin\controller;
use \PDO;
use think\image\Exception;
use think\Log;
use \think\Request;
use think\Image;
use think\Db;
class Demo extends Base
{
    public function index()
    {
return $this->fetch();
    }

    public  function test()
    {

        $a='1,3,2,5,6a,"delete adminusers" ,';
        dd(idsToInt($a));

    }


//todo:实现代码录入
  public function getTypeahead(){
  	    $name=input('keyword');
    	$data=Db::name('code')->field('codename,id')->where('codename','like','%'.$name.'%')->whereOr('id','like','%'.$name.'%')->select();
    	$json=$data;
        return json($json);
    }
}
