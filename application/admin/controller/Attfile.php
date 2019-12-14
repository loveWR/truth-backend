<?php
namespace app\admin\controller;

use think\Db;
use think\Image;
use think\Exception;
use think\Log;
class Attfile extends Base
{
    /**给定ID清单， 按附件顺序返回附件JSON
     * @param $ids  逗号相隔的附件ID，如1,2,3,5
     * @return \think\response\Json
     */
    public function getList($ids){
        $data=Db::name('attfile')->whereIn('id',$ids)->order('sortno asc ,id asc ')->select();
        return json($data);
    }

    public function updateOrder($ids){
        $ar=explode(',',trim( $ids,','));
        foreach ($ar as $k => $v) {
            Db::execute("update attfile set sortno=:sortno where id=:id",['sortno'=>$k,'id'=>$v]);
        }
        return json(['state' => 'success', 'message' => '更新顺序成功', 'data' => $ids]);
    }

    /**修改附件的title  字段 
     * @param $id
     * @return \think\response\Json
     */
    public function updateTitle($id){
        Db::name('attfile')->where('id',$id)->update(['atttitle'=>input('title')]);
        return json(['state' => 'success', 'message' => '更新标题成功', 'data' => $id]);
    }
     /**修改附件的 desc 字段 
     * @param $id
     * @return \think\response\Json
     */
    public function updateDesc($id){
       Db::name('attfile')->where('id',$id)->update(['attdesc'=>input('desc')]);
       return json(['state' => 'success', 'message' => '更新描述成功', 'data' => $id]);
    }
    /** 旋转附件中的一张图片，对缩略图和大图都旋转
     * @param $id
     * @param $angle  要旋转的角度
     * @return \think\response\Json
     */
    public function rotateImg($id,$rotate){
        //todo: 按顺序取JSON
        if($rotate=='right')
        {
            $degrees=90;
        }
        else
        {
            $degrees=-90;
        }
        $row=Db::name('attfile')->where('id',$id)->field('fileurl,smallimgurl,oldname')->find();
        $fileurl=ltrim($row['fileurl'],"/");
        $smallimgurl=ltrim($row['smallimgurl'],"/");
        $suffix = substr($fileurl, strrpos($fileurl, '.'));//后缀
        $image = Image::open($fileurl);
        $new_fileurl=str_replace($suffix,createNonceStr(1).$suffix, $fileurl);
        $image->rotate($degrees)->save($new_fileurl,null,100);
        $update['fileurl']='/'.$new_fileurl;
        @unlink( $fileurl);
        if(!empty($smallimgurl))
        {
            $suffix='_s'.$suffix;
            $image = Image::open($smallimgurl);
            $new_smallimgurl=str_replace($suffix,createNonceStr(1).$suffix, $smallimgurl);
            $image->rotate($degrees)->save($new_smallimgurl,null,100);
            @unlink( $smallimgurl);
            $update['smallimgurl']='/'.$new_smallimgurl;
        }
        Db::name('attfile')->where('id',$id)->update($update);
        return json(['state' => 'success', 'message' => '旋转成功', 'data' => ['fileurl'=> $update['fileurl']]]);
    }

    
    /** 删除一个附件，必须提供正确的ID和TOKEN
     * @param $id
     * @param $token
     * @return \think\response\Json
     */
    public function delete($id,$token){
        //先找到文件，把它磁盘文件删除。
        $row=Db::name('attfile')->where('id',$id)->where('token',$token)->find();
        if($row)  
        {
            $fileurl=$row['fileurl'];
            $smallimgurl=$row['smallimgurl'];
            @unlink(trim($fileurl,'/'));
            @unlink(trim($smallimgurl,'/'));
        }
        Db::name('attfile')->where('id',$id)->where('token',$token)->delete();
        return json(['state' => 'success', 'message' => '删除成功', 'data' => $id]);
    }

    /**  显示图片裁剪界面
     * @param $src 原图片URL
     * @param int $width 要裁剪的宽
     * @param int $height要裁剪的高
     * @return \think\response\View
     */
    public function imgCrop($src,$width=200,$height=200){
        $cropsizelist=cache('sysconfig')['imgcrop'];
        $options='';
        if ($cropsizelist) {
            $listarr=explode(';',$cropsizelist);
            foreach ($listarr as $k => $v) {
                $item=explode(':',$v);
                $title=$item[0];
                if (preg_match('/^\d*\*\d*$/', $item[1])) {
                    $WAH=explode('*',$item[1]);
                    $width=$WAH[0];
                    $height=$WAH[1];
                    $options.="<option  data-width='$width' data-height='$height'>$title</option>";
                }
                
            }
        }
        $this->assign('options',$options);
        $this->assign('src',$src);
        $this->assign('width',$width);
        $this->assign('height',$height);
        return view();
    }
    /**根据给定的图片URL，旋转角度，绽放比例，裁剪坐标，对指定的URL图片，进行裁剪，并生成以URLx.ext地址，
    *前端回调中可以引用result.data.url得到裁剪后的新图片URL
     * @return \think\response\Json
     */
    public function imgCropSave(){
        
        $data=input('post.');
        $file=ltrim( input('src'),'/') ;
       // dd($file);//test
        if (!file_exists($file)) {
            throw new Exception("文件不存在");           
        }

        $suffix = substr($file, strrpos($file, '.'));//后缀
        $new_name=str_replace($suffix,createNonceStr(1).$suffix, $file);

        $image=Image::open($file);
        $width = $image->width(); 
        $height = $image->height();
        $image->rotate($data['dataRotate']);
        
        if (!empty($data['dataZoom'])) {
           $image->thumb($width*$data['dataZoom'],$height*$data['dataZoom'],6);
        } 
        $image->crop($data['dataWidth'],$data['dataHeight'],$data['dataX'],$data['dataY']);       
        $image->save($new_name);
        $data['url']='/'.$new_name;
        return json(['state' => 'success', 'message' => '裁剪成功', 'data' => $data]);
 
    }
    /**
     * 图片裁剪
     * 取到base64加密的图片内容生成规定尺寸的图片
     * @return [json] [description]
     */
    public function cropsave()
    {
        $img=input('imgBase');//图片经过base64加密
        $file=input('post.');
        $img=substr($img,strpos($img,',')+1);
        $new_name='.'.$file['src'];
        $suffix = substr($new_name, strrpos($new_name, '.'));//后缀
        $new_name=str_replace($suffix,createNonceStr(8).$suffix, $new_name);
        file_put_contents($new_name,base64_decode($img));
        $image=Image::open($new_name);
        //thumb默认以填充方法（1）缩放，6为以规定尺寸缩放图片，
        $image->thumb($file['width'],$file['height'],6);
        $image->save($new_name);
        $data['url']=substr($new_name, strpos($new_name, '.')+1);
        return json(['state' => 'success', 'message' => '裁剪成功', 'data' => $data]);
    }
}