<?php
namespace app\admin\controller;
use think\Db;
use think\Exception;
class Tools extends Base
{
    /**
     * 通用选择用户弹窗
     * @param string $ismulti  true/false 是否多选
     * @return \think\response\View
     */
     public function selectuser($ismulti='false')
    {
        $this->assign('ismulti',$ismulti);
        return view();
    }
    
    /**为selectuser 提供数据
     * @return \think\response\Json
     */
    public function selectuser_json()
    {
        $w='';
        //单位条件，可以有多个ID
        $w.=idsInWhere('deptid',input('deptid'),'and');  // deptid in (1,2,3,)
        $r=input('rolesid');
        if($r)
        {
            $r=idsToInt($r);//防SQL注入强制整形
            $w.=" and u.id in (select distinct userid from user_role where roleid in ({$r}))";
        }

        $k=addslashes( input('keyword'));//防SQL注入
        if($k)
        {
            $w.=" and ( mobile like '%{$k}%' or  realname like '%{$k}%' or  loginid  like '%{$k}%' or  deptname  like '%{$k}%' )";
        }
        $psql=new \PageSQL();
        $psql->select="SELECT u.id,loginid,realname,mobile,lastlogin,d.deptname ";
        $psql->from ="FROM adminusers u inner join dept d on u.deptid=d.id   ";
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
     * 通用选择用户弹窗
     * @param string $ismulti  true/false 是否多选
     * @return \think\response\View
     */
     public function selectrole($ismulti='false')
    {
        $this->assign('ismulti',$ismulti);
        return view();
    }
    
    /**为selectuser 提供数据
     * @return \think\response\Json
     */
    public function selectrole_json()
    {
        $w='';
        $k=addslashes( input('keyword'));//防SQL注入
        if($k)
        {
            $w.=" and ( rolename like '%{$k}%' or  roledesc like '%{$k}%'  )";
        }
        $psql=new \PageSQL();
        $psql->select="SELECT id,rolename,roledesc ,sortno ";
        $psql->from ="FROM roles  ";
        $psql->where =' where 1=1 '.$w;
        $psql->orderBy='order by  id desc';
        $db=new \DbTools();
        $pager=new \PageJQ();
        $rows=$db->getDataJQ($psql,$pager);
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
    }


    /**根据代码类型，使用表格列表方式选择代码 弹窗
     * @param string $codeType  代码类型 在codetype 表中typekEY字段定义
     * @param string $ismulti  是否多选
     * @return \think\response\View
     */
    public function selectCode($codeType,$ismulti='false')
    {
        $this->assign('codeType',$codeType);
        $this->assign('ismulti', $ismulti );
        return view();
    }

    /** 为selectCode提供数据
     * @param $codeType  代码类型，在codetype 表中typekEY字段定义
     * @return \think\response\Json
     */
    public function selectCode_json($codeType)
    {
        $codeType=addslashes($codeType);
        $DbTools=new \DbTools;
        $PageSQL=new \PageSQL;
        $PageJQ=new \PageJQ;
        $PageSQL->select="select c.* ";
        $PageSQL->from=" from code c inner join codetype t on c.type_id=t.id ";
        $PageSQL->where=" where t.typekey='{$codeType}' ".$this->getWhere("c.code,c.codename,c.mnemonic,c.alias1,c.alias2");
        $PageSQL->orderBy='order by c.sortno,c.id '; 
        $data=$DbTools->getDataJQ($PageSQL,$PageJQ);
        $json['rows']=$data;
        $json['records']=$PageJQ->records;
        $json['page']=$PageJQ->page;
        $json['total']=$PageJQ->total;
        return json($json);
    }

    /**以树状形式弹窗选择指定类型的代码。
     * @param $codeType 代码类型，在codetype 表中typekEY字段定义
     * @param $ismulti  是否多选
      * @param $async  是否异步加载子节点
     * @return \think\response\View
     */
    public function selectCodeTree($codeType,$ismulti='false',$async='false')
    {
        if($async=='false')
           { 
            $k=addslashes($codeType);
            $sql=" select c.* from code c inner join codetype t on c.type_id=t.id where t.typekey='{$k}' ".$this->getWhere("c.code,c.codename,c.mnemonic,c.alias1,c.alias2")." order by c.pid , c.sortno ,c.id ";
            $rst=Db::query($sql);
            $this->assign('zNodes',json_encode($rst,JSON_UNESCAPED_UNICODE));
            }
        else{
                $this->assign('zNodes','[]');
            }
        $this->assign('codeType',$codeType);
        $this->assign('ismulti', $ismulti);
        $this->assign('async', $async);
        return view();
    }
    /**
    *异步加载子节点的情况下由此方法提供数据
    * @param $codeType 代码类型，在codetype 表中typekEY字段定义
    *@param $id  点击某个节点后，传入后台的节点ID，根据此ID加载其子节点。
    */
     public function selectCodeTree_async($codeType,$id=0)
     {
       $k=addslashes($codeType);
       $pid= (int)$id;
       $sql=" select c.* ,(select count(*) from code where pid=c.id) as subcount  from code c inner join codetype t on c.type_id=t.id
       where t.typekey='{$k}' and c.pid={$pid} order by  c.sortno ,c.id ";
       $data=Db::query($sql);
       foreach ($data as &$r) {
        $r['isParent']=$r['subcount']==0 ?'false':'true';
       }
       return json($data); 
    }

    /**通用表格方式弹窗选择数据，需要在dataDLG表中定义
     * @param string $keyword 搜索关键字
     * @return \think\response\View
     */
    public function dlgTable($dlgsn)
    {

        $data=Db::name('datadlg')->where('dlgsn',$dlgsn)->find();
        $listtitle=explode(',', $data['listtitle']);
        $returnfield=explode(',', $data['returnfield']);
        $colwidth=explode(',', $data['colwidth']);
        $fullfield=explode(',', $data['selectfield']);
        $rst=[];
        foreach ($listtitle as $k => $v) {
            if($returnfield[$k]==$data['idfield'])
            {
                $rst[]=array('label'=>$listtitle[$k],'name'=>$returnfield[$k], 'width'=>$colwidth[$k],'sortable'=>false,'key'=>true);
            }
            else
            {
                $rst[]=array('label'=>$listtitle[$k],'name'=>$returnfield[$k] , 
                    'width'=>$colwidth[$k],'sortable'=>false);
            }
        }
        $this->assign('data',$data);
        $this->assign('dlgsn',$dlgsn);
        $this->assign('colmodel', json_encode($rst,JSON_UNESCAPED_UNICODE));
        return view();
    }

     public function dlgtable_Json($dlgsn)
    {
        $params=  array();
        $param=input('param');
        //给定SQL参数，则分解 附加参数 只以以如下格式表达： a-3_b-4, 表示a=3&b=4 所以值的内容不能包含-和_ 
        if($param){
          $p_ar=explode('_', $param);
          foreach ($p_ar as $key => $value) {
            $a=explode('-', $value);
            $params[$a[0]]=$a[1];
          }
        }
        $data=Db::name('datadlg')->where('dlgsn',$dlgsn)->find();
        $DbTools=new \DbTools;
        $PageSQL=new \PageSQL;
        $PageJQ=new \PageJQ;
        $PageSQL->select="select ".$data['selectfield'];
        $PageSQL->from=" from ".$data['fromwhere'];
        $PageSQL->where=$this->getWhere($data['confield']);
        $PageSQL->orderBy=$data['orderby'];
      
        $data=$DbTools->getDataJQ($PageSQL,$PageJQ,$params);
        $json['rows']=$data;
        $json['records']=$PageJQ->records;
        $json['page']=$PageJQ->page;
        $json['total']=$PageJQ->total;
        return json($json);
    }

    /**通用树状方式弹窗 选择数据,需要在dataDLG表中定义
     * @param string $keyword
     * @return \think\response\View  附加参数 只以以如下格式表达： a-3_b-4, 表示a=3&b=4 所以值的内容不能包含-和_ 
     */
    public function dlgtree($dlgsn)
    {   

        $params=  array();
        $param=input('param');
        //给定SQL参数，则分解
        if($param){
          $p_ar=explode('_', $param);
          foreach ($p_ar as $key => $value) {
            $a=explode('-', $value);
            $params[$a[0]]=$a[1];
          }
        }
        $data=Db::name('datadlg')->where('dlgsn',$dlgsn)->find();
        $where=$this->getWhere($data['confield']); 
        $sql="select ".$data['selectfield']." from ".$data['fromwhere']." ".$where." ".$data['orderby'];
        $rst=Db::query($sql,$params);
        $this->assign('data',$data);
        $this->assign('dlgsn',$dlgsn);
        $this->assign('zNodes',json_encode($rst,JSON_UNESCAPED_UNICODE));
        return view();
    }

    /**根据给定的条件 字段清单和前端输入的keyword ，生成LIKE比较条件 。
     * @param $confield  条件字段， 在DATADLG 的confield中定义，如 u.name,d.dept,c.code
     * @return string  如 and (name like '%关键字%' or dept like '%关键字%')
     */
    public function getWhere($confield)
    {
        $keyword=input('keyword');
        $sql='';
        if($keyword)
        {
            $confield=explode(",",$confield);
            foreach ($confield as $k => $v) {
                $k=addslashes($keyword);
                $sql.="or $v LIKE '%{$k}%' ";
            }
            $sql=' and ('. substr($sql,2) .')';
            return $sql;
        }
        return $sql;
    }

}
