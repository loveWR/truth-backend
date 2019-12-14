<?php
namespace app\admin\controller;
use think\Db;
use app\common\model\AuthRule;
class Dept extends Base
{
    /**
     * 显示资源列表
     */
    public function index()
    {
        $k=addslashes( input('keyword'));//防SQL注入
        if($k)
        {
            $w.=" where  ( deptname like '%{$k}%' or  fullname like '%{$k}%' or  shortname  like '%{$k}%'  or  mnemonic  like '%{$k}%' or  remark  like '%{$k}%'   )";
        }    
        $sql = "select *   from dept $w order by pid,sortno,id  ";
        $data = Db::query($sql);
 
         foreach ($data as &$r) {
            $r['target']='rightframe';
                $r["url"] =  "__ROOT__/admin/dept/read?id={$r['id']}";
        }
        $this->assign('zNodes', json_encode($data, JSON_UNESCAPED_UNICODE)); //中文原样输出
        return $this->fetch();
    }
    /**
     * 显示创建资源表单页.
     */
    public function create()
    {   
        $typeidSelect= $this->initSelectByType("depttype","typeid");
        $this->assign('typeidSelect',$typeidSelect);
        return $this->fetch();
    }
    /**
     * 保存新建的资源
     */
    public function save()
    {   
        $data=input('post.');
        Db::name('dept')->insert($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'新增成功']);
    }
    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        $data=Db::name('dept')->where('id',$id)->find();
        $data['isdir']=config('dict')['yesorno'][$data['isdir']];
        $pid=$data['pid'];
        $pname=Db::name('dept')->where('id',$data['pid'])->value('deptname');
        $depttype=Db::name('code')->where('id',$data['typeid'])->value('codename');
        $this->assign('pname',$pname);
        $this->assign('depttype',$depttype);
        $this->assign('data',$data);
        return $this->fetch();
    }
    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        $data=Db::name('dept')->where('id',$id)->find();
        $typeidSelect= $this->initSelectByType("depttype","typeid",[$data['typeid']]);
        $pid=$data['pid'];
        $pname=Db::name('dept')->where('id',$data['pid'])->value('deptname');
        $this->assign('pname',$pname);
        $isdirSelect=$this->initSelectByDict('yesorno','isdir',[$data['isdir']] );
        $this->assign('isdirSelect',$isdirSelect);
        $this->assign('typeidSelect',$typeidSelect);
        $this->assign('data',$data);

        return $this->fetch();
    }
    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.');
        $tool= new \DbTools;
        $result=$tool->getTreeSubId('dept',$data['id']);
        $id=$data['id'];
        array_push($result,$id);
        if(in_array($data['pid'], $result))
        {
            return json(['state'=>'error','message'=>'父节点不能选择自己及子节点' ]);
        }
        Db::name('dept')->where('id',$id)->update($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'修改成功' ]);
    }
    /**
     * 删除指定资源
     */
    public function delete()
    {
        $id=input('ids');
        $tool= new \DbTools;
        $result=$tool->getTreeSubId('dept',$id);
        $arr=explode(',',$id);
        $ids=array_merge($arr,$result);
        if(Db::name('dept')->where('id','in',$ids)->delete())
        {
            return json(['state'=>'success','message'=>'删除成功']);
        }
    }
}
