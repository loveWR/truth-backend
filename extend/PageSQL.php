<?php
class PageSQL
{
    //select 子句,  eg: select id,name
    public $select;
    // from子句， ，eg: from user u , dept d where u.deptid=d.id
    public $from;
    //where 条件 ，如果from子句中已经有where 则此属性以and 或or 开头，否则以where 开头 eg: where id>0
    public $where;
    //order by 子句，以order by 开头。 如果此处指定了order by 则排序以此为准，覆盖JQgrid中的sidx字段排序
    //如果要让用户点击jQgrid的标题进行排序，则保持此属性为空，如果需要有默认排序，则在前端JQgrid中设置
    //要特别注意，MYSQL的orderby 与limit 同时使用时，orderby中的排序必须唯一，否则分页不准确
    public $orderBy;

    //MYSQL的orderby 与limit 同时使用时，orderby中的排序必须唯一，否则分页不准确。 当前端点击标题排序时，往往需要增加主键字段排序
    //如前端设置sortno排序， select * from users  order by sortno asc , id desc limint 0,10 .  keyIndex 就需要设置为id desc 
    //注意值缀不要order by  关键字。 如：id desc 
    public $keyIndex;

    
}

?>