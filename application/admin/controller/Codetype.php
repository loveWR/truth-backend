<?php
namespace app\admin\controller;
use think\Db;
use app\common\model\AuthRule;
class Codetype extends Base
{
    /**
     * 显示资源列表
     */
    public function index()
    {
        $k=addslashes( input('keyword'));//防SQL注入
        if($k)
        {
            $w.=" where  ( typekey like '%{$k}%' or  typename like '%{$k}%' or  remark  like '%{$k}%'   )";
        }    
        $sql = "select *   from codetype $w order by pid,sortno,id  ";
        $data = Db::query($sql);

         foreach ($data as &$r) {
            $r['target']='rightframe';
            $r["url"] =  "__ROOT__/admin/codetype/read?id={$r['id']}"; 
        }
        $this->assign('zNodes', json_encode($data, JSON_UNESCAPED_UNICODE)); //中文原样输出
        return $this->fetch();
            
    }

    /**
     * 显示创建资源表单页.
     */
    public function create()
    {
        $depttype=Db::name('codetype')->field('typekey')->select();
        $listtype=$this->initSelectByDict('listtype','listtype');
        $this->assign('listtype',$listtype);
        $this->assign('depttype',$depttype);
        
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     */
    public function save()
    {   
        $data=input('post.');
        Db::name('codetype')->insert($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'新增成功']);
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        $data=Db::name('codetype')->where('id',$id)->find();
        $pname=Db::name('codetype')->where('id',$data['pid'])->value('typename');
        if(!$pname){
            $pname="顶级";
        }
        $data['isdir']=config('dict')['yesorno'][$data['isdir']];
        $data['issystem']=config('dict')['yesorno'][$data['issystem']];
        $data['listtype']=config('dict')['listtype'][$data['listtype']];
        $this->assign('pname',$pname);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        $data=Db::name('codetype')->where('id',$id)->find();
        $pname=Db::name('codetype')->where('id',$data['pid'])->value('typename');
        if(empty($pname)){
            $pname="顶级";
        }
        $listtype=$this->initSelectByDict('listtype','listtype',$data['listtype']);
        $this->assign('listtype',$listtype);
        $this->assign('pname',$pname);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.');
        Db::name('codetype')->where('id',$id)->update($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'修改成功' ]);
    }
    /**
     * 删除指定资源
     */
    public function delete()
    {
        $ids=input('ids');
        if(Db::name('codetype')->where('id','in',$ids)->delete())
        {
            return json(['state'=>'success','message'=>'删除成功']);
        }
    }
}
