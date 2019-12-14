<?php
//此翻页类，适用于JQgrid 
class PageJQ
{
    //每页记录数
    public $rows;
    //当前显示第几页
    public $page;
    //当前排序字段名
    public $sidx;
    //当前字段排序方向，asc顺序 desc倒序
    public $sord;
    //执行查询后符合条件的记录总数
    public $records;
    //执行查询后符合条件的总页数
    public $total;
    //用户通过点击表格头设置排序字段和方向
    public $orderBy;
    public function __construct( )
    {
        $this->rows = input("rows",10);
        $this->page = input("page",1);
        $this->sidx = input("sidx");
        $this->sord = input("sord");
        //前端排序条件 
        $sidx = safeFieldName(input("sidx")); //排序字段，防止SQL注入
        $sord = strtolower(input("sord"))== 'asc' ? 'asc' : 'desc';   //排序方向
        $this->orderBy = empty($sidx) ? '' :  "order by  $sidx $sord" ;
        $this->records = 0;
        $this->total = 0;
    }
 }

?>