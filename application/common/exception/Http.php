<?php
namespace app\common\exception;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\Request;
use think\Log;
use think\Db;
use app\api\exception\BaseException;

class Http extends Handle
{

    private $code;
    private $msg;
    private $errorcode;
    /**
     * 统一异常处理
     * 1、根据异常中的内容，判断是否唯一索引冲突，如果唯一索引错误，则从数据库取出相应的提示返回到前台，否则原样显示错误信息
     * 2、自定义api异常抛错类   
     *     必须继承 app\api\exception\BaseException; 
     *     例：throw new TokenException([
     *                                 'code'=>'500',//页面http错误码
     *                                 'msg'=>'未知错误',//错误信息
     *                                 'errcode'=>'999']//自定义错误码
     *                                 );
     *      
     * 3、记录错误日志，前提是config文件关闭调试模式
     * 4、常规抛错
     * @param  Exception $e [description]
     * @return [type]       [description]
     */
    public function render(Exception $e)
    {
        //api抛异常处理 json格式
        $request=Request::instance();
        if ($e instanceof BaseException) {
            $this->code=$e->code;
            $this->msg=$e->msg;
            $this->errorcode=$e->errorcode;
            
            $result=[
                'msg'=>$this->msg,
                'errorcode'=>$this->errorcode,
                
                ];
            return json($result,$this->errorcode);
        }
        else{
            if (config('app_debug'))
            {
                return parent::render($e);
            }
            if (!config('app_debug')&&$isajax) {
                //发生脚本错误，记录日志
                $this->recordLog($e);
                return json(['state' => 'error', 'message' =>'服务器错误','errorcode'=>'999']);
            }
        }
        // 客户端用户抛错 json 格式 
        // 部署上线关闭debug开关
        // 小程序的请求必须设置头信息 isajax=1 才能被拦截
        
        
        //服务端开发调试抛错 
        $msg = $e->getMessage();
        preg_match('/Integrity constraint violation[\s\S]*?for key \'(.*?)\'/', $msg, $match);
        if ($match) {
            $uniname = $match[1];
            $dbn = config('database')['database'];
            $sql = "select e.msg from information_schema.TABLE_CONSTRAINTS c LEFT JOIN unique_error e on  c.CONSTRAINT_NAME=e.uniname  where table_SCHEMA='{$dbn}' and CONSTRAINT_TYPE='UNIQUE' and c.CONSTRAINT_NAME='{$uniname}'";
            $rst = Db::Query($sql);
            if ($rst[0]['msg']) {
                $msg = $rst[0]['msg'];
            }
        } 
        
        
        //ajax请求 异常处理
        if (Request::instance()->isAjax()) {
           return json(['state' => 'error', 'message' => $msg]);
        } 

        //非ajax请求 异常处理
        if ($match) {
            $e = new Exception($msg);
        }
        return parent::render($e);

    }

    private  function recordLog(Exception $e)
    {
        $request=Request::instance();
        Log::init([
            'type' => 'File',
            // 日志保存目录
            'path' => DEBUG_LOG_PATH,
            // 日志记录级别
            'level' => ['error'],
            ]);
        Log::record($e->getMessage()."\n [url] ".$request->url(),'error');
    }

}