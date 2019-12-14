<?php
namespace app\admin\controller;

use think\Db;
use think\Image;
use think\Cache;
use think\image\Exception;
use think\Log;
class Upload extends Base
{
    public function index()
    {  
        //todo: 此处需要实现获取dir_id 图片空间目录 ID
        $dir_id=input('dir_id');
        $maxSize=input('maxSize');
        $config['maxFiles'] = 300;
        $config['savePath'] = input('savePath');
        $config['autoDir'] = input('autoDir');
        $config['fileTypes'] = input('fileTypes');
        $config['maxSize'] =$maxSize=='0'?0: fileSizeToByte( $maxSize); //单个文件最大字节值。0表示不限
        $config['guid'] = getGUID();
        $sql = "select *   from att_dir order by pid,id  ";
        $data = Db::query($sql);
        $this->assign('zNodes', json_encode($data, JSON_UNESCAPED_UNICODE)); //中文原样输出
        $this->assign('config', $config);
        return view('index');
    }
    public function getpic($id)
    {
        //dump(input('get.'));
        $k=addslashes(input('keywords'));
        $page=input('page');
        $w=" dir_id=$id ";
        if ($k!=='') {
            $w.=" oldname like '%$k%' ";
        }
        $pics=Db::name('attfile')->where($w)->paginate(60,false);
        $totalpage=ceil($pics->total()/60);  
        $page=$pics->render();
        return json(['pics'=>$pics,'totalpage'=>$totalpage]);
    }
    /**
     *
     *接收上传的文件， 每个文件请求一次，如果开启了分块上传，则每个文件请求多次，所有分片上传成功后进行文件合并
     *"id">随机唯一
     * "guid">formdata上传的唯一号
     * "name">原始文件名
     * "type">文件类型MIME
     * "lastModifiedDate">文件最后修改时间
     * "size">bigint 文件大小
     * "file">文件对象
     * "savepath">上传的文件保存的目录 名， 目录 名基于/upload 为开始
     * "autoDir">是否自动创建yy\mm\dd的目录
     * "chunk">当前分片号，从0开始，如果前端未采用分片模式，则chunk和chunks两个参数不会提供
     * "chunks">总分片数
     */
    public function saveFile()
    {
        $dir_id=input('dir_id');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }
        
        @set_time_limit(0);//最大的执行时间，单位为秒。如果设置为0（零），没有时间方面的限制。

        $savepath = trim(input('savePath'), '/');
        $savepath = $savepath == "" ? "files" : $savepath;
        $autoDir = input('autoDir') == 'true';
        $frontId = input('id');
        $guid = input('guid');

        $tmpDir = 'upload/tmp/';
        $uploadDir = 'upload/' . $savepath . '/';
        if ($autoDir) {
            $uploadDir = $uploadDir . date("Y") . "/" . date("m") . '/' . date("d") . '/';
        }
        if (!$dir_id) {
            $yearId=Db::name('att_dir')->where('dir_name',date('Y年'))->value('id');
            $monthId=Db::name('att_dir')->where('dir_name',date('m月'))->value('id');
            $dayId=Db::name('att_dir')->where('dir_name',date('d日'))->value('id');
            //dd($dayId);
            if (!$yearId) {
                $yearId=Db::name('att_dir')->insertGetId(['dir_name'=>date('Y年')]);
            }
            if (!$monthId) {
                $monthId=Db::name('att_dir')->insertGetId(['dir_name'=>date('m月'),'pid'=>$yearId]);
            }
            if (!$dayId) {
                $dayId=Db::name('att_dir')->insertGetId(['dir_name'=>date('d日'),'pid'=>$monthId]);
            }
            $dir_id=$dayId;
        }
		
        $cleanupTargetDir = false; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds
        // Cre c ate target dir
        if (!file_exists($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        // Create target dir
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        // Get a file name
        if (isset($_REQUEST["name"])) {
            $oldname = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $oldname = $_FILES["file"]["name"];
        } else {
            $oldname = uniqid("file_");
        }

        $suffix = substr($oldname, strrpos($oldname, '.'));
        $random = time() . createNonceStr(4);
        $filename = $random . $suffix;
        $chunkPath = $tmpDir . $guid . '_' . $frontId;  //此处不能使用$oldname 如果$oldname有中文有时会出错
        $uploadPath = $uploadDir . $filename;
        // Chunking might be enabled
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
        // Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($tmpDir) || !$dir = opendir($tmpDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $tmpDir . "/" . $file;
                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$chunkPath}_{$chunk}.part" || $tmpfilePath == "{$chunkPath}_{$chunk}.parttmp") {
                    continue;
                }
                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }

        // Open temp file
        if (!$out = @fopen("{$chunkPath}_{$chunk}.parttmp", "wb")) {
            return json(['id' => $frontId, 'errorcode' => 102, 'message' => 'Failed to open temp output stream.']);
        }
        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                return json(['id' => $frontId, 'errorcode' => 103, 'message' => 'Failed to move uploaded file.']);
            }
            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                return json(['id' => $frontId, 'errorcode' => 101, 'message' => 'Failed to open input stream.']);
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                return json(['id' => $frontId, 'errorcode' => 101, 'message' => 'Failed to open input stream.']);
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename("{$chunkPath}_{$chunk}.parttmp", "{$chunkPath}_{$chunk}.part");

        $done = true;
        for ($index = $chunks - 1; $index >= 0; $index--) {
            if (!file_exists("{$chunkPath}_{$index}.part")) {
                $done = false;
                break;
            }
        }
        if ($done) {
            if (!$out = @fopen($uploadPath, "wb")) {
                return json(['id' => $frontId, 'errorcode' => 102, 'message' => 'Failed to open output stream for Merge.']);
            }
            $merge = true;
            //堵塞式排他锁文件
            if (flock($out, LOCK_EX)) {
                for ($index = 0; $index < $chunks; $index++) {
                    if (!$in = @fopen("{$chunkPath}_{$index}.part", "rb")) {
                        //只要有一个分片不存在，就认为被其它进程合并了所有分片，
                        $merge = false; //标记为合并不成功
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink("{$chunkPath}_{$index}.part");
                }
                flock($out, LOCK_UN);
            }
            @fclose($out);
            //合并成功，记录附件信息。
            if ($merge) {
                //缩略图生成
                if (substr($_FILES["file"]["type"],0,5) == 'image') {
                    $image = Image::open($uploadPath);
                    $smallimgurl = $uploadDir . $random . "_s" . $suffix;
                    $image->thumb(200, 200, Image::THUMB_FILLED)->save($smallimgurl);
                    $data['smallimgurl'] ='/'. $smallimgurl;
                }
                $totalsize=filesize($uploadPath); //取得文件大小信息
                // Return Success JSON-RPC response
                $data['dir_id'] = $dir_id;//？ todo:文件空间中使用
                $data['oldname'] = $oldname;
                $data['fileurl'] ='/'.$uploadPath;
                $data['adminuser_id'] = session('admin_id');
                $data['uploadtime'] = date("Y-m-d H:i:s", time());
                $data['filesize'] = fileSizeToUnit($totalsize);//需要转换函数
                $data['sizebyte'] = $totalsize;
                $data['atttitle'] = $oldname;
                $data['attdesc'] = '';
           
                $data['joinguid'] = '';//？暂时不用。由业务模块去更新。
                $data['token'] = getGUID();
                //自动添加水印
                if (input('water')=="true" && cache('sysconfig')['water_enable']==1) {

                    $this->addWater('.'.$data['fileurl']);
                }
                $id = Db::name('attfile')->insertGetId($data);
                $data['id'] = $id;
                return json(['id' => $frontId, 'attfile' => $data]);
            }
        }

        return json(['id' => $frontId, 'chunk' => $chunk]);
    }


    /**
     * 图片空间替换
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function replaceFile($oldid)
    {
        $olddata=Db::name('attfile')->where('id',$oldid)->find();
        $oldfile=$olddata['fileurl'];
        $file=$olddata['fileurl'];
        $filepath=substr($file,0,strrpos($file,'/')+1);
        $filename=substr($file,strrpos($file,'/')+1);
        $filename=substr($filename,0,strrpos($filename,'.'));
		//dd($smallimgurl);
        @unlink('.'.$oldfile);
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }
        @set_time_limit(0);//最大的执行时间，单位为秒。如果设置为0（零），没有时间方面的限制。
        $frontId = input('id');
        $guid = input('guid');

        $tmpDir = 'upload/tmp/';
        $uploadDir = '.'.$filepath;
        
        $cleanupTargetDir = false; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds
        // Cre c ate target dir
        if (!file_exists($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        // Create target dir
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $oldname = $olddata['oldname'];
        $suffix = substr($oldname, strrpos($oldname, '.'));
		$smallimgurl = $filename . "_s" . $suffix;
        $filename = $filename.$suffix;
        $chunkPath = $tmpDir . $guid . '_' . $frontId;  //此处不能使用$oldname 如果$oldname有中文有时会出错
        $uploadPath = $uploadDir . $filename;
        // Chunking might be enabled
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
        // Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($tmpDir) || !$dir = opendir($tmpDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $tmpDir . "/" . $file;
                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$chunkPath}_{$chunk}.part" || $tmpfilePath == "{$chunkPath}_{$chunk}.parttmp") {
                    continue;
                }
                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }

        // Open temp file
        if (!$out = @fopen("{$chunkPath}_{$chunk}.parttmp", "wb")) {
            return json(['id' => $frontId, 'errorcode' => 102, 'message' => 'Failed to open temp output stream.']);
        }
        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                return json(['id' => $frontId, 'errorcode' => 103, 'message' => 'Failed to move uploaded file.']);
            }
            // Read binary input stream and append it to temp file
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                return json(['id' => $frontId, 'errorcode' => 101, 'message' => 'Failed to open input stream.']);
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                return json(['id' => $frontId, 'errorcode' => 101, 'message' => 'Failed to open input stream.']);
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename("{$chunkPath}_{$chunk}.parttmp", "{$chunkPath}_{$chunk}.part");

        $done = true;
        for ($index = $chunks - 1; $index >= 0; $index--) {
            if (!file_exists("{$chunkPath}_{$index}.part")) {
                $done = false;
                break;
            }
        }
        if ($done) {
            if (!$out = @fopen($uploadPath, "wb")) {
                return json(['id' => $frontId, 'errorcode' => 102, 'message' => 'Failed to open output stream for Merge.']);
            }
            $merge = true;
            //堵塞式排他锁文件
            if (flock($out, LOCK_EX)) {
                for ($index = 0; $index < $chunks; $index++) {
                    if (!$in = @fopen("{$chunkPath}_{$index}.part", "rb")) {
                        //只要有一个分片不存在，就认为被其它进程合并了所有分片，
                        $merge = false; //标记为合并不成功
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink("{$chunkPath}_{$index}.part");
                }
                flock($out, LOCK_UN);
            }
            @fclose($out);
            //合并成功，记录附件信息。
            if ($merge) {
                //缩略图生成
                if (substr($_FILES["file"]["type"],0,5) == 'image') {
                    $image = Image::open($uploadPath);
                    $image->thumb(200, 200, Image::THUMB_FILLED)->save($smallimgurl);
                    $data['smallimgurl'] =$smallimgurl;
                }
                $totalsize=filesize($uploadPath); //取得文件大小信息
                // Return Success JSON-RPC response
                $data['adminuser_id'] = session('admin_id');
                $data['uploadtime'] = date("Y-m-d H:i:s", time());
                $data['filesize'] = fileSizeToUnit($totalsize);//需要转换函数
                $data['sizebyte'] = $totalsize;
                $data['token'] = getGUID();
                //自动添加水印
                if (input('water')=="true" && cache('sysconfig')['water_enable']==1) {

                    $this->addWater('.'.$olddata['fileurl']);
                }
                Db::name('attfile')->where('id',$oldid)->update($data);
                $data['id'] = $id;
                return json(['id' => $frontId, 'attfile' => $data]);
            }
        }

        return json(['id' => $frontId, 'chunk' => $chunk]);
    }
    public function killVirus()
    {
        $ids = "";//todo:实现杀毒过程
        return json(['state' => 'success', 'message' => '扫描病毒完成', 'data' => '']);
    }

//在图片上增加水印。 
   public function addWater($imgpath)
    {
        $img=\think\Image::open($imgpath);
        $sys=cache('sysconfig');
        if ($sys['water_type']) {
            $img->water('.'.$sys['water_file'],$sys['water_position'],$sys['water_alpha'])->save($imgpath);
        }
        else{

            $offset=array($sys['water_offset_x'], $sys['water_offset_y']);
            if (stripos($sys['water_text_color'], '#')!==0) {
                $sys['water_text_color']='#'.$sys['water_text_color'];
            }
            $img->text($sys['water_text'],$sys['water_font'],floatval($sys['water_text_size']),$sys['water_text_color'],$sys['water_position'],$offset,floatval($sys['water_text_angle']))->save($imgpath);

        }
    }

}