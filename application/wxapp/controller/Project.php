<?php

namespace app\wxapp\controller;
use think\Db;
use app\admin\controller\Base;
class Project extends Base
{
      
    /**
     * 显示资源列表
     */
    public function index()
    {
        $k=addslashes( input('keyword'));//防SQL注入
        if($k)
        {
            $w.=" where  ( project_name like '%{$k}%'   )";
        }    
        $sql = "select *   from project $w order by id desc  ";
        $data = Db::query($sql);

         foreach ($data as &$r) {
            $r['target']='rightframe';
            $r["url"] =  "__ROOT__/wxapp/project/loglist?id={$r['id']}"; 
        }
        $this->assign('zNodes', json_encode($data, JSON_UNESCAPED_UNICODE)); //中文原样输出
        return $this->fetch();
    }
    /**
     * 首页json数据
     */
    public function index_json()
    {   
        $w='';
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='') {
            $w.=" and (   project_name like '%{$k}%' )";
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT * ";
        $psql->from =" FROM project  ";
        $psql->where =' where 1=1 '.$w;
        $psql->keyIndex='id desc'; 
        //$psql->orderBy=' order by rolename asc,id desc  '; // 通过后台程序固定排序时，将前端JQ列全部设置为不可排序。

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
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     */
    public function save()
    {   
        
        $data=input('post.');
        Db::name('project')->insert($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'新增成功']);
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        $data=Db::name('project')->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        $data=Db::name('project')->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.');
        Db::name('project')->update($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'修改成功']);
    }

    /**
     * 删除指定资源
     */
    public function delete()
    {
        Db::name('project')->where('id','in',input('ids'))->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }

    
    public function loglist($id)
    {
        return $this->fetch();
    }

    public function loglist_json($pid)
    {
        $w='';
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='') {
            $w.=" and (   project_name like '%{$k}%' )";
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT a.*,b.realname,c.project_name ";
        $psql->from =" FROM worklogs a inner join users b on a.userid=b.id left join project c on a.pid=c.id ";
        $psql->where =" where pid=$pid ".$w;
        $psql->keyIndex='a.id desc'; 
        //$psql->orderBy=' order by rolename asc,id desc  '; // 通过后台程序固定排序时，将前端JQ列全部设置为不可排序。

        $db=new \DbTools();
        $pager=new \PageJQ();
        $rows=$db->getDataJQ($psql,$pager);
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
    }


}
