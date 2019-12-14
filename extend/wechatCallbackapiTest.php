<?php  
use think\Db;
class wechatCallbackapiTest
{
    private $postObj;
    private $wxid;
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            header('content-type:text');
            echo $echoStr;
            exit;
        }
    }

    public function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = 'token';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //$postStr = file_get_contents("php://input");
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->postObj=$postObj;
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $this->wxid=$toUsername;
            $type=$postObj->MsgType;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        </xml>";

            switch ($type) 
                {
                    case 'text':
                        {
                           
                            $contentStr = $this->autoMsg($keyword);
                            if (!$contentStr) {
                                //默认回复
                                return ;
                            }
                            $msgType = "text";
                            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                            echo $resultStr;
                        }
                        break;
                    case 'event':
                        {   
                            $event=$postObj->Event;
                            $eventkey=$postObj->EventKey;
                            switch (strtolower($event)) {
                                case 'subscribe':
                                    $contentStr = "欢迎关注天安智慧直通车！";
                                    break;
                                case  'click':
                                    $contentStr=$this->clickEvent($eventkey);
                                    break;
                                default:
                                    $contentStr = "这是哪?我是谁?你要干什么?".$eventkey;
                                    break;
                                        }
                            
                            $msgType = "text";
                            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                            echo $resultStr;
                        }
                        break;
                    case 'image':
                            $resultStr = $this->reImage();
                            echo $resultStr;
                        break;
                   case 'voice':
                       $contentStr=$this->reVoice();
                       echo $contentStr;
                       break;
                    default:
                        {
                            $msgType = "text";
                            $contentStr = json_encode($postObj);
                            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                            echo $resultStr;
                        }
                        break;
                }
            
        }else{
            return ;
            exit;
        }
    }
//todo
    public function clickEvent($eventkey)
    {
        $contentStr='';
        $data=Db::name('wxmenu')->where('key',"{$eventkey}")->where('wxid',"{$this->wxid}")->find();
         if ($data) {
                switch (strtolower($data['type'])) {

                   case 'music':
                       $contentStr=$this->reMusic($data);
                       echo $contentStr;
                       break;
                   case 'news':
                       //$data['picurl']='/1491379684.jpg';
                       $contentStr=$this->reNews($data);
                       echo $contentStr;
                       die();
                       break;
                   case 'voice':
                       $contentStr=$this->reVoice($data['mediaid']);
                       echo $contentStr;
                       break;
                   case 'video':
                       $contentStr=$this->reVideo($data);
                       echo $contentStr;
                       break;
                        break;
                  case 'image':
                      $contentStr=$this->reImage($data['mediaid']);
                      echo $contentStr;
                      break;
                   default:
                        $contentStr=!empty($data['content'])?$data['content']:'设置的回复内容为空';
                       break;
               }
                
             }else{
                $contentStr="这是".$eventkey;
             }
        return $contentStr;
    }
    //todo
    public function autoMsg($value)
    {
        $value=trim($value);
        $contentStr='';
        $data=Db::name('wxremsg')->field(" *,(length(keyword)-length('{$value}')) as rn ")->where('wxid',"{$this->wxid}")->where('keyword','like',"%{$value}%")->order('rn')->find();
        if ($data) {
                switch (strtolower($data['type'])) {

                   case 'music':
                       $contentStr='music';
                       break;
                   case 'vedio':
                       $contentStr='vedio';
                       break;
                   case 'voice':
                       $contentStr=$this->reVoice();
                       echo $contentStr;
                       break;

                  case 'image':
                      $contentStr=$this->reImage($data['mediaid']);
                      echo $contentStr;
                      break;
                   default:
                        $remsg=!empty($data['remsg'])?$data['remsg']:'设置的回复内容为空';
                        $contentStr=$this->reText($remsg);
                        echo $contentStr;
                        die();
                       break;
               }
                
             }else{
                //todo
                //如果设置了默认回复则回复默认消息，否则进入机器人聊天
                //机器人聊天
               $content=$this->robotRe($value);
               echo $this->reText($content);
             }
        return $contentStr;
    }
//todo
    public function reNews($data)
    {
        
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[news]]></MsgType>
                        <ArticleCount>1</ArticleCount>
                        <Articles>

                        <item>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <PicUrl><![CDATA[%s]]></PicUrl>
                        <Url><![CDATA[%s]]></Url>
                        </item>
                        
                        </Articles>
                        </xml>";
            $fromUsername = $this->postObj->FromUserName;
            $toUsername = $this->postObj->ToUserName;
            $articletitle=$data['articletitle']||'articletitle';
            $description=$data['articledescription']||'description';
            $time=time();
            $PicUrl=$this->createRoot().$data['picurl'];
            $Url=$data['articleurl'];
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time,$articletitle,$description,$PicUrl,$Url);
            return $resultStr;

    }
/**
 * [回复图片消息]
 * @param  string $mediaId [如果存在就回复media_id相对应的图片]
 * @return [string]          [xml消息]
 */
    public function reImage($mediaId='')
    {
         
            $text="<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[image]]></MsgType>
                    <Image>
                    <MediaId><![CDATA[%s]]></MediaId>
                    
                    </Image>
                    </xml>";
            $fromUsername = $this->postObj->FromUserName;
            $toUsername = $this->postObj->ToUserName;
            $time=time();
            if (empty($mediaId)) {
                $mediaId=$this->postObj->MediaId;
            }

            $resultStr = sprintf($text, $fromUsername, $toUsername, $time,$mediaId);
        
        return $resultStr;
    }
   //todo 
    public function reMusic($data)
    {
             $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[music]]></MsgType>
                        <Music>
                            <Title><![CDATA[%s]]></Title>
                            <Description><![CDATA[%s]]></Description>
                            <MusicUrl><![CDATA[%s]]></MusicUrl>
                            <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
                            
                        </Music>
                        </xml>";
            $fromUsername = $this->postObj->FromUserName;
            $toUsername = $this->postObj->ToUserName;
            $time=time();
            $title=$data['music']||'music';
            $description=$data['singer']||'singer';
            $MusicUrl=$this->createRoot().$data['musicurl'];
            $HQMusicUrl=$this->createRoot().$data['musicurl'];
            
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time,$title,$description,$MusicUrl,$HQMusicUrl);
            return $resultStr;
    }
    public function reVoice($mediaId)
    {
         $text ="<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[voice]]></MsgType>
                    <Voice>
                    <MediaId><![CDATA[%s]]></MediaId>
                    </Voice>
                    </xml>";
            $fromUsername = $this->postObj->FromUserName;
            $toUsername = $this->postObj->ToUserName;
            $time=time();
            if (empty($mediaId)) {
                $mediaId=$this->postObj->MediaId;
            }
            $resultStr = sprintf($text, $fromUsername, $toUsername, $time,$mediaId);
        return $resultStr;  
    }
    public function reVideo($data)
    {
        $textTpl="<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[video]]></MsgType>
                <Video>
                <MediaId><![CDATA[%s]]></MediaId>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                </Video> 
                </xml>";
            $fromUsername = $this->postObj->FromUserName;
            $toUsername = $this->postObj->ToUserName;
            $time=time();
            if (empty($data['mediaid'])) {
                $mediaId=$this->postObj->MediaId;
            }else if (is_array($data)) {
                $mediaId=$data['mediaid'];
            }else{
                $mediaId=$data;
            }
            $title=$data['videotitle'];
            $description=$data['videodescription'];
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time,$mediaId,$title,$description);
        return $resultStr;    
    }
    //ok
    public function reText($content)
    {
        $keyword = trim($content);
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        </xml>";
            $fromUsername = $this->postObj->FromUserName;
            $toUsername = $this->postObj->ToUserName;
            $time=time();
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time,$content); 
            return $resultStr;   
    }
    public function defaultRes($content)
    {


    }

   public function robotRe($content)
    {
        //定义app
        $app_key="jeHrLww6xtDu";
        $app_secret="QN4G3xPyMZIrSMyGRCJi";

        //签名算法
        $realm = "xiaoi.com";
        $method = "POST";
        $uri = "/robot/ask.do";
        $nonce = "";
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < 40; $i++) {
            $nonce .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        $HA1 = sha1($app_key.":".$realm.":".$app_secret);
        $HA2 = sha1($method.":".$uri);
        $sign = sha1($HA1.":".$nonce.":".$HA2);

        //接口调用
        $url = "http://nlp.xiaoi.com/robot/ask.do";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Auth:    app_key="'.$app_key.'", nonce="'.$nonce.'", signature="'.$sign.'"'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "question=".urlencode($content)."&userId=".$this->postObj->FromUserName."&platform=custom&type=0");
        $output = curl_exec($ch);
        if ($output === FALSE){
            return "cURL Error: ". curl_error($ch);
        }
        return trim($output);
    }

    public function createRoot()
    {
        $http='https://';
        if (stripos($_SERVER['SERVER_PROTOCOL'],'http/')!==false) {
            $http='http://';
        }
        return $url=$http.$_SERVER['HTTP_HOST'].str_ireplace('/index.php', '', $_SERVER['SCRIPT_NAME']);
    }

}


?>