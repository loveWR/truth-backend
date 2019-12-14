<?php 
namespace app\wechat\controller;
use think\Controller;
use think\Db;
/**
* 
*/
class Index extends Controller
{
    public function index()
    {
        $wechatObj = new \wechatCallbackapiTest();
        if (isset($_GET['echostr'])) {
            $wechatObj->valid();
        }else{
            $wechatObj->responseMsg();
        }  
    }
  
    
}


?>