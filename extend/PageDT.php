<?php

 

//此翻页类，适用于DataTables
class PageDT
{
    //每页记录数
    public $length;
    //起始位置
    public $start;
    //排序表达试
    public $orderBy;
    //符合搜索条件的记录数
    public $records;
    public function __construct( )
    {
        $this->length = input("length",10);
        $this->start = input("start",0);
        $this->records = 0;
        $this->orderBy=getDataTableOrder();  //
    }
 }




?>