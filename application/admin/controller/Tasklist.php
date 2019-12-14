<?php

namespace app\admin\controller;
use think\Db;
use app\common\model\AuthRule;
use app\common\model\Adminusers;
use think\Cache;
class Tasklist extends Base
{
    /**
     * 显示资源列表
     */
    public function index()
    {
        return $this->fetch();
    }
    /**
     * 首页json数据
     */
    public function index_json()
    {   
        $w='';
        //select下拉框条件
        // if(input('tid') )
        // {$r=input('tid/d');//防SQL注入强制整形
        //     $w.=" and t.id in (select id from codetype where id={$r})";
        // }
        if(input('id') )
        {$r=input('id/d');//防SQL注入强制整形
        $w.=" and id in (select id from tasklist where id={$r})";
        }
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='') {
            $w.=" and ( id like '%{$k}%' or  rolename like '%{$k}%' or  roledesc  like '%{$k}%' )";
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT a.* , b.taskname ";
        $psql->from =" FROM tasklist a left join taskinfo b on a.taskid=b.id ";
        $psql->where =' where 1=1 '.$w;
        $psql->keyIndex='id desc'; 
        $db=new \DbTools();
        $pager=new \PageJQ();
        $rows=$db->getDataJQ($psql,$pager);
        
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
    }
    /**
     * 显示创建资源表单页.
     */
    public function create()
    {

        $data=Db::name('taskinfo')->select();
        $tasklist=$this->initSelectByData($data,'taskid','id','taskname');
        $this->assign('tasklist',$tasklist);
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     */
    public function save()
    {   
        
        $data=input('post.');
        $isPassTime=(strtotime($data['start_time'])-time())>0?true:false;
       
        if (!$isPassTime&&$data['isrun']) {
            return json(['state'=>'error','message'=>'执行时间是过去时，任务将不会执行']);
        }
        Db::name('tasklist')->insert($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'新增成功']);
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        $data=Db::name('tasklist')->where('id',$id)->find();
        $task=Db::name('taskinfo')->where('id',$data['taskid'])->value('taskname');
        $this->assign('task',$task);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
       
        $data=Db::name('tasklist')->where('id',$id)->find();
        $data1=Db::name('taskinfo')->select();
        $tasklist=$this->initSelectByData($data1,'taskid','id','taskname',$data['taskid']);
        $this->assign('tasklist',$tasklist);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.');
        $isPassTime=(strtotime($data['start_time'])-time())>0?true:false;
        if (!$isPassTime&&$data['isrun']) {
            return json(['state'=>'error','message'=>'执行时间是过去时，任务将不会执行']);
        }
        Db::name('tasklist')->update($data);
        clearTokenGUID();
        
        return json(['state'=>'success','message'=>'修改成功']);
    }

    /**
     * 删除指定资源
     */
    public function delete()
    {
        Db::name('tasklist')->where('id','in',input('ids'))->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }
    public function starttask()
    {

       return $this->fetch();
    }
    public function execTask()
    {

        $password=md5(input('password'));
        $adminpwd=Db::name('adminusers')->where('id',1)->value('password');
        if ($password!==$adminpwd) {//注意：密码是否为md5加密
           return json(['state'=>'error','message'=>'密码错误']);
        }
        $res=action('admin/Task/main',['pwd'=>$password,'issafe'=>true]);
        if ($res) {
            return $res;
        }
        return json(['state'=>'success','message'=>'任务执行成功']);
    }

}
