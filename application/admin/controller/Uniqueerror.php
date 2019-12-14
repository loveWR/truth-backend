<?php
namespace app\admin\controller;
use think\Db;

class Uniqueerror extends Base
{
    public $table='unique_error';
    /**
     * 显示资源列表
     */
    public function index()
    {
        return $this->fetch();
    }

    public function index_json()
    {
        $w='';
        $k= addslashes(input('keyword'));//防SQL注入
        if($k)
        {
            $w.=" and (constraint_name like '%{$k}%' or TABLE_NAME like '%{$k}%'  or msg like '%{$k}%' )";
        }
         
        $dbn=config('database')['database'];
        $psql=new  \PageSQL();
        $psql->select="select e.id, c.table_name  ,c.constraint_name  ,e.msg ";
        $psql->from ="from information_schema.TABLE_CONSTRAINTS c LEFT JOIN unique_error e on  c.CONSTRAINT_NAME=e.uniname and c.table_name=e.tablename ";
        $psql->where =" where table_SCHEMA='{$dbn}'  and CONSTRAINT_TYPE='UNIQUE' ".$w;
        $psql->orderBy="order by c.table_name ,c.CONSTRAINT_NAME";
        $db=new \DbTools();
        $pager=new \PageJQ();
        $rows=$db->getDataJQ($psql,$pager);
        $i=-1;
        //没有ID的，给它赋负数。
        foreach ($rows as $key => &$value) {
            if(empty($value['id']))
            {
                $value['id']=$i-- ;
            }
        }
        $json['rows']=$rows;
        $json['records']=$pager->records;
        $json['page']=$pager->page;
        $json['total']=$pager->total;
        return json($json);
    }
     
    
   

    /**
     * 显示编辑资源表单页.
     */
    public function edit($tablename,$uniname)
    {
        $where['uniname']=$uniname;
        $where['tablename']=$tablename;
        $data=Db::name($this->table)->where($where)->find();
        if (empty($data)) {
            
            $data['tablename']=$tablename;
            $data['uniname']=$uniname;
            $data['msg']='';
            $data['id']='';
           
        }
         $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     */
    public function update()
    {     $data=input('post.');
        if (empty(input('id'))) {
         
           Db::name($this->table)->insert($data);
        }else{
            
            Db::name($this->table)->where('id',input('id'))->update($data);
        }
        clearTokenGUID();
        return json(['state'=>'success','message'=>'修改成功']);

    }

    /**
     * 删除指定资源
     */
    public function delete()
    {   
        Db::name($this->table)->where('id','in',input('ids/a'))->delete();
        return json(['state'=>'success','message'=>'删除成功']);
    }
    
}
