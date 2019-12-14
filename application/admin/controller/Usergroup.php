<?php
namespace app\admin\controller;
use think\Db;
use app\common\model\AuthRule;
class Usergroup extends Base
{
    /**
     * 显示资源列表
     */
    public function index()
    {
        $action=Db::name('action')->select();
        $tree=new \Tree;
        $action=$tree->get_tree($action);
        $this->assign('action',$action);
        return $this->fetch();
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
        $auth= new AuthRule;
        $data['actcode']=input('actcode');
        $data['actname']=input('actname');
        $data['menuurl']=$data['actcode'];
        $data['menutext']=$data['actname'];
        $data['ismenu']=1;
        $data['isdir']=1;
        $data['pid']=2;
        $data['sortno']=100;
        $data['isdir']=0;
        $auth->batch($data);
        return json(['state'=>'success','message'=>'新增成功']);
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {

    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.');
        $model=Db::name('auth_rule');
        return ajax_validate($model,$data,'update');
    }

    /**
     * 删除指定资源
     */
    public function delete()
    {
        Db::name('auth_rule')->where('id','in',input('ids'))->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }
    
}
