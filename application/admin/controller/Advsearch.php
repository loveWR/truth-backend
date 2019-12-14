<?php
namespace app\admin\controller;
use think\Db;
use think\Exception;
class Advsearch extends Base
{

/*****************高级搜索相关功能开始**********************/
/**
datatype:    int,decimal,varchar,date,datetime
inputtype:  input,select,datadlgtable,datadlgtree,date,datetime
 
data键名： fieldname,datatype,fieldtitle,inputtype, inputdata,valuename,textname

业务界面的控制器中手动定义一个方法（如advsearch_fields()），返回以下格式的数组
   $data=[
            'realname'=>['fieldname'=>'realname','datatype'=>'varchar','fieldtitle'=>'登录名','inputtype'=>'input','inputdata'=>'','valuename'=>'','textname'=>''],
            'sex'=>['fieldname'=>'sex','datatype'=>'int','fieldtitle'=>'性别','inputtype'=>'select', 'inputdata'=>$sexarr,'valuename'=>'key','textname'=>'value'],
            'deptid'=>['fieldname'=>'deptid','datatype'=>'int','fieldtitle'=>'单位','inputtype'=>'datadlgtree', 'inputdata'=>'selectdept','valuename'=>'id','textname'=>'deptname'],
            'lastlogin'=>['fieldname'=>'lastlogin','datatype'=>'datetime','fieldtitle'=>'上次登录时间','inputtype'=>'datetime'],
            'defaultrole'=>['fieldname'=>'defaultrole','datatype'=>'int','fieldtitle'=>'默认角色','inputtype'=>'datadlgtable', 'inputdata'=>'selectrole','valuename'=>'id','textname'=>'rolename']

        ];
 ，注意fieldname可能有别名如： a.username,b.deptid，deptname,codename等。

*/
	public function index()
  {
       //$uikey=admin/adminusers/list 
       //$action=admin/adminusers/advsearch_fields
       //$id =   是表ADVSEARCH中的id,表示从收藏 中选择了个已经收藏的条件。
        //  $data=action('admin/adminusers/advsearch_fields' );  list 
         // dd($data);
     //把$data输出到HTML成为JS数组变量，
    // $this->assign('zNodes',json_encode($rst,JSON_UNESCAPED_UNICODE));
    //在HTML中填充select
    //如果ID>0就需要到表ADVSEARCHITEM中把这个搜索定义的搜索项按顺序取出，并json_encode 送到view中，由JS负责填充。 
    /*
      
    */
    $uikey=input('uikey');
    $action=input('action');
    $items='';
    $advid=input('id');
	$route=explode('/', $uikey);
    $route[2]=$action;
    $fieldlist=action(implode('/', $route));
    $this->assign('fieldlist',json_encode($fieldlist,JSON_UNESCAPED_UNICODE));
    $userid=session('admin_id');
    $sql="select id,TITLE,ispub from advsearch where (ISPUB=1 or userid=$userid ) and UIKEY='$uikey'";
    $seldata=Db::query($sql);
    foreach ($seldata as $key => &$value) {
      if ($value['ispub']==1) {
        $value['title']="[公用]".$value['title'];
      }
    }
    $fav_select= $this->initSelectByData($seldata,'fav_select','id','title',$advid,$defaultTxt='请选择',$defaultVal='',$css='');
    $this->assign('fav_select',$fav_select);
    if ($advid>0) {
       $items=Db::name('advsearch')->where('id',$advid)->value('items');
    }

    $this->assign('items',$items);
    $this->assign('uikey',$uikey);
    $this->assign('action',$action);
    //dd(json_encode($items,JSON_UNESCAPED_UNICODE));
		return view();
		
	}


	public function create(){
		
		return view();
	}
    public function save2(){

        $data=input('post.');
        $pdata['uikey']=strtolower($data['uikey']);
        $pdata['title']=$data['title'];
        $pdata['ispub']=$data['ispub'];
        $pdata['items']=$data['items'];
        $pdata['userid']=session('admin_id');
        $oldid=Db::name('advsearch')->where('uikey',$pdata['uikey'])->where('title',$pdata['title'])->where('userid',$pdata['userid'])->value('id');
        if ($oldid) {
            $id=$oldid;
            Db::name('advsearch')->where('id',$id)->update($pdata);
        }
        else{
            $id=Db::name('advsearch')->insertGetId($pdata);
        }
        return json(['state'=>'success','message'=>'加入收藏成功','id'=>$id]);
        
    }
	public function advsearch_list(){
		
		return view();
	}
	public function list_json($uikey){
		$w='';
        //单位条件，可以有多个ID
        $k=addslashes( input('keyword'));//防SQL注入
        if($k!='')
        {   
            $w.=" and ( title like '%{$k}%' or a.remark like '%{$k}%' )";
        } 
        $userid=session('admin_id');
        $uikey=strtolower($uikey);
        $psql=new  \PageSQL();
        $psql->select="SELECT a.userid,a.id,a.uikey,title,a.sortno,ispub,a.remark,b.realname username ";
        $psql->from ="FROM advsearch a left join adminusers b on a.userid=b.id ";
        $psql->where =" where (a.userid=$userid or ispub=1) and a.uikey='$uikey' ".$w;
        $psql->keyIndex='a.id desc'; 
        $db=new \DbTools();
        $pager=new \PageJQ();
        $rows=$db->getDataJQ($psql,$pager);
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
		
	}
    public function edit($id)
    {   
        $data=Db::name('advsearch')->field('title,sortno,remark,ispub')->where('id',$id)->find();
        $this->assign('data',$data);
        return view();
    }
    public function update($id)
    {

        $data=input('post.');
        Db::name('advsearch')->where('id',$id)->update($data);
        clearTokenGUID();
        return json(['state'=>'success','message'=>'修改成功']);
    }
    public function delete ($ids){
        $res=Db::name('advsearch')->where('id','in',$ids)->where('userid',session('admin_id'))->delete();

        if (!$res) {
          return json(['state'=>'error','message'=>'删除失败,只能删除私人收藏']);
        }
        return json(['state'=>'success','message'=>'删除成功']);
    }
  public function fav_isexist()
  {
    $data=input('post.');
    $res=Db::name('advsearch')
            ->where('title',$data['title'])
            ->where('uikey',strtolower($data['uikey']))
            ->where('userid',session('admin_id'))
            ->find();
    if ($res) {
      return true;
    }
    return false;
  }
/*****************高级搜索相关功能结束**********************/
	
}
