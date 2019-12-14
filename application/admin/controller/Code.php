<?php
namespace app\admin\controller;
use think\Db;
use app\common\model\AuthRule;
class Code extends Base
{
    /**
     * 显示资源列表
     */
    public function index()
    {   
        $sql = "select * from codetype order by pid,sortno  ";
        $data = Db::query($sql);
        foreach ($data as &$r) {
            $r['target']='codemain';
            if($r['isdir']==1)
            {
                $r["url"] ='';
            }else {
                $r["url"] = $r["listtype"] == "table" ? "__ROOT__/admin/code/table?typeid={$r['id']}" : "__ROOT__/admin/code/tree?typeid={$r['id']}";
            }
        }
        $this->assign('zNodes', json_encode($data, JSON_UNESCAPED_UNICODE));
        return $this->fetch();
    }

    public function create()
    {
        $data=Db::name('codetype')->where('id',input('typeid'))->find();
        if (input('pid')) {
            $pname=Db::name('code')->where('id',input('pid'))->value('codename');
        }else{
            $pname='顶级';
        }
        $typename=$data['typename'];
        $listtype=$data['listtype'];
        $typekey=$data['typekey'];
        $this->assign('typekey',$typekey);
        $this->assign('typename',$typename);
        $this->assign('listtype',$listtype);
        
        $this->assign('pid',input('pid'));
        $this->assign('pname',$pname);
        return $this->fetch();
    }
    /**
     * 保存新建的资源
     */
    public function save()
    {
        $data=input('post.');
        $data['type_id']=$data['type_id'];
        Db::name('code')->insert($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'新增成功']);
    }

    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        $data=Db::name('code')->where('id',$id)->find();
        $data1=Db::name('codetype')->where('id',input('typeid'))->find();
        if ($data['pid']) {
            $pname=Db::name('code')->where('id',$data['pid'])->value('codename');
        }else{
            $pname='顶级';
        }
        $typename=$data1['typename'];
        $listtype=$data1['listtype'];
        $this->assign('typename',$typename);
        $this->assign('listtype',$listtype);
        $this->assign('typename',$typename);
        $this->assign('pname',$pname);
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        $data=Db::name('code')->where('id',$id)->find();
        $data1=Db::name('codetype')->where('id',input('typeid'))->find();
        if ($data['pid']) {
            $pname=Db::name('code')->where('id',$data['pid'])->value('codename');
        }else{
            $pname='顶级';
        }
        $typename=$data1['typename'];
        $listtype=$data1['listtype'];
        $typekey=$data1['typekey'];
        $this->assign('typekey',$typekey);
        $this->assign('typename',$typename);
        $this->assign('listtype',$listtype);
        $this->assign('typename',$typename);
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
        $data['type_id']=input('type_id');
        Db::name('code')->where('id',$id)->update($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'修改成功']);
    }

    /**
     * 删除指定资源
     */
    public function delete()
    {
        Db::name('code')->where('id','in',input('ids'))->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }

    public function table($typeid)
    {
       return view();
    }
    public function table_json($typeid)
    {
        $typeid=(int)$typeid;
        $w='';
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='') {
            $w.=" and ( code like '%{$k}%' or  codename like '%{$k}%' or  shortname  like '%{$k}%' or  mnemonic  like '%{$k}%' )";
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT  *  ";
        $psql->from ="FROM code     ";
        $psql->where =" where type_id={$typeid} ".$w;
        $psql->keyIndex='id desc';
        $db=new \DbTools();
        $pager=new \PageJQ();
        $rows=$db->getDataJQ($psql,$pager);
      
        foreach ($rows as $k => $v) {
            $rows[$k]['issystem']=config('dict')['yesorno'][$v['issystem']];
        }
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
    }
    public function tree($typeid)
    {   
        $k=addslashes( input('keyword'));//防SQL注入
        if($k)
        {
            $w.=" and ( code like '%{$k}%' or  codename like '%{$k}%' or  shortname  like '%{$k}%' or  mnemonic  like '%{$k}%' )";
        } 
        $sql = "select * from code where type_id=$typeid $w order by pid,sortno  ";
        $data = Db::query($sql);
        foreach ($data as &$r) {
            $r['target']='rightname';
            if($r['isdir']==1)
            {
                $r["url"] ='';
            }else {
                $r["url"] = "__ROOT__/admin/code/read?id={$r['id']}&typeid={$typeid}" ;
            }
        }
        $this->assign('zNodes', json_encode($data, JSON_UNESCAPED_UNICODE));
        return $this->fetch();
    }
    public function getAllParent($pid)
    {
        $arr=[];
        if($pid!=0)
        {
            $parent=Db::name('code')->where('id',$pid)->field('pid,codename')->find();
            $arr[]=$parent['codename'];
            $arr=array_merge($arr,$this->getAllParent($parent['pid']));
        }
        return $arr;
    }

  public function getCodeHead(){
  	    $name=input('name');
    	$data=Db::name('code')->field('codename,id')
        ->where('codename','like','%'.$name.'%')
        ->where('type_id',input('typeid'))
        ->whereOr('id','like','%'.$name.'%')
        ->select();
    	$json=$data;
        return json($json);
    	
    	
    	
    }
}
