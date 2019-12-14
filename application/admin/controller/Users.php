<?php
namespace app\admin\controller;
use think\Db;
use think\Log;
class Users extends Base
{
    /**
     * 显示资源列表
     */
    public function index()
    {
        //生成select
        $roles=Db::name('roles')->order(" sortno asc ")->select();
        $rolesddl=$this->initSelectByData($roles,'roleid','id','rolename',[],'全部角色');
        $this->assign('rolesddl',$rolesddl);
        return $this->fetch();
    }
    public function out()
    {
        return "test";
    }
    /**
     * 首页json数据
     */
    public function index_json()
    {

        $w='';
        //单位条件，可以有多个ID
        //高级搜索
        if (input('is_adv_search')==1) {
            //sql拼接
            //$this->advsearch_fields()根据datatype拼接筛选条件
            $fieldlist=$this->advsearch_fields(0);
            $advsearch=input('adv_search');

            $w=$this->analy_advsearch($advsearch,$fieldlist);
            //dd($w);
        }else{
            $w.=idsInWhere('deptid',input('deptid'),'and');  // deptid in (1,2,3,)
            if(input('roleid')&&input('roleid'))
            {
                $r=input('roleid/d');//防SQL注入强制整形
                $w.=" and u.id in (select userid from user_role where roleid={$r})";
            }
            $k=addslashes( input('keyword'));//防SQL注入
            if($k!='')
            {
                $w.=" and ( mobile like '%{$k}%' or  realname like '%{$k}%' or  loginid  like '%{$k}%' or  deptname  like '%{$k}%' )";
            } 
        }
        $psql=new  \PageSQL();
        $psql->select="SELECT u.id,loginid,realname,mobile,lastlogin,d.deptname,u.sex";
        $psql->from ="FROM users u inner join dept d on u.deptid=d.id   ";
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
    public function index2()
    {
        return $this->fetch();
    }
    /**
     * index2json数据
     */
    public function index2_json()
    {
        $w='';
        //单位条件，可以有多个ID
        $w.=idsInWhere('deptid',input('deptid'),'and');  // deptid in (1,2,3,)
        if(input('roleid')&&input('roleid'))
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
        $psql->select="SELECT u.id,loginid,realname,mobile,lastlogin,d.deptname,u.sex";
        $psql->from ="FROM users u inner join dept d on u.deptid=d.id   ";
        $psql->where =' where 1=1 '.$w;
        $psql->keyIndex='u.id desc '; 
        
        $db=new \DbTools();
        $pager=new \PageDT();
        $rows=$db->getDataDT($psql,$pager);
        $json['data']=$rows;
        $json['draw']=input('draw');
        $json['recordsTotal']=$pager->records;
        $json['recordsFiltered']=$pager->records;
        return json($json);
    }

    /**
     * 显示创建资源表单页.
     */
    public function create()
    {
        $roles=Db::name('roles')->select();
        $sex=$this->initSelectByDict('sex','sex');
        $defaultrole=$this->initSelectByData($roles,'defaultrole','id','rolename');
        $assignrole=$this->initSelectByData($roles,'assignrole','id','rolename','','','','multiple="multiple"');
        $this->assign('defaultrole',$defaultrole);
        $this->assign('assignrole',$assignrole);
        $this->assign('sex',$sex);
        $this->assign('roles',$roles);
        return $this->fetch();
    }
    /**
     * 保存新建的资源
     */
    public function save()
    {
        $data=input('post.');
        $data['password']=md5($data['password']);
        $uid=Db::name('users')->insertGetId($data);
        if (!empty($data['assignrole'])) {
            foreach ($data['assignrole'] as $k => $v) {
                Db::name('user_role')->insert(['userid'=>$uid,'roleid'=>$v]);
            }
        }
        clearTokenGUID();
        return json(['state'=>'success','message'=>'新增成功']);
    }
    /**
     * 显示指定的资源
     */
    public function read($id)
    {
        $data=Db::name('users')->where('id',$id)->find();
        $data['assignrole']=Db::name('user_role a')->join('roles b','a.roleid=b.id')->where('a.userid',$id)->column('b.rolename');
        $roles=Db::name('roles')->select();
        $deptname=db('dept')->where('id',$data['deptid'])->value('deptname') ;
        $data['sex']=config('dict')['sex'][$data['sex']];
        $this->assign('roles',$roles);
        $this->assign('deptname',$deptname);
        $this->assign('data',$data);
        return $this->fetch();   
    }
    /**
     * 显示编辑资源表单页.
     */
    public function edit($id)
    {
        $data=Db::name('users')->where('id',$id)->find();
        $data['assignrole']=Db::name('user_role')->where('userid',$id)->column('roleid');
        $roles=Db::name('roles')->select();
        $deptname=db('dept')->where('id',$data['deptid'])->value('deptname') ;
        $assignrole=$this->initSelectByData($roles,'assignrole','id','rolename',$data['assignrole'],'','','multiple="multiple"');
        $this->assign('assignrole',$assignrole);
        $this->assign('roles',$roles);
        $this->assign('deptname',$deptname);
        $this->assign('data',$data);
        return $this->fetch();
    }
    /**
     * 保存更新的资源
     */
    public function update($id)
    {
        $data=input('post.');
        $row=Db::name('users')->where('id',$id)->find();
        if($row && $row['loginid']=='root')  //root用户不能被改名
        {
            $data['loginid']='root';
        }
        Db::name('users')->where('id',$id)->update($data);
        Db::name('user_role')->where('userid',$id)->delete();
        if($data['assignrole'])
        {
           foreach ($data['assignrole'] as $k => $v) {
               Db::name('user_role')->insert(['userid'=>$id,'roleid'=>$v]);
           } 
       }
       clearTokenGUID();
       return json(['state'=>'success','message'=>'修改成功']);
   }
    /**
     * 删除指定资源
     */
    public function delete()
    {//root不能删除
        Db::name('users')->where('id','in',input('ids'))->where('loginid','<>','root')->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }

/**
 * 管理员修改密码
 */
public function resetpwd($id)
{
    $user=Db::name('users')->where('id',$id)->find($id);
    if (input('post.')) {
        $data=input('post.');
        Db::name('users')->where('id',$id)->update(['password'=>md5( $data['password'])]);
        return json(['state'=>'success','message'=>'重置密码成功']);
    }

    $this->assign('loginid',$user['loginid']);
    return $this->fetch();
}
/**
     * 位置偏移数据传输arr=array(fid,few,layer); 利用递归
     */
public function node($arr,$nex_uid,$recall_time,$arr_data=array()){
    if($recall_time<48){
        if(!$nex_uid){
            return $date_arr;
        }
        else{
                $arr0= $arr;//备选数据防止更新失败便于重新更新
                
                $arr = db("users")->where("user_id=".$nex_uid)->field("fid,few,layer")-> find(); 

                $recall_time=$recall_time+1;
                if((pow(2, $arr["layer"])-1)>$arr["few"]){
                 $on['layer'] =$arr["layer"];
                 $on['few'] =$arr["few"]+1;
                 $get_uid = db("users")->where($on)->field("user_id")->find();
                 $nex_uid=$get_uid['user_id'];
             }
             else{
                 $on['layer'] =$arr["layer"]+1;
                 $on['few'] =0;
                 $get_uid = db("users")->where($on)->field("user_id")->find();
                 $nex_uid=$get_uid['user_id'];
             }
             array_push($arr_data, $arr);
             return model("users")->node($arr,$nex_uid,$recall_time,$arr_data);
         }
     }
     else{
        $re_cc['arr']=$arr;
        $re_cc['nex_uid']=$nex_uid;
        $re_cc['2']="0000";
        $re_cc['arr_data']=$arr_data;
        return $re_cc;
    }
}
    /**
     * 根据筛选结果导出全部
     * 
     */
    public function expall()
    {       
//todo: 不能使用currentsql
        // 需要导出的字段在index_json方法中定义
        //$data=Db::query(session('currentsql'));
        //create_excel($data);

    }
    public function exp()
    { 
        $ids=input('Ids');
        $data=Db::name('users')
        ->field('password,realname,loginid')
        ->where('id','in',$ids)->select();
        $sql=Db::name('roles')->getLastSql();
        create_excel($data,'',['string','','','','','int']);
    }
    public function expAsTemp()
    {

        $data=Db::name('roles')->where('id < 10')->select();//test
        $path=PUBLIC_PATH.'templateExcel/simple.xls';//test
        createExcelAsTemplate($data,$path);
    }
    public function import()
    {
        $files=explode(',',input('file'));
        $num=['success'=>0,'failed'=>0];
         // 启动事务
        Db::startTrans();
        foreach ($files as $key => $value) {
            $file=substr(str_replace('/','\\',$value),1);
            $data=import_excel(PUBLIC_PATH.$file);
            foreach ($data as $key => $value) {
                foreach ($value as $k => $v) {
                    $data[$key]['name']=changeCode($value[0]);//test
                    $data[$key]['dec']=changeCode($value[1]);//test 
                }                     
                try{
                    Db::name('test')->insert($data[$key]);//test
                    // 提交事务
                    // 记录成功个数
                    $num['success']++;
                    

                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $num['failed']++;
                }
                
            }
        }
        //提交事务
        Db::commit();
        return json(['state'=>'success','message'=>'成功:'.$num['success'].';失败:'.$num['failed']]);
    }
    public function index3()
    {
        //生成select
        $roles=Db::name('roles')->order(" sortno asc ")->select();
        $rolesddl=$this->initSelectByData($roles,'roleid','id','rolename',[],'全部角色');
        $this->assign('rolesddl',$rolesddl);
        return $this->fetch();
    }

    /**
     * 首页json数据
     */
    public function index3_json()
    {
        $w='';
        //单位条件，可以有多个ID
        $w.=idsInWhere('deptid',input('deptid'),'and');  // deptid in (1,2,3,)
        if(input('roleid')&&input('roleid'))
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
        $psql->select="SELECT u.id,loginid,realname,mobile,lastlogin,d.deptname,u.sex";
        $psql->from ="FROM users u inner join dept d on u.deptid=d.id   ";
        $psql->where =' where 1=1 '.$w;
        $db=new \Oci('db2');
        $pager=new \PageJQ();
        $rows=$db->get_page($psql,$pager);
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
    }

    public function  advsearch_fields($init_data=1)
    {
        $data=array();  
        // data键 fieldname,datatype,fieldtitle,inputtype, inputdata,valuename,textname
        //手动定义 ，注意fieldname可能有别名如： a.username,b.deptid，deptname,codename等。
        //['fieldname'=>'','datatype'=>'','fieldtitle'=>'','inputtype'=>'', 'inputdata'=>'','valuename'=>'','textname'=>'']
        /**
           datatype:    int,decimal,varchar,date,datetime
           inputtype:  input,select,datadlgtable,datadlgtree,date,datetime
 

        */
        $sexarr=array();
           if($init_data==1)
           {
             $roles=Db::name('roles')->select();
             $sex=config('dict.sex');
             foreach ($sex as $k => $v) {
                 $sexarr[]=['key'=>$k,'value'=>$v];
             }
         }
         $data=[
            'loginid'=>['fieldname'=>'loginid','datatype'=>'varchar','fieldtitle'=>'登录名','inputtype'=>'input','inputdata'=>'','valuename'=>'','textname'=>''],
            'sex'=>['fieldname'=>'sex','datatype'=>'int','fieldtitle'=>'性别','inputtype'=>'select', 'inputdata'=>$sexarr,'valuename'=>'key','textname'=>'value'],
            'deptid'=>['fieldname'=>'deptid','datatype'=>'int','fieldtitle'=>'单位','inputtype'=>'datadlgtable', 'inputdata'=>'selectdepts','valuename'=>'id','textname'=>'deptname'],
            'lastlogin'=>['fieldname'=>'lastlogin','datatype'=>'datetime','fieldtitle'=>'上次登录时间','inputtype'=>'datetime'],
            'createtime'=>['fieldname'=>'createtime','datatype'=>'datetime','fieldtitle'=>'注册日期','inputtype'=>'date'],
            'defaultrole'=>['fieldname'=>'defaultrole','datatype'=>'int','fieldtitle'=>'默认角色','inputtype'=>'datadlgtable', 'inputdata'=>'selectrole','valuename'=>'id','textname'=>'rolename']

        ];
        return $data;

    }



}
