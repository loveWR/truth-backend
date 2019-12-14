<?php

namespace app\admin\controller;
use think\Db;
class Roles extends Base
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
        //采用TP助手和链式操作
        // $m=Db::name('roles');
        // $k=addslashes( input('keyword'));//防SQL注入
        // $w='';
        // if($k!='') {
        //     $w=" rolename like '%{$k}%' or  roledesc  like '%{$k}%' ";
        // }
        // $frist=input('rows')*(input('page')-1);
        // $data=$m->where($w)->limit($frist,input('rows'))->select();
        // $json['rows']=$data;
        // $json['records']=$m->where($w)->count();
        // $josn['page']=input('page');
        // $json['total']=ceil($json['records']/input('rows'));
        // return json($json);

        //采用自定义数据访问类，取数据。
        $w='';
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='') {
            $w.=" and (   rolename like '%{$k}%' or  roledesc  like '%{$k}%' )";
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT id,rolename,roledesc,sortno ";
        $psql->from =" FROM roles  ";
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
        Db::name('roles')->insert($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'新增成功']);
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        $data=Db::name('roles')->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        $data=Db::name('roles')->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.');
        Db::name('roles')->update($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'修改成功']);
    }

    /**
     * 删除指定资源
     */
    public function delete()
    {
        Db::name('roles')->where('id','in',input('ids'))->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }

     /**
      * 已经指派的用户列表--数据
      */
    public function assign_user_json()
    {
        $w=' and r.roleid='.input('roleid/d');
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='') {
            $w.=" and (u.mobile like '%{$k}%' or  u.loginid like '%{$k}%' 
                 or  u.realname like '%{$k}%' or  d.deptname  like '%{$k}%' )";
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT u.id,u.loginid,u.realname,u.mobile, d.deptname,u.lastlogin  ";
        $psql->from =" FROM adminusers u inner join dept d on u.deptid=d.id inner join user_role r on u.id=r.userid  ";
        $psql->where =' where 1=1 '.$w;
        $psql->keyIndex='u.id desc';
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
      * 已经指派的用户列表
      */
    public function assign_user()
    {
        $data=Db::name('roles')->where('id',input('roleid/d'))->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

        //移出用户
    public function removeUser()
    {
        Db::name('user_role')->where('roleid',input('roleid'))->where('userid','in',input('ids'))->delete();
        return json(['state'=>'success','message'=>'移出成功']);

    }
        //指派用户
    public function addUser()
    {
        $userids=explode(',',input('ids'));
        $rid=input('roleid/d');
        foreach ($userids as $k => $v) {
            if(!Db::name('user_role')->where('userid',$v)->where('roleid',$rid)->find())
            {
                Db::name('user_role')->insert(['userid'=>$v,'roleid'=>$rid]);
            }
        }
        return json(['state'=>'success','message'=>'加入成功']);
    }


    //指派权限
    public function assign_action()
    { 
        //当前角色
        $r=input('roleid/d');
        $data=Db::name('roles')->where('id',$r)->find();
        $this->assign('data',$data);
        //树状数据
        $sql="select a.*, if(actionid>0,'true','false') checked from action a left join 
              (select DISTINCT actionid from  role_action where roleid={$r} )ra on a.id=ra.actionid order by a.sortno";
        $rst=Db::query($sql);
        $this->assign('zNodes',json_encode($rst,JSON_UNESCAPED_UNICODE));
        return $this->fetch();

    }
        //指派权限保存
    public function addAction()
    {
        $rid=input('roleid/d');
        Db::name('role_action')->where('roleid',$rid)->delete();//先删除这个角色原有的权限
        //然后重新加入所有勾选的权限ID
        $actionid=explode(',',input('ids'));
        foreach ($actionid as $k => $v) {
           Db::name('role_action')->insert(['roleid'=>$rid,'actionid'=>$v]);
       }
       return json(['state'=>'success','message'=>'指派成功']);
    }



}
