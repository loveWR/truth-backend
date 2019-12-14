<?php

/**
 * 通用的树型类，可以生成任何树型结构

 */
class Tree
{
    /**
     * 生成树型结构所需要的2维数组
     * @var array
     */
    public $arr = array();

    /**
     * 生成树型结构所需修饰符号，可以换成图片
     * @var array
     */
    private $icon = array('│', '├', '└');
    private $nbsp = '&nbsp;';
    private $str='';
    private $ret = '';
    private $config=array(
        'id'=>'id',
        'parentid'=>'pid',
        'name'=>'name',
        'child'=>'child',
    );

    /**
     * 构造函数，初始化类
     * @param array 2维数组，例如：
     * array(
     *      1 => array('id'=>'1','parentid'=>0,'name'=>'一级栏目一'),
     *      2 => array('id'=>'2','parentid'=>0,'name'=>'一级栏目二'),
     *      3 => array('id'=>'3','parentid'=>1,'name'=>'二级栏目一'),
     *      4 => array('id'=>'4','parentid'=>1,'name'=>'二级栏目二'),
     *      5 => array('id'=>'5','parentid'=>2,'name'=>'二级栏目三'),
     *      6 => array('id'=>'6','parentid'=>3,'name'=>'三级栏目一'),
     *      7 => array('id'=>'7','parentid'=>3,'name'=>'三级栏目二')
     *      )
     * @param array $config 配置数组字段名称
     * @return boolean
     */
    public function init($arr=array(),$config=array())
    {
        $this->arr = $arr;
        $this->ret = '';
        $this->str='';
        if($config) $this->config=array_merge($this->config,$config);
        return is_array($arr);
    }
    /**
     * 递归重组节点信息为多维数组
     * @param array
     * @param int
     * @param string
     * @param string
     * @param string
     * @return array
     */
    public function get_arraylist(&$node, $pid = 0)
    {
        $arr = array();
        foreach ($node as $v) {
            if ($v [$this->config['parentid']] == $pid) {
                $v [$this->config['child']] = $this->get_arraylist($node, $v [$this->config['id']]);
                $arr [] = $v;
            }
        }
        return $arr;
    }
    /**
     * @param array $data
     * @param int pid
     * @param level pid
     * @return array $array
     * 传入数组化成树状结构
     */
    public function get_tree($data,$pid=0,$level=1)
    {
        $array=[];
        foreach ($data as $k => $v) {
            if($v[$this->config['parentid']]==$pid)
            {
                $v['level']=$level;
                $array[]=$v;
                $array=array_merge($array,$this->get_tree($data,$v['id'],$level+1));
            }
        }
        return $array;
    }
    public function bootstrap_tree($data, $pid = 0)
    {
        $arr = [];
        foreach ($data as $k => $v) {
            if($v['pid']==$pid)
            {
                $v['href']='#'.$v['id'];
                $v['nodes']=$this->bootstrap_tree($data,$v['id']);
                if(count($v['nodes'])==0)
                {
                    unset($v['nodes']);
                }
                unset($v['pid']);
                unset($v['id']);
                $arr[]=$v;
            }
        }
        return $arr;
    }
}