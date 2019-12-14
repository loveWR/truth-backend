<?php
namespace app\admin\controller;
use think\Db;
class Testgii extends Base
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
    	//单位条件，可以有多个ID
    	$w.=idsInWhere('deptid',input('deptid'),'and');  // deptid in (1,2,3,)
    	if(input('roleid') )
    	{
    	    $r=input('roleid/d');//防SQL注入强制整形
    	    $w.=" and u.id in (select userid from user_role where roleid={$r})";
    	}

    	$k=addslashes( input('keyword'));//防SQL注入
    	if($k)
    	{
    	$w.=" and ( mobile like '%{$k}%' or  realname like '%{$k}%' or  loginid  like '%{$k}%' or  deptname  like '%{$k}%' )";
    	}
    	$psql=new  \PageSQL();
    	$psql->select="SELECT u.id,loginid,realname,mobile,lastlogin,d.deptname ";
    	$psql->from ="FROM adminusers u inner join dept d on u.deptid=d.id   ";
    	$psql->where =' where 1=1 '.$w;
    	$db=new \DbTools();
    	$pager=new \Pagination();
    	$rows=$db->getPage($psql,$pager);
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
        Db::name('')->insert($data);
        return json(['state'=>'success','message'=>'新增成功']);
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        return $this->fetch();
    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        $data=Db::name('')->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.');
        Db::name('')->where('id',$id)->save();
        return json(['state'=>'success','message'=>'修改成功']);
    }

    /**
     * 删除指定资源
     */
    public function delete()
    {
        Db::name('')->where('id','in',input('ids'))->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }
}