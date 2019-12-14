<?php
use think\Db;

class DbTools
{
    /**
     * @param \PageSQL $pagesql
     * @param \PageJQ  $PageJQ
     * @return mixed
     *获取JQgrid所需的翻页数据，
     *
     * 根据给定的PageSQL和Pagination，进行分页查询，返回二维数组，并把总页数，总记录数写回$pagination类的相应属性
     */
    public function getDataJQ($pagesql, $pageJQ,$params=[])
    {
        $sql = $this->jqSql($pagesql, $pageJQ,$params);
        $data = Db::query($sql,$params);
        return $data;

    }

    /**
     * @param \PageSQL $ps 类的实例
     * @param  \PageJQ $pa 类的实例
     * @return mixed|string      返回分页查询的SQL语句
     */
    private function jqSql($ps, $pa,$params=[])
    {
        //先取总记录数
        $sql = "select count(*) cnt $ps->from $ps->where";
        $ar = Db::query($sql,$params);
        $records = $ar[0]['cnt'];
        $pa->records = $records;
        //计算总页数，确保当前页码>0 不大于总页数
        $pa->total = ceil($records / $pa->rows);
        $pa->page = $pa->page > $pa->total ? $pa->total : $pa->page;
        $pa->page = $pa->page < 1 ? 1 : $pa->page;
        //计算翻页记录起止数
        $start = ($pa->page - 1) * $pa->rows;
        $end =  $pa->rows;
        if(!$ps->orderBy){
            if(empty($ps->keyIndex))abort(-1,'请通过PageSQL的keyIndex指定唯一字段');
             //增加主键排序，在 pageSQL实例中设置的。如果前端未指定排序条件 ，则以pagesql->keyIndex排序
            $paOrderBy=$pa->orderBy.( empty($pa->orderBy)  ? 'order by ' :  ',') . $ps->keyIndex; 
        }
        //排序子句的构成。 优先使用PageSQL->OrderBy，未指定则以前端排序条件组合 。
        $orderby = empty($ps->orderBy) ? $paOrderBy : $ps->orderBy;
        $sql = "$ps->select $ps->from $ps->where $orderby limit $start,$end ";
        return $sql;
    }

/** 
     * @param \PageSQL $pagesql
     * @param \PageDT $pager
     * @return mixed
     * 获取DataTables 所需的翻页数据，
     *根据给定的PageSQL和PageDT，进行分页查询，返回二维数组，并把总页数，总记录数写回$pageDT类的相应属性
     */
    public function getDataDT($pagesql, $pageDT,$params=[])
    {
        $sql = $this->dtSql($pagesql, $pageDT,$params);
        $data = Db::query($sql,$params);
        return $data;
    }

   /**
     * @param \PageSQL $ps 类的实例
     * @param  \PageDT $pa 类的实例
     * @return mixed|string      返回分页查询的SQL语句
     */
    private function dtSql($ps, $pa,$params=[])
    {
        //先取总记录数
        $sql = "select count(*) cnt $ps->from $ps->where";
        $ar = Db::query($sql,$params);
        $pa->records= $ar[0]['cnt'];
         
        //计算翻页记录起止数
        $start =   $pa->start;
        $end = $pa->length;
        if(!$ps->orderBy){

        if(empty($ps->keyIndex))abort(-1,'请通过PageSQL的keyIndex指定唯一字段');
         //增加主键排序，在 pageSQL实例中设置的。如果前端未指定排序条件 ，则以pagesql->keyIndex排序
        $paOrderBy=$pa->orderBy.( empty($pa->orderBy)  ? 'order by ' :  ',') . $ps->keyIndex; 
        }
        //排序子句的构成。 优先使用PageSQL->OrderBy，未指定则以前端排序条件组合 。
        $orderby = empty($ps->orderBy) ? $paOrderBy : $ps->orderBy;
        $sql = "$ps->select $ps->from $ps->where $orderby limit $start,$end ";

        return $sql;
    }

    /**
     * @param $table 表名
     * @param int $pid    起始父ID
     * @param $idfield ID字段名
     * @param $pidfield 父ID字段名
     * @return array |int    数组方式返回所有的子节点ID，不包括起始父ID
     */
    public function getTreeSubId($table,$pid,$idfield='id',$pidfield='pid')
    {
        //todo:
        static $result1=array();
        if (!empty($pid)) {
            $aid=Db::name($table)->where($pidfield,'in',$pid)->column($idfield);
            $result1=array_merge($aid,$result1); 
            $this->getTreeSubId($table,$aid,$idfield,$pidfield);
        }
        return $result1;
    }

    /**
     * @param $table
     * @param  int   $subid 起始子节点
     * @param string $idfield  ID字段名
     * @param string $pidfield 父ID字段名
     * @return array int 数组方式返回所有父点节的ID，不包括起始子节点subid
     */
    public function getTreeParentId($table,$subid,$idfield='id',$pidfield='pid')
    {
        //todo:
        static $result2=array();
        if (!empty($subid)) {
            $pid=Db::name($table)->where($idfield,'in',$subid)->column($pidfield);
            $result2=array_merge($pid,$result2); 
            $this->getTreeParentId($table,$pid,$idfield,$pidfield);
        }
        return $result2;
    }

    /**
     * @param $table 表名
     * @param $id    最深节点ID
     * @param $showfield  显示的字段名
     * @param string $idfield  ID字段名
     * @param string $pidfield 父ID字段名
     * @return string  返回 例如： /分局/派出所/警区 这样的路径字符串
     */
    public function getTreePath($table,$id,$showfield,$idfield='id',$pidfield='pid')
    {
        
        static $result3=array();
        if (!empty($id)) {
            $pid=Db::name($table)->where($idfield,$id)->value($pidfield);
            $name=Db::name($table)->where($idfield,$id)->value($showfield);
            array_unshift($result3,$name);
            $this->getTreePath($table,$pid,$showfield,$idfield,$pidfield);
        }
        
        return '/'.implode('/',$result3);

    }

}