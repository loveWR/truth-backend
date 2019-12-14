<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;
class Sysconfig extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->assign('sysconfig',cache('sysconfig'));
    }
    /**
     * 显示资源列表
     */
    public function index()
    {

        return $this->fetch();
    }
    public function base()
    {
        return view();
    }
    public function water()
    {
        $path=PUBLIC_PATH.'static/admin/fonts/';
        $handler=opendir($path);
        while (($filename = readdir($handler)) != false) {
            if ($filename != '.'&& $filename != '..') {
                $files[$path.$filename]=$filename;
            }
        }
        $this->assign('files',$files);
        //测试
        //$this->addWater();
        return view();
    }
    public function lang()
    {
        return view();
    }
    public function save($configgroup)
    { 
        $data=input('post.');
        array_shift($data);//删除token
        foreach ($data as $k => $v) {
            $res=Db::name('sysconfig')->where('configname',$k)->where('langid',session('langid'))->find();

            if (!$res) {
                Db::name('sysconfig')->insert(['configvalue'=>$v,'configname'=>$k,'langid'=>session('langid'),'configgroup'=>$configgroup]);
            }else{
                Db::name('sysconfig')->where('configname',$k)->where('langid',session('langid'))->update(['configvalue'=>$v,'langid'=>session('langid'),'configgroup'=>$configgroup]);
            }
            
        }
        clearTokenGUID();//删除session中的token
        cache('sysconfig',null);
       return json(['state'=>'success','message'=>'保存成功']);
    }
    public function preview()
    {
        $path=PUBLIC_PATH.'test/demo.jpg';//test
        $this->addWater($path);
        return '<img src="/public/test/test.jpg" alt="" id="img1">';//test
    }
    public function addWater($imgpath)
    {
        
        $sys=cache('sysconfig');
        if ($sys['water_enable']!=1) {
            return false;
        }
        $dir=dirname($imgpath);
        $previewpath=$dir.'/test.jpg';
        @unlink($previewpath);
        $img=file_get_contents($imgpath);
        file_put_contents($previewpath,$img);

        $img=\think\Image::open($previewpath);
        if ($sys['water_type']) {
            $img->water('.'.$sys['water_file'],$sys['water_position'],$sys['water_alpha'])->save($previewpath);
        }
        else{
            $offset=array($sys['water_offset_x'], $sys['water_offset_y']);
            $img->text($sys['water_text'],$sys['water_font'],floatval($sys['water_text_size']),$sys['water_text_color'],$sys['water_position'],$offset,floatval($sys['water_text_angle']))->save($previewpath);
        }
    }

    public function extra()
    {
       return $this->fetch();
    }

    public function changeLang($langid)
    {
        session('langid',$langid);
        return json(['state'=>'success','message'=>'设置成功']);
    }
    /**
     * 清除runtime/temp下的文件
     * @return [json] [description]
     */
    public function clearRuntime()
    {   
        if (!isRoot()) {
            return false;
        }
        $path=ROOT_PATH.'runtime/temp';
        $res=unlink_dir($path);
        if ($res!=true) {
            return json(['state'=>'success','message'=>'清除失败']);
        }
        return json(['state'=>'success','message'=>'清除成功']);
    }
    public function clearcache()
    {
        if (!isRoot()) {
            return false;
        }
        $res=Cache::clear();
        if ($res!=true) {
            return json(['state'=>'success','message'=>'清除失败']);
        }
        return json(['state'=>'success','message'=>'清除成功']); 
    }
}
